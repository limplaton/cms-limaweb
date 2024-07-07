<?php
 

namespace Modules\Deals\App\Resources;

use Maatwebsite\Excel\Row;
use Modules\Core\App\Resource\Import\Import;

class DealImport extends Import
{
    /**
     * Map the row keys with it's selected attributes.
     */
    protected function mapRow(Row $row): array
    {
        $pipelineId = request()->integer('pipeline_id');

        if ($pipelineId === 0) {
            throw new \LogicException('Pipeline ID must be provided.');
        }

        return array_merge(parent::mapRow($row), ['pipeline_id' => $pipelineId]);
    }
}
