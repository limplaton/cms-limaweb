<?php
 

namespace Modules\Users\Tests\Concerns;

trait TestsMentions
{
    protected function mentionText($id, $name, $notified = 'false')
    {
        return '<span class="mention" data-mention-id="'.$id.'" data-notified="'.$notified.'">@'.$name.'</span>';
    }
}
