<?php
 

namespace Modules\Contacts\Database\State;

use Illuminate\Support\Facades\DB;
use Modules\Core\App\Models\Tag;

class EnsureDefaultContactTagsArePresent
{
    public array $tags = [
        'Customer' => '#10b981',
        'Hot Lead' => '#DC2626',
        'Cold Lead' => '#2563eb',
        'Warm Lead' => '#f59e0b',
    ];

    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        foreach ($this->tags as $tag => $color) {
            $tag = Tag::findOrCreate($tag, 'contacts');

            $tag->swatch_color = $color;

            $tag->save();
        }
    }

    private function present(): bool
    {
        return DB::table('tags')->where('type', 'contacts')->count() > 0;
    }
}
