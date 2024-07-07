<?php
 

namespace Modules\Comments\Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Modules\Activities\App\Models\Activity;
use Modules\Users\App\Notifications\UserMentioned;
use Modules\Users\Tests\Concerns\TestsMentions;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use TestsMentions;

    public function test_unauthenticated_user_cannot_access_the_comments_endpoints()
    {
        $this->getJson('/api/comments')->assertUnauthorized();
        $this->getJson('/api/comments/FAKE_ID')->assertUnauthorized();
        $this->postJson('/api/comments')->assertUnauthorized();
        $this->putJson('/api/comments/FAKE_ID')->assertUnauthorized();
        $this->deleteJson('/api/comments/FAKE_ID')->assertUnauthorized();
    }

    public function test_comment_can_be_added_to_resource()
    {
        $user = $this->signIn();
        $event = Activity::factory()->create();

        $this->postJson('/api/activities/'.$event->getKey().'/comments', [
            'body' => 'Comment goes here',
        ])->assertCreated()
            ->assertJson([
                'body' => 'Comment goes here',
                'created_by' => $user->getKey(),
            ])
            ->assertJsonStructure(['creator']);
    }

    public function test_comments_can_be_retrieved()
    {
        $this->signIn();
        $activity = Activity::factory()->create();
        $activity->comments()->create(['body' => 'Comment goes here']);

        $this->getJson("/api/activities/{$activity->id}/comments")->assertJsonCount(1);
    }

    public function test_comments_can_be_retrieved_for_record_that_the_user_is_authorized_to_see()
    {
        $user = $this->signIn();
        $activity = Activity::factory()->for($user)->create();
        $activity->comments()->create(['body' => 'Comment goes here']);

        $this->asRegularUser()->signIn();

        $this->getJson("/api/activities/{$activity->id}/comments")->assertForbidden();
    }

    public function test_comment_cannot_be_added_to_resources_the_user_is_not_authorized_to_see()
    {
        $this->asRegularUser()->signIn();
        $activity = Activity::factory()->create();

        $this->postJson("/api/activities/{$activity->id}/comments", [
            'body' => 'Comment goes here',
        ])->assertForbidden();
    }

    public function test_when_present_comment_requires_resource()
    {
        $this->signIn();
        $activity = Activity::factory()->create();

        $this->postJson("/api/activities/{$activity->id}/comments", [
            'body' => 'Comment goes here',
            'via_resource' => '',
            'via_resource_id' => '',
        ])->assertJsonValidationErrors(['via_resource', 'via_resource_id']);

        $comment = $activity->comments()->create(['body' => 'Comment goes here']);

        $this->putJson('/api/comments/'.$comment->id, [
            'body' => 'Comment goes here',
            'via_resource' => '',
            'via_resource_id' => '',
        ])->assertJsonValidationErrors(['via_resource', 'via_resource_id']);
    }

    public function test_comment_requires_body()
    {
        $this->signIn();
        $activity = Activity::factory()->create();

        $this->postJson("/api/activities/{$activity->id}/comments", [
            'body' => '',
        ])->assertJsonValidationErrors(['body']);

        $id = $this->postJson("/api/activities/{$activity->id}/comments", [
            'body' => 'Comment goes here',
        ])->getData()->id;

        $this->putJson('/api/comments/'.$id, [
            'body' => '',
        ])->assertJsonValidationErrors(['body']);
    }

    public function test_comment_can_be_retrieved()
    {
        $this->signIn();
        $activity = Activity::factory()->create();
        $comment = $activity->comments()->create(['body' => 'Comment goes here']);

        $this->getJson('/api/comments/'.$comment->id)->assertJson([
            'body' => 'Comment goes here',
        ]);
    }

    public function test_comment_can_be_retrieved_only_by_creator()
    {
        $this->signIn();
        $activity = Activity::factory()->create();
        $comment = $activity->comments()->create(['body' => 'Comment goes here']);

        $this->asRegularUser()->signIn();

        $this->getJson('/api/comments/'.$comment->id)->assertForbidden();
    }

    public function test_comment_can_be_updated()
    {
        $this->signIn();
        $activity = Activity::factory()->create();

        $comment = $activity->comments()->create(['body' => 'Comment goes here']);

        $this->putJson('/api/comments/'.$comment->id, [
            'body' => 'Changed Body',
        ])->assertJson([
            'body' => 'Changed Body',
        ]);
    }

    public function test_comment_can_be_updated_only_by_creator()
    {
        $this->signIn();
        $activity = Activity::factory()->create();
        $comment = $activity->comments()->create(['body' => 'Comment goes here']);

        $this->asRegularUser()->signIn();

        $this->putJson('/api/comments/'.$comment->id, [
            'body' => 'Changed Body',
        ])->assertForbidden();
    }

    public function test_comment_can_be_deleted()
    {
        $this->signIn();
        $activity = Activity::factory()->create();
        $comment = $activity->comments()->create(['body' => 'Comment goes here']);

        $this->deleteJson('/api/comments/'.$comment->id)->assertNoContent();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_comment_can_be_deleted_only_by_creator()
    {
        $this->signIn();
        $activity = Activity::factory()->create();
        $comment = $activity->comments()->create(['body' => 'Comment goes here']);

        $this->asRegularUser()->signIn();

        $this->deleteJson('/api/comments/'.$comment->id)->assertForbidden();
    }

    public function test_it_send_notifications_to_mentioned_users_when_comment_is_created()
    {
        $this->signIn();

        $user = $this->createUser();
        $activity = Activity::factory()->create();

        Notification::fake();

        $commentId = $this->postJson('/api/activities/'.$activity->getKey().'/comments', [
            'body' => 'Other Text - '.$this->mentionText($user->id, $user->name),
        ])->getData()->id;

        Notification::assertSentTo($user, UserMentioned::class, function (UserMentioned $notification) use ($activity, $commentId) {
            return $notification->mentionUrl === "/activities/{$activity->id}?comment_id={$commentId}";
        });
    }

    public function test_it_send_notifications_to_mentioned_users_when_comment_is_updated()
    {
        $this->signIn();

        $user = $this->createUser();
        $activity = Activity::factory()->create();
        $comment = $activity->comments()->create(['body' => 'comment']);

        Notification::fake();

        $commentId = $this->putJson('/api/comments/'.$comment->getKey(), [
            'body' => 'Other Text - '.$this->mentionText($user->id, $user->name),
        ])->getData()->id;

        Notification::assertSentTo($user, UserMentioned::class, function (UserMentioned $notification) use ($activity, $commentId) {
            return $notification->mentionUrl === "/activities/{$activity->id}?comment_id={$commentId}";
        });
    }

    public function test_it_send_notifications_to_mentioned_users_when_comment_is_created_via_resource()
    {
        $this->signIn();

        $user = $this->createUser();
        $activity = Activity::factory()->create();

        Notification::fake();

        $comment = $activity->addComment([
            'via_resource' => 'activities',
            'via_resource_id' => $activity->id,
            'body' => 'Other Text - '.$this->mentionText($user->id, $user->name),
        ]);

        Notification::assertSentTo($user, UserMentioned::class, function (UserMentioned $notification) use ($activity, $comment) {
            return $notification->mentionUrl === "/activities/{$activity->id}?comment_id={$comment->id}&section=activities&resourceId={$activity->id}";
        });
    }

    public function test_it_send_notification_to_mentioned_users_when_comment_is_updated_via_resource()
    {
        $this->signIn();

        $user = $this->createUser();
        $activity = Activity::factory()->create();
        $comment = $activity->comments()->create(['body' => 'comment']);

        Notification::fake();

        $this->putJson('/api/comments/'.$comment->getKey(), [
            'via_resource' => 'activities',
            'via_resource_id' => $activity->id,
            'body' => 'Other Text - '.$this->mentionText($user->id, $user->name),
        ])->getData()->id;

        Notification::assertSentTo($user, UserMentioned::class, function (UserMentioned $notification) use ($activity, $comment) {
            return $notification->mentionUrl === "/activities/{$activity->id}?comment_id={$comment->id}&section=activities&resourceId={$activity->id}";
        });
    }
}
