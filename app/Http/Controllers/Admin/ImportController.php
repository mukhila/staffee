<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Import\CsvImportService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ImportController extends Controller
{
    public function __construct(private readonly CsvImportService $imports) {}

    public function index()
    {
        return view('admin.imports.index', ['types' => $this->imports->types()]);
    }

    public function show(string $type)
    {
        return view('admin.imports.show', [
            'type'    => $this->resolve($type),
            'maxRows' => CsvImportService::MAX_ROWS,
        ]);
    }

    public function template(string $type)
    {
        $importType = $this->resolve($type);

        return response($this->imports->template($importType), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"staffee-{$importType->key()}-template.csv\"",
        ]);
    }

    public function preview(Request $request, string $type)
    {
        $importType = $this->resolve($type);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'file.required' => 'Choose a CSV file to upload.',
            'file.mimes'    => 'The file must be a CSV. In Excel use File → Save As → CSV.',
            'file.max'      => 'The file must be 2 MB or smaller.',
        ]);

        try {
            $result = $this->imports->preview($importType, $request->file('file')->getRealPath(), $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('admin.imports.preview', [
            'type'     => $importType,
            'result'   => $result,
            'filename' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    public function confirm(Request $request, string $type)
    {
        $importType = $this->resolve($type);
        $data       = $request->validate(['token' => 'required|uuid']);

        try {
            $counts = $this->imports->confirm($importType, $data['token'], $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.imports.show', $importType->key())->with('error', $e->getMessage());
        }

        $parts = array_filter([
            $counts['created'] ? "{$counts['created']} added" : null,
            $counts['updated'] ? "{$counts['updated']} updated" : null,
            $counts['skipped'] ? "{$counts['skipped']} skipped because they already existed" : null,
        ]);

        return redirect()->route('admin.imports.show', $importType->key())
            ->with('success', "{$importType->label()} imported: " . (implode(', ', $parts) ?: 'nothing to change') . '.');
    }

    private function resolve(string $type)
    {
        try {
            return $this->imports->type($type);
        } catch (InvalidArgumentException) {
            abort(404);
        }
    }
}
