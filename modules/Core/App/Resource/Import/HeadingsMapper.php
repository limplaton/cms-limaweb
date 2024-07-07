<?php
 

namespace Modules\Core\App\Resource\Import;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Core\App\Contracts\Fields\Dateable;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;

class HeadingsMapper implements ToCollection, WithHeadingRow, WithLimit
{
    protected ?Collection $collection = null;

    protected array $mappedFields = [];

    /**
     * Create new HeadingsMapper instance.
     */
    public function __construct(string $filePath, protected FieldsCollection $fields, string $disk)
    {
        Excel::import($this, $filePath, $disk, \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Map the actual heading with the fields
     */
    public function map(): array
    {
        // We will get the first row key (the headings) to have something
        // to work with and from the headings will detect the appropriate fields
        return $this->collection->first()
            ->keys()
            ->reject(fn ($heading) => is_numeric($heading) || $heading === SkipFileGenerator::SKIP_REASON_HEADING)
            ->values()
            ->map(fn ($originalHeadingKey, $index) => $this->mapHeading($originalHeadingKey, $index))
            ->all();
    }

    /**
     * Map the given heading
     */
    protected function mapHeading(string $originalHeadingKey, string $index): array
    {
        $field = $this->detectFieldFromHeading($originalHeadingKey, $index);

        return [
            'original' => $originalHeadingKey,
            'detected_attribute' => $attribute = $field?->attribute,
            // User-selected attribute, default the detected one
            'attribute' => $attribute,
            'preview' => $this->previewRecords($originalHeadingKey)->implode(', '),
            'skip' => ! (bool) $attribute,
            'auto_detected' => (bool) $attribute,
        ];
    }

    /**
     * Get records for preview
     */
    public function previewRecords(string $heading): Collection
    {
        return $this->collection
            ->reject(fn (Collection $row) => empty($row[$heading]))
            ->take(3)
            ->map(fn (Collection $row) => $row[$heading]);
    }

    /**
     * Detect field from the given heading
     */
    protected function detectFieldFromHeading(string $heading, string $index): ?Field
    {
        $field = $this->fields->first(function ($field, $fIndex) use ($heading, $index) {
            return ! array_key_exists($field->attribute, $this->mappedFields) &&
            (
                Str::lower($field->label) === Str::lower($heading) ||
                $field->attribute === $heading ||
                // Same index order, slightly changes in heading
                $fIndex === $index && Str::contains($heading, $field->label) ||
                // E.q. recognize "Field Label (UTC)" heading to "Field Label" or "field_attribute"
                $field instanceof Dateable && Str::endsWith($heading, '('.config('app.timezone').')') && Str::contains($heading, [$field->label, $field->attribute])
            );
        });

        // We will store all fields that are already mapped to a heading to prevent
        // mapping the same field to another heading that contains the field label
        // e.q. if we have 2 fields "field name" and "another field name"
        // The "another field name" will be mapped with the "field name" heading/field
        // as it contains the actual name in heading
        if ($field) {
            $this->mappedFields[$field->attribute] = true;
        }

        return $field;
    }

    /**
     * Set the collection from the Excel
     */
    public function collection(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Provide the max number of rows to read to prepare the mapper
     */
    public function limit(): int
    {
        return 100;
    }
}
