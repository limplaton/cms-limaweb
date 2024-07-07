<?php
 

namespace Modules\Deals\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Rules\StringRule;
use Modules\Deals\App\Enums\DealStatus;
use Modules\Deals\App\Http\Resources\DealResource;
use Modules\Deals\App\Models\Deal;

class DealStatusController extends ApiController
{
    /**
     * Change the deal status.
     *
     * @deprecated Use regular deal update with "status" attribute.
     */
    public function handle(Deal $deal, $status, Request $request): JsonResponse
    {
        $this->authorize('update', $deal);

        $status = DealStatus::find($status);

        // User must unmark the deal as open when the deal status is won or lost in order to change any further statuses
        abort_if(
            $deal->isStatusLocked($status),
            409,
            'The deal first must be marked as open in order to apply the '.$status->name.' status.'
        );

        $request->validate([
            'lost_reason' => [
                settings('lost_reason_is_required') ? 'required' : 'nullable',
                StringRule::make(),
            ],
        ]);

        $deal->fillStatus($status, $request->lost_reason)->save();

        return $this->response(
            new DealResource(
                $deal->resource()->displayQuery()->find($deal->id)
            )
        );
    }
}
