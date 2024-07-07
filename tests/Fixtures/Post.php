<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Models\Model;
use Tests\Factories\PostFactory;

class Post extends Model
{
    use HasFactory, HasMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['title', 'body'];

    public function textAttributesWithMedia(): array
    {
        return ['body'];
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
