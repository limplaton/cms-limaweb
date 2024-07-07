<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ActionRequest;

class ActionController extends ApiController
{
    /**
     * Run resource action.
     */
    public function handle($action, ActionRequest $request): mixed
    {
        $request->performValidation();

        return $request->run();
    }
}
