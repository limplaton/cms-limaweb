<?php
 

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Http\Requests\ImportRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\Database\Factories\ImportFactory;

class Import extends Model
{
    use HasFactory;

    const STATUSES = [
        'mapping' => 1,
        'in-progress' => 2,
        'finished' => 3,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_path',
        'skip_file_path',
        'resource_name',
        'status',
        'imported',
        'skipped',
        'duplicates',
        'user_id',
        'completed_at',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => AsArrayObject::class,
        'user_id' => 'int',
        'duplicates' => 'int',
        'skipped' => 'int',
        'imported' => 'int',
        'completed_at' => 'datetime',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::deleted(function (Import $model) {
            Storage::disk(static::disk())->deleteDirectory($model->storagePath());
        });
    }

    /**
     * Scope a query to only include imports of a given resource.
     */
    public function scopeByResource(Builder $query, string $resourceName): void
    {
        $query->where('resource_name', $resourceName);
    }

    /**
     * Scope a query to only include imports with status in progress.
     */
    public function scopeInProgress(Builder $query): void
    {
        $query->where('status', 'in-progress');
    }

    /**
     * Remove import file from storage
     */
    public function removeFile(string $path): bool
    {
        $disk = Storage::disk(static::disk());

        if ($disk->exists($path)) {
            return $disk->delete($path);
        }

        return false;
    }

    /**
     * Store the import file in storage.
     *
     * @return string|false
     */
    public function storeFile(UploadedFile $file): string|bool
    {
        if ($this->file_path) {
            $this->removeFile($this->file_path);
        }

        return $file->storeAs(
            $this->storagePath(),
            $file->getClientOriginalName(),
            static::disk()
        );
    }

    /**
     * Check if the import has finished.
     */
    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    /**
     * Check whether the import "imported" records can be reverted.
     */
    public function isRevertable(): bool
    {
        if (! $this->completed_at || ! $this->isFinished()) {
            return false;
        }

        if (! $this->completed_at->gt(now()->subHours(config('core.import.revertable_hours')))) {
            return false;
        }

        return $this->resource()
            ->newQuery()
            ->where('import_id', $this->getKey())
            ->count() > 0;
    }

    /**
     * Get the import related resource instance.
     */
    public function resource(): Resource
    {
        return Innoclapps::resourceByName($this->resource_name);
    }

    /**
     * Get the import files storage path
     *
     * Should be used once the model has been created and  the file is uploaded as it's
     * using the folder from the initial upload files, all other files will be stored there as well
     */
    public function storagePath(string $glue = ''): string
    {
        $path = $this->id ?
            pathinfo($this->file_path, PATHINFO_DIRNAME) :
            'imports'.DIRECTORY_SEPARATOR.Str::random(15);

        return $path.($glue ? (DIRECTORY_SEPARATOR.$glue) : '');
    }

    /**
     * An Import has user/creator
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Get the fields intended for this import
     */
    public function fields(): FieldsCollection
    {
        return Innoclapps::resourceByName(
            $this->resource_name
        )->fieldsForImport();
    }

    /**
     * Serialize the import fields for the front-end.
     */
    public function serializeFields()
    {
        Field::setRequest(app(ImportRequest::class));

        return tap($this->fields()->map(fn (Field $field) => $field->jsonSerialize()), function () {
            Field::setRequest(null);
        });
    }

    /**
     * Get the import progress.
     */
    public function progress(): int
    {
        // Pre batching implementation.
        if (! isset($this->data['total_batches'])) {
            return 100;
        }

        // Progress is calculated only from the initial uploaded file
        // uploading skip files does not affect the progress.
        return $this->calculateProgress(
            $this->data['total_batches_processed'],
            $this->data['total_batches'],
        );
    }

    /**
     * Check if the import is using skip file.
     */
    public function isUsingSkipFile(): bool
    {
        return (bool) $this->skip_file_path;
    }

    /**
     * Get the total batches for the import.
     */
    public function totalBatches(): int
    {
        $key = ! $this->isUsingSkipFile() ? 'total_batches' : 'total_batches_via_skip_file';

        return (int) $this->data[$key];
    }

    /**
     * Set the total batches for the import.
     */
    public function setTotalBatches(int $value): static
    {
        $key = ! $this->isUsingSkipFile() ? 'total_batches' : 'total_batches_via_skip_file';

        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get the total batches processed for the import.
     */
    public function totalBatchesProcessed(): int
    {
        $key = ! $this->isUsingSkipFile() ? 'total_batches_processed' : 'total_batches_processed_via_skip_file';

        return (int) $this->data[$key];
    }

    /**
     * Set the total batches processed for the import.
     */
    public function setTotalBatchesProcessed(int $value): static
    {
        $key = ! $this->isUsingSkipFile() ? 'total_batches_processed' : 'total_batches_processed_via_skip_file';

        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Increment the total batches processed for the import.
     */
    public function incrementProcessedBatches(): static
    {
        $this->setTotalBatchesProcessed(
            $this->totalBatchesProcessed() + 1
        );

        return $this;
    }

    /**
     * Get the next batch number.
     */
    public function nextBatch(): ?int
    {
        if (! array_key_exists('next_batch', $this->data->toArray())) {
            return null;
        }

        return (int) $this->data['next_batch'];
    }

    /**
     * Remove the import skip file.
     */
    public function removeSkipFile(): bool
    {
        return $this->removeFile($this->skip_file_path);
    }

    /**
     * Calculate the import progress.
     */
    public function calculateProgress(int $currentBatch, int $totalBatches)
    {
        if ($totalBatches == 0) { // To avoid division by zero
            return 0;
        }

        $progress = ($currentBatch / $totalBatches) * 100;

        return number_format($progress, 2); // Returns the result rounded to two decimal places
    }

    /**
     * Get the file name attribute
     */
    public function fileName(): Attribute
    {
        return Attribute::get(
            fn () => basename($this->file_path)
        );
    }

    /**
     * Get the skip file filename name attribute
     */
    public function skipFileFilename(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->skip_file_path) {
                return null;
            }

            return basename($this->skip_file_path);
        });
    }

    /**
     * Get the import storage disk
     */
    public static function disk(): string
    {
        return 'local';
    }

    /**
     * Get the import's status.
     */
    protected function status(): Attribute
    {
        return new Attribute(
            get: fn ($value) => array_search($value, static::STATUSES),
            set: fn ($value) => static::STATUSES[
                is_numeric($value) ? array_search($value, static::STATUSES) : $value
            ]
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ImportFactory
    {
        return ImportFactory::new();
    }
}
