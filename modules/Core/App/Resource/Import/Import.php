<?php
 

namespace Modules\Core\App\Resource\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Row;
use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\Notifications;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Http\Requests\ImportRequest;
use Modules\Core\App\Models\Changelog;
use Modules\Core\App\Models\Import as ImportModel;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Workflow\Action as WorkflowAction;
use Modules\Users\App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class Import extends DefaultValueBinder implements OnEachRow, SkipsEmptyRows, WithCustomValueBinder, WithEvents, WithHeadingRow, WithLimit, WithStartRow
{
    use RegistersEventListeners,
        ValidatesImport;

    /**
     * Indicates if the import is running.
     */
    public static bool $running = false;

    /**
     * Count of imported records for the current import.
     */
    protected static int $imported = 0;

    /**
     * Count of skipped records for the current import.
     */
    protected static int $skipped = 0;

    /**
     * Count of duplicate records for the current import.
     */
    protected static int $duplicates = 0;

    /**
     * The number of rows per batch.
     */
    protected static int $defaultLimit = 501;

    /**
     * The maximum rows per import limit.
     */
    protected static int $maxRows;

    /**
     * Instance of the import model.
     */
    protected static ?ImportModel $import = null;

    /**
     * Duplicates lookup callback.
     *
     * @var callable|null
     */
    protected $lookupForDuplicatesUsing;

    /**
     * Perform callback on after save.
     *
     * @var callable|null
     */
    protected $afterSaveCalback;

    /**
     * Cached mappings.
     */
    protected ?Collection $mappings = null;

    /**
     * @var Failure[]
     */
    protected array $failures = [];

    protected static int $lastRowIndex = 0;

    /**
     * Columns with fields mappings.
     */
    protected array $columnsMappings = [];

    protected static array $changelogs = [];

    protected ImportRequest $sampleImportRequest;

    /**
     * Create new Import instance.
     */
    public function __construct(protected Resource $resource)
    {
        static::$maxRows = (int) config('core.import.max_rows');
        $this->sampleImportRequest = app(ImportRequest::class);
    }

    /**
     * Start the import process.
     */
    public function perform(ImportModel $import): void
    {
        if ($import->isFinished() && ! $import->isUsingSkipFile()) {
            return;
        }

        // Before the first batch of the skipped file, we will reset the total
        // skipped record from the original import so they are updated correctly
        // from the new fixed uploaded skipped file.
        if ($import->isUsingSkipFile() && $import->totalBatchesProcessed() === 0) {
            $import->fill(['skipped' => 0])->save();
        }

        static::$import = $import;
        static::$imported = $import->imported ?: 0;
        static::$duplicates = $import->duplicates ?: 0;
        static::$skipped = $import->skipped ?: 0;

        Excel::import($this, $import->file_path, $import::disk(), \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Create skip file.
     */
    public function createSkipFile(): string
    {
        $generator = new SkipFileGenerator(
            static::$import,
            static::$import->data['failures'],
            $this->mappings()
        );

        return $generator->store();
    }

    /**
     * Add callback for duplicates validation.
     */
    public function lookupForDuplicatesUsing(callable $callback): static
    {
        $this->lookupForDuplicatesUsing = $callback;

        return $this;
    }

    /**
     * Handle the import and validation.
     *
     * @return void
     */
    public function onRow(Row $row)
    {
        // Because we are reading the spreadsheet with limit, the headings row is not always available.
        // In this case, in the first onRow call, we will store the headings
        if ($row->getIndex() === 1) {
            return $this->rememberHeadings($row);
        }

        static::$lastRowIndex = $row->getIndex();

        // The model cache will flush the cache each time the data is updated
        // if using the file driver, as most customers will user, since they are installing
        // on shared hosting, in this case, we do not need the IQ to flush the cache when
        // there is possibility to update hundreds of records.
        app('model-cache')->runDisabled(function () use ($row) {
            $this->performRowImport($row);
        });
    }

    /**
     * Perform import for the given row.
     */
    protected function performRowImport(Row $row)
    {
        $request = $this->createRequest($row);

        Field::setRequest($request);

        $validator = $this->prepareRequestForValidation($request);

        try {
            $this->validate($validator, $request);

            $this->save($request);
        } catch (RowSkippedException) {
        } finally {
            Field::setRequest(null);
        }
    }

    /**
     * Create request for the given import row.
     */
    protected function createRequest(Row $row): ImportRequest
    {
        $data = $this->mapRow($row);

        return (clone $this->sampleImportRequest)
            ->setResource($this->resource->name())
            ->setFields($this->getFields())
            ->replace($data)
            ->setOriginal($data)
            ->setRowNumber($row->getIndex());
    }

    /**
     * Prepare the request for validation and get the validator instance.
     */
    protected function prepareRequestForValidation(ImportRequest $request)
    {
        $validator = $this->createValidator($request);

        // After the validation callbacks are executed, we need to re-set the data
        // with the new (possibly) modified data from the validation callbacks.
        $validator->setData($request->runValidationCallbacks($validator)->all());

        // We will perform a search for duplicate record after the validation callbacks
        // so all the values are properly formatted for the validator.
        // If a record is found, we will set the record in the request instance
        // and can be used in the "save" method to determine whether to perform update or create.
        if ($record = $this->searchForDuplicateRecord($request)) {
            $request->setResourceId($record->getKey())->setRecord($record);
        }

        return $validator;
    }

    /**
     * Get the fields for the import.
     */
    protected function getFields(): FieldsCollection
    {
        return $this->resource->fieldsForImport();
    }

    /**
     * Get all of the mappings intended for the current import.
     */
    protected function mappings(): Collection
    {
        return $this->mappings ??= collect(static::$import->data['mappings'])
            ->reject(function ($column) {
                return $column['skip'] || ! $column['attribute'];
            });
    }

    /**
     * Map the row keys with it's selected attributes.
     */
    protected function mapRow(Row $row): array
    {
        $data = array_combine(static::$import->data['headings'], $row->toArray());

        return $this->mappings()->reduce(function ($carry, $column) use ($data) {
            $carry[$column['attribute']] = $data[$column['original']];

            return $carry;
        }, []);
    }

    /**
     * Handle the model save for the given request.
     */
    protected function save(ImportRequest $request): void
    {
        // If the request resource ID is set, means that a duplicate
        // record was found, in this case, we are free to perform an update.
        if ($request->resourceId()) {
            $model = $request->record();

            if ($model?->trashed()) {
                $model->restore();
            }

            $this->performUpdate($model, $request);
        } else {
            // spatie activity log is adding additional 2 queries on model create
            // we will disable the activity log during import for creation to avoid those queries
            // and will insert custom changelog via batch.
            ChangeLogger::disabled(function () use ($request) {
                $model = $this->performCreate($request);

                if ($model->logsModelChanges()) {
                    $this->addImportedChangelog($model, $request);
                }
            });
        }
    }

    /**
     * Create new record for the resource.
     */
    protected function performCreate(ImportRequest $request): Model
    {
        /** @var \Modules\Core\App\Models\Model */
        $model = $request->resource()->newModel()->forceFill([
            'import_id' => static::$import->getKey(),
        ]);

        $model::withoutTouching(function () use ($request, &$model) {
            $model = $request->resource()->create(
                $request->hydrateModel($model),
                $request->asCreateRequest(),
            );

            static::$imported++;
        });

        return $model;
    }

    /**
     * Update record for the resource.
     */
    protected function performUpdate(Model $model, ImportRequest $request): Model
    {
        $model::withoutTouching(function () use ($request, &$model) {
            $model = $request->resource()->update(
                $request->hydrateModel($model),
                $request->asUpdateRequest($model),
            );

            static::$duplicates++;

            if (static::$import->isUsingSkipFile() && static::$skipped > 0) {
                static::$skipped--;
            }
        });

        return $model;
    }

    /**
     * Try to find duplicate record from the request.
     */
    protected function searchForDuplicateRecord(ImportRequest $request): ?Model
    {
        // First, we need to check duplicates based on any unique custom fields
        // because the fields consist of a unique index which does not allow duplicates inserting
        // in this case, we must make sure to update them instead of try to create the record
        if ($record = $request->findRecordFromUniqueCustomFields(true)) {
            return $record;
        }

        if (is_callable($this->lookupForDuplicatesUsing)) {
            return call_user_func($this->lookupForDuplicatesUsing, $request);
        }

        return null;
    }

    /**
     * Check whether any import is in progress.
     */
    public static function isImportInProgress(): bool
    {
        return ImportModel::inProgress()->count() > 0;
    }

    /**
     * Before import event handler.
     */
    public static function beforeImport(BeforeImport $event)
    {
        $total = $event->getReader()->getTotalRows()['Worksheet'];

        // Subtract the heading row
        if (($total - 1) > static::$maxRows) {
            throw new RowsExceededException(static::$maxRows);
        }

        static::$import
            ->fill(['status' => 'in-progress'])
            ->setTotalBatches(
                (int) ceil($total / static::$import->data['limit'])
            )->save();

        // Disable the query log to reduce memory usage.
        if (app()->isProduction()) {
            DB::disableQueryLog();
        }

        static::$running = true;

        WorkflowAction::disableExecutions();
        Innoclapps::muteAllCommunicationChannels();
    }

    /**
     * After import event handler.
     */
    public static function afterImport(AfterImport $event)
    {
        if (app()->isProduction()) {
            DB::enableQueryLog();
        }

        if (count(static::$changelogs)) {
            static::insertChangelogs();
        }

        static::$running = false;

        static::$import->incrementProcessedBatches();

        $attributes = [
            'status' => 'in-progress', 'imported' => static::$imported,
            'skipped' => static::$skipped, 'duplicates' => static::$duplicates,
        ];

        $data = static::$import->data;

        if (static::$import->totalBatchesProcessed() >= static::$import->totalBatches()) {
            $attributes['status'] = 'finished';

            if (! static::$import->isUsingSkipFile()) {
                $attributes['completed_at'] = now();
            }

            unset($data['next_batch_start_row'], $data['next_batch']);
        } else {
            $data['next_batch'] = static::$import->totalBatchesProcessed() + 1;
            $data['next_batch_start_row'] = static::$lastRowIndex + 1;
        }

        $data['failures'] = array_merge(
            $data['failures'] ?? [],
            $event->getConcernable()->failures()->toArray()
        );

        // Finished importing (valid for both main and skip file)
        if (! isset($data['next_batch'])) {
            if (static::$import->isUsingSkipFile()) {
                static::$import->removeSkipFile();
                $attributes['skip_file_path'] = null;
            }

            if (count($data['failures']) > 0) {
                $attributes['skip_file_path'] = $event->getConcernable()->createSkipFile();
                $data['failures'] = [];
            }
        }

        static::$import->fill(array_merge($attributes, ['data' => $data]))->save();

        WorkflowAction::disableExecutions(false);
        Notifications::enable();
    }

    /**
     * Import failed event handler.
     */
    public static function importFailed(ImportFailed $event)
    {
        // Because we are not using transactions, there may be changelogs.
        if (count(static::$changelogs)) {
            static::insertChangelogs();
        }

        static::$import->fill(['status' => 'mapping'])->save();
    }

    /**
     * Value binder handler.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function bindValue(Cell $cell, $value)
    {
        $column = $cell->getColumn();
        $rowIdx = $cell->getRow();

        // The first row is always the headings, no special values are needed here, in this case
        // we will use the first row to map the columns with the fields e.q. A => Field
        if ($rowIdx === 1) {
            if ($mapping = $this->mappings()->where('original', $value)->first()) {
                $this->columnsMappings[$column] = $this->getFields()->find(
                    $mapping['attribute']
                );
            }

            // Let's make the headings to be always string without any formatting
            // So they are consistent over the application
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        } elseif ($rowIdx > 1) {
            // In this stage it's safe to assume that when the row is > 1, here are the actual values
            // will check if any field has defined custom import value data type and will bind it to the cell
            $field = $this->columnsMappings[$column] ?? null;

            if ($field && $dataType = $field->importValueDataType()) {
                $cell->setValueExplicit($value, $dataType);

                return true;
            }
        }

        // default behavior
        return parent::bindValue($cell, $value);
    }

    /**
     * Batch logs the added changes during import.
     */
    protected static function insertChangelogs(): void
    {
        $columns = [
            'log_name', 'identifier', 'subject_type', 'subject_id',
            'causer_type', 'causer_id', 'causer_name', 'description',
            'created_at',
        ];

        batch()->insert(new Changelog, $columns, static::$changelogs);

        static::$changelogs = [];
    }

    /**
     * Add the imported changelog to the changelogs list.
     */
    protected function addImportedChangelog(Model $model, ImportRequest $request): void
    {
        static::$changelogs[] = [
            'model', // log_name
            'imported', // identifier
            get_class($model), // subject_type
            $model->getKey(), // subject_id
            get_class($request->user()), // causer_type
            $request->user()->getKey(), // causer_id
            $request->user()->name, // causer_name
            '', // description
            now()->subSeconds(1), // created_at
        ];
    }

    /**
     * Remember the headings.
     */
    protected function rememberHeadings(Row $headingsRow): void
    {
        static::$import->data['headings'] = array_keys($headingsRow->toArray());
        static::$import->save();
    }

    /**
     * Get the limit of rows to import.
     */
    public static function setDefaultLimit(int $limit): void
    {
        static::$defaultLimit = $limit;
    }

    /**
     * Get the limit of rows to import.
     */
    public static function getDefaultLimit(): int
    {
        return static::$defaultLimit;
    }

    /**
     * Get the limit for imported rows.
     */
    public function limit(): int
    {
        return static::$import->data['limit'];
    }

    /**
     * Get the starting row.
     */
    public function startRow(): int
    {
        if (array_key_exists('next_batch_start_row', static::$import->data->toArray())) {
            return (int) static::$import->data['next_batch_start_row'];
        }

        return 1;
    }

    /**
     * Initiate new import from the given file and start mapping the fields.
     */
    public function upload(UploadedFile $file, User $user): ImportModel
    {
        $model = new ImportModel;

        $path = $model->storeFile($file);

        return tap($model->fill([
            'file_path' => $path,
            'resource_name' => $this->resource->name(),
            'user_id' => $user->getKey(),
            'status' => 'mapping',
            'imported' => 0,
            'duplicates' => 0,
            'skipped' => 0,
            'data' => [
                'limit' => static::$defaultLimit,
                'mappings' => $this->createMappings($path),
                'failures' => [],
                'total_batches' => 0,
                'total_batches_processed' => 0,
                'total_batches_via_skip_file' => 0,
                'total_batches_processed_via_skip_file' => 0,
            ],
        ]))->save();
    }

    /**
     * Upload new fixed skip file.
     */
    public function uploadViaSkipFile(UploadedFile $file, ImportModel $model): ImportModel
    {
        $path = $model->storeFile($file);

        $model->data['mappings'] = $this->createMappings($path);

        $model->data['total_batches_via_skip_file'] = 0;
        $model->data['total_batches_processed_via_skip_file'] = 0;
        $model->data['limit'] = static::$defaultLimit;

        $model->fill(['file_path' => $path, 'status' => 'mapping'])->save();

        return $model;
    }

    /**
     * Create mappings for the given path.
     */
    protected function createMappings(string $path): array
    {
        return (new HeadingsMapper($path, $this->getFields(), ImportModel::disk()))->map();
    }
}
