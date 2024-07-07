<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resourceable;
use Modules\Core\App\Workflow\HasWorkflowTriggers;

class Calendar extends Model
{
    use HasFactory, HasWorkflowTriggers, Resourceable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'user_id'];

    protected $table = 'event_calendars';

    public function events()
    {
        return $this->morphToMany(Event::class, 'eventable', 'eventables');
    }
}
