<?php
 

namespace Modules\Core\App\Resource\Import;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class Failure implements Arrayable, JsonSerializable
{
    /**
     * Create new Failure instance.
     */
    public function __construct(protected int $row, protected string $attribute, protected array $errors, protected array $values = [])
    {
    }

    public function row(): int
    {
        return $this->row;
    }

    public function attribute(): string
    {
        return $this->attribute;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function values(): array
    {
        return $this->values;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return collect($this->errors)->map(function ($message) {
            return __('There was an error on row :row. :message', ['row' => $this->row, 'message' => $message]);
        })->all();
    }

    public function jsonSerialize(): array
    {
        return [
            'row' => $this->row(),
            'attribute' => $this->attribute(),
            'errors' => $this->errors(),
            'values' => $this->values(),
        ];
    }
}
