<?php

namespace App\Services\Import;

use App\Services\AuditLogger;
use App\Services\Import\Types\AttendanceImport;
use App\Services\Import\Types\DepartmentImport;
use App\Services\Import\Types\HolidayImport;
use App\Services\Import\Types\LeaveBalanceImport;
use App\Services\Import\Types\StaffImport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Two-step CSV import: preview (check every row, import nothing), then
 * confirm (import the rows that passed, all or nothing).
 */
class CsvImportService
{
    public const MAX_ROWS = 2000;

    /** How long a preview can be confirmed for. */
    public const PREVIEW_MINUTES = 60;

    /** Order shown to admins: departments before staff, staff before their records. */
    private const TYPES = [
        DepartmentImport::class,
        StaffImport::class,
        HolidayImport::class,
        LeaveBalanceImport::class,
        AttendanceImport::class,
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /** @return array<string, ImportType> */
    public function types(): array
    {
        return collect(self::TYPES)
            ->map(fn (string $class) => app($class))
            ->keyBy(fn (ImportType $type) => $type->key())
            ->all();
    }

    public function type(string $key): ImportType
    {
        return $this->types()[$key] ?? throw new InvalidArgumentException("Unknown import type \"{$key}\".");
    }

    /** Template file: header row plus one example row. */
    public function template(ImportType $type): string
    {
        $columns = $type->columns();

        return $this->toCsvLine(array_keys($columns)) . $this->toCsvLine(array_column($columns, 'example'));
    }

    /**
     * Read and check the file. Nothing is written.
     *
     * @return array{token: ?string, total: int, valid: int, errors: array<int, array{line:int, message:string, values:array}>, sample: array, ignored_columns: string[]}
     *
     * @throws InvalidArgumentException when the file itself cannot be used
     */
    public function preview(ImportType $type, string $path, int $userId): array
    {
        [$header, $rows] = $this->read($path);

        $columns = $type->columns();
        $missing = array_values(array_diff(array_keys(array_filter($columns, fn ($c) => $c['required'])), $header));

        if ($missing) {
            throw new InvalidArgumentException(
                'The file is missing ' . Str::plural('column', count($missing)) . ': ' . implode(', ', $missing)
                . '. Download the template to see the expected columns.'
            );
        }

        $ignored = array_values(array_diff(array_filter($header), array_keys($columns)));

        $valid  = [];
        $errors = [];
        $seen   = [];

        foreach ($rows as [$line, $values]) {
            try {
                $valid[] = $type->validateRow($values, $seen);
            } catch (RowError $e) {
                $errors[] = ['line' => $line, 'message' => $e->getMessage(), 'values' => $values];
            }
        }

        $token = null;
        if ($valid) {
            $token = (string) Str::uuid();
            Cache::put($this->cacheKey($token), [
                'type'    => $type->key(),
                'user_id' => $userId,
                'rows'    => $valid,
            ], now()->addMinutes(self::PREVIEW_MINUTES));
        }

        return [
            'token'           => $token,
            'total'           => count($rows),
            'valid'           => count($valid),
            'errors'          => $errors,
            'sample'          => array_slice(array_column($rows, 1), 0, 5),
            'ignored_columns' => $ignored,
        ];
    }

    /**
     * Import the rows that passed preview, in one transaction.
     *
     * @return array{created:int, updated:int, skipped:int}
     *
     * @throws InvalidArgumentException when the preview has expired or belongs to someone else
     */
    public function confirm(ImportType $type, string $token, int $userId): array
    {
        $preview = Cache::get($this->cacheKey($token));

        if (!$preview || $preview['type'] !== $type->key() || $preview['user_id'] !== $userId) {
            throw new InvalidArgumentException('This preview has expired. Upload the file again.');
        }

        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($type, $preview, $userId, &$counts) {
            foreach ($preview['rows'] as $row) {
                $counts[$type->import($row, $userId)]++;
            }
        });

        Cache::forget($this->cacheKey($token));

        $this->audit->record('import', null, [], $counts + ['type' => $type->label()],
            "{$type->label()} import: {$counts['created']} created, {$counts['updated']} updated, {$counts['skipped']} skipped");

        return $counts;
    }

    /**
     * @return array{0: string[], 1: array<int, array{0:int, 1:array<string,string>}>}
     */
    private function read(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            throw new InvalidArgumentException('The file is empty.');
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            // Excel on Windows often saves CSV as Windows-1252.
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // byte-order mark

        $lines     = preg_split('/\r\n|\n|\r/', $content);
        $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $header = fgetcsv($handle, null, $delimiter, '"', '');
        $header = array_map(fn ($h) => Str::snake(trim(strtolower((string) $h))), $header ?: []);

        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;
            if ($values === [null] || count(array_filter($values, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // blank line
            }
            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                throw new InvalidArgumentException('The file has more than ' . self::MAX_ROWS . ' rows. Split it into smaller files.');
            }

            $values = array_map(fn ($v) => trim((string) $v), array_pad(array_slice($values, 0, count($header)), count($header), ''));
            $rows[] = [$line, array_combine($header, $values)];
        }
        fclose($handle);

        if (!$rows) {
            throw new InvalidArgumentException('The file has a header row but no data rows.');
        }

        return [$header, $rows];
    }

    private function toCsvLine(array $values): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $values, ',', '"', '');
        rewind($handle);
        $line = stream_get_contents($handle);
        fclose($handle);

        return $line;
    }

    private function cacheKey(string $token): string
    {
        return "csv-import:{$token}";
    }
}
