<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Comments\App\Models\Comment;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resourceable;
use Modules\Users\App\Models\User;

class Event extends Model implements Presentable
{
    use HasFactory, HasMedia, Resourceable;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['title', 'description', 'start', 'end', 'date', 'total_guests', 'is_all_day', 'user_id', 'status'];

    public function displayName(): string
    {
        return $this->title;
    }

    public function path(): string
    {
        return "/events/{$this->id}";
    }

    public function status()
    {
        return $this->belongsTo(EventStatus::class, 'status_id');
    }

    public function locations()
    {
        return $this->morphMany(Location::class, 'locationable')->orderBy('locations.created_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function calendars()
    {
        return $this->morphedByMany(Calendar::class, 'eventable', 'eventables');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at');
    }
}
