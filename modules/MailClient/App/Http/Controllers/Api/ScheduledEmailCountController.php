<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\MailClient\App\Criteria\EmailAccountsForUserCriteria;
use Modules\MailClient\App\Models\ScheduledEmail;

class ScheduledEmailCountController extends ApiController
{
    /**
     * Count the total of the scheduled emails.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $result = ScheduledEmail::query()
            ->where(function (Builder $query) {
                $query->where('status', 'pending')->orWhere(function (Builder $query) {
                    $query->retryable();
                });
            })
            ->when($request->has(['via_resource', 'via_resource_id']), function (Builder $query) use ($request) {
                $query->ofResource($request->via_resource, $request->integer('via_resource_id'));
            })
            ->withWhereHas(
                'account', fn ($query) => $query->criteria(EmailAccountsForUserCriteria::class)
            )->count();

        return $this->response(['count' => $result]);
    }
}
