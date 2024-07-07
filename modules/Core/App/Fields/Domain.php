<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Http\Requests\ResourceRequest;

class Domain extends Field
{
    /**
     * Field component.
     */
    public static $component = 'domain-field';

    /**
     * Initialize new Domain instance.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());

        $this->provideSampleValueUsing(fn () => 'example.com');
    }

    /**
     * Get the field value for the given request
     */
    public function attributeFromRequest(ResourceRequest $request, string $requestAttribute): mixed
    {
        $value = parent::attributeFromRequest($request, $requestAttribute);

        return \Modules\Core\App\Support\Domain::extractFromUrl($value ?? '');
    }
}
