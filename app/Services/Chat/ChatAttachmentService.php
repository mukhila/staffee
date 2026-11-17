<?php

namespace App\Services\Chat;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chat attachments live on the private disk and are only served through an
 * authorised route, never as public files.
 */
class ChatAttachmentService
{
    public const DISK = 'local';

    /** Accepted file types. No HTML, SVG or scripts: they could run in a colleague's browser. */
    public const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'txt', 'csv',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip',
    ];

    /** Only these are shown inline; everything else downloads. */
    private const INLINE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public const MAX_KB = 10240;

    public static function validationRule(): string
    {
        return 'nullable|file|max:' . self::MAX_KB . '|mimes:' . implode(',', self::ALLOWED_EXTENSIONS);
    }

    /** @return array{path: string, name: string, type: string} */
    public function store(UploadedFile $file, string $folder): array
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());

        $path = $file->storeAs(
            "chat/{$folder}/" . now()->format('Y/m'),
            Str::uuid() . '.' . $extension,
            self::DISK
        );

        return [
            'path' => $path,
            'name' => Str::limit($this->cleanName($file->getClientOriginalName()), 200, ''),
            'type' => $file->getMimeType() ?: 'application/octet-stream',
        ];
    }

    /** Send the file to an authorised viewer. */
    public function respond(string $path, ?string $name, ?string $type): Response
    {
        // Older attachments were saved on the public disk before this change.
        $disk = Storage::disk(self::DISK)->exists($path) ? self::DISK : (Storage::disk('public')->exists($path) ? 'public' : null);
        abort_if($disk === null, 404, 'This attachment is no longer available.');

        $name ??= basename($path);
        $type = in_array($type, self::INLINE_TYPES, true) ? $type : null;

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control'          => 'private, max-age=3600',
        ];

        if ($type) {
            return Storage::disk($disk)->response($path, $name, $headers + ['Content-Type' => $type], 'inline');
        }

        return Storage::disk($disk)->download($path, $name, $headers);
    }

    private function cleanName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '', $name);

        return trim($name) !== '' ? trim($name) : 'attachment';
    }
}
