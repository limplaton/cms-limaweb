<?php
 

namespace Modules\Users\App\Services;

use Modules\Core\App\Models\Filter;
use Modules\Core\App\Models\Workflow;
use Modules\Users\App\Events\TransferringUserData;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;

class TransferUserDataService
{
    protected int $fromUserId;

    /**
     * Create new TransferUserData instance.
     */
    public function __construct(protected int $toUserId, protected User $fromUser)
    {
        $this->fromUserId = $fromUser->getKey();
    }

    /**
     * Invoke the transfer
     */
    public function __invoke(): void
    {
        TransferringUserData::dispatch($this->toUserId, $this->fromUserId, $this->fromUser);

        $this->transferSharedFilters();
        $this->transferTeams();
        $this->transferWorkflows();
    }

    /**
     * Transfer shared filter.
     */
    public function transferSharedFilters(): void
    {
        Filter::where('user_id', $this->fromUserId)->shared()->update(['user_id' => $this->toUserId]);
    }

    /**
     * Transfer created workflows.
     */
    public function transferWorkflows(): void
    {
        Workflow::where('created_by', $this->fromUserId)->update(['created_by' => $this->toUserId]);
    }

    /**
     * Transfer teams.
     */
    public function transferTeams(): void
    {
        Team::where('user_id', $this->fromUserId)->update(['user_id' => $this->toUserId]);
    }
}
