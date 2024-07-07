<?php
 

namespace Modules\Core\App\Resource\Import;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Core\App\Models\Import;

class SkipFileGenerator implements FromArray
{
    /**
     * Skip reason heading name
     */
    const SKIP_REASON_HEADING = '--Skip Reason--';

    /**
     * Initialize new SkipFileGenerator instance.
     */
    public function __construct(protected Import $import, protected array $failures, protected Collection $mappings)
    {
    }

    /**
     * Creates the import skip file.
     */
    public function array(): array
    {
        return [
            $this->headings(),
            ...$this->rows(),
        ];
    }

    /**
     * Store the skip file in storage.
     */
    public function store(): string
    {
        Excel::store($this, $path = $this->diskPath(), $this->import::disk());

        return $path;
    }

    /**
     * Get the skip file filename.
     */
    public function filename(): string
    {
        $filename = basename($this->import->file_path);

        if (! str_starts_with($filename, 'skip-file-')) {
            $filename = 'skip-file-'.$filename;
        }

        return $filename;
    }

    /**
     * Group all of the validation errors grouped per row.
     */
    protected function errors(): array
    {
        $grouped = [];

        foreach ($this->failures as $failure) {
            $grouped[$failure['row']] = array_unique(array_merge(
                $grouped[$failure['row']] ?? [],
                $failure['errors']
            ));
        }

        return $grouped;
    }

    /**
     * Get the skip file path on the disk.
     */
    protected function diskPath(): string
    {
        return $this->import->storagePath($this->filename());
    }

    /**
     * Get the skip file headings.
     */
    public function headings(): array
    {
        return $this->mappings->pluck('original')
            ->forget(static::SKIP_REASON_HEADING)
            ->prepend(static::SKIP_REASON_HEADING)
            ->all();
    }

    /**
     * Get the skip file rows.
     */
    public function rows(): array
    {
        $errors = $this->errors();

        return collect($this->failures)
            ->unique(fn (array $failure) => $failure['row'])
            ->map(fn ($failure) => [
                implode(PHP_EOL, $errors[$failure['row']]),
                ...array_values($failure['values']),
            ])->all();
    }
}
