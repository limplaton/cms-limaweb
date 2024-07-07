<?php
 

namespace Modules\Users\Tests\Feature;

use Tests\Fixtures\SampleDatabaseNotification;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    public function test_user_can_retrieve_notifications()
    {
        $user = $this->signIn();
        $user->notify(new SampleDatabaseNotification);
        $user->notify(new SampleDatabaseNotification);

        $this->getJson('/api/notifications')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_user_can_retrieve_notification()
    {
        $user = $this->signIn();
        $user->notify(new SampleDatabaseNotification);
        $notification = $user->notifications()->first();

        $this->getJson('/api/notifications/'.$notification->id)
            ->assertOk()
            ->assertJson(
                [
                    'id' => $notification->id,
                    'type' => SampleDatabaseNotification::class,
                    'notifiable_type' => $user::class,
                    'notifiable_id' => $user->id,
                    'data' => [],
                    'read_at' => null,
                ]
            );
    }

    public function test_all_notifications_can_be_marked_as_read()
    {
        $user = $this->signIn();
        $user->notify(new SampleDatabaseNotification);
        $notification = $user->notifications()->first();

        $this->putJson('/api/notifications')
            ->assertNoContent();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_can_be_deleted()
    {
        $user = $this->signIn();
        $user->notify(new SampleDatabaseNotification);
        $notification = $user->notifications()->first();

        $this->deleteJson('/api/notifications/'.$notification->id)
            ->assertNoContent();
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_it_uses_only_logged_in_user_notifications()
    {
        $user = $this->signIn();
        $user2 = $this->createUser();
        $user->notify(new SampleDatabaseNotification);
        $user2->notify(new SampleDatabaseNotification);

        $otherUserNotification = $user2->notifications()->first();

        $this->getJson('/api/notifications')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/notifications/'.$otherUserNotification->id)->assertNotFound();
        $this->deleteJson('/api/notifications/'.$otherUserNotification->id)->assertNotFound();
        $this->putJson('/api/notifications');

        $this->assertNull($otherUserNotification->fresh()->read_at);
    }
}
