<?php
 

namespace Modules\Comments\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Comments\App\Models\Comment;
use Tests\TestCase;

class CommentModelTest extends TestCase
{
    public function test_comment_has_commentables()
    {
        $comment = new Comment;

        $this->assertInstanceOf(MorphTo::class, $comment->commentable());
    }
}
