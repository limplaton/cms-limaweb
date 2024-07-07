<?php
 

namespace Modules\Activities\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Models\ActivityType;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Support\Carbon;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Deals\App\Models\Deal;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;
use Modules\Users\App\Notifications\UserMentioned;
use Modules\Users\Tests\Concerns\TestsMentions;

class ActivityResourceTest extends ResourceTestCase
{
    use TestsMentions;

    protected $samplePayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->samplePayload = [
            'title' => 'Activity Title',
            'due_date' => '2021-12-14 15:00:00',
            'end_date' => '2021-12-15 17:00:00',
            'activity_type_id' => ActivityType::factory()->create()->id,
            'user_id' => $this->createUser()->id,
        ];
    }

    protected function tearDown(): void
    {
        unset($this->samplePayload);
        parent::tearDown();
    }

    protected $resourceName = 'activities';

    public function test_user_can_create_activity()
    {
        $this->signIn();
        $user = $this->createUser();
        $type = ActivityType::factory()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create();

        $dueDate = Carbon::parse()->addMonth()->seconds(0)->milliseconds(0);
        $endDate = Carbon::parse()->addMonth()->seconds(0)->milliseconds(0);

        $response = $this->postJson($this->createEndpoint(), [
            'title' => 'Activity Title',
            'description' => 'Description',
            'note' => 'Note',
            'due_date' => $dueDate->toISOString(),
            'end_date' => $endDate->toISOString(),
            'reminder_minutes_before' => 60,
            'activity_type_id' => $type->id,
            'user_id' => $user->id,
            'companies' => [$company->id],
            'contacts' => [$contact->id],
            'deals' => [$deal->id],
            'guests' => ['users' => [$user->id], 'contacts' => [$contact->id]],
        ])->assertCreated();

        $this->assertResourceJsonStructure($response);

        $response->assertJsonCount(1, 'companies')
            ->assertJsonCount(1, 'contacts')
            ->assertJsonCount(1, 'deals')
            ->assertJsonCount(2, 'guests')
            ->assertJson([
                'companies' => [
                    ['id' => $company->id],
                ],
                'contacts' => [
                    ['id' => $contact->id],
                ],
                'deals' => [
                    ['id' => $deal->id],
                ],
                'is_completed' => false,
                'is_due' => false,
                'is_reminded' => false,
                'title' => 'Activity Title',
                'description' => 'Description',
                'note' => 'Note',
                'due_date' => $dueDate->toJSON(),
                'end_date' => $endDate->toJSON(),
                'reminder_minutes_before' => 60,
                'activity_type_id' => $type->id,
                'user_id' => $user->id,
                'was_recently_created' => true,
                'display_name' => 'Activity Title',
            ]);
    }

    public function test_activity_due_and_end_date_accept_multiple_payload_formats()
    {
        $this->signIn();

        $this
            ->postJson(
                $this->createEndpoint(),
                $this->samplePayload
            )
            ->assertCreated()
            ->assertJson([
                'due_date' => Carbon::parse('2021-12-14 15:00:00')->toJSON(),
                'end_date' => Carbon::parse('2021-12-15 17:00:00')->toJSON(),
            ]);

        $this
            ->postJson(
                $this->createEndpoint(),
                array_merge($this->samplePayload, ['due_date' => '2021-12-14', 'end_date' => '2021-12-15'])
            )
            ->assertCreated()
            ->assertJson([
                'due_date' => '2021-12-14',
                'end_date' => '2021-12-15',
            ]);

        $this
            ->postJson(
                $this->createEndpoint(),
                array_merge($this->samplePayload, ['due_date' => '2021-12-14 15:00:00', 'end_date' => '2021-12-14'])
            )
            ->assertCreated()
            ->assertJson([
                'due_date' => Carbon::parse('2021-12-14 15:00:00')->toJSON(),
                'end_date' => '2021-12-14',
            ]);

        $testTz = 'Europe/Berlin';
        $date = Carbon::parse('2023-11-24T19:00:00', $testTz);

        $this
            ->postJson(
                $this->createEndpoint(),
                array_filter(array_merge($this->samplePayload, ['due_date' => $date->toIso8601String(), 'end_date' => null]))
            )
            ->assertCreated()
            ->assertJson([
                'due_date' => $date->clone()->setTimezone(config('app.timezone'))->toJSON(),
                'end_date' => '2023-11-24',
            ]);

        $this
            ->postJson(
                $this->createEndpoint(),
                array_merge($this->samplePayload, ['due_date' => $date->toIso8601String(), 'end_date' => $date->toIso8601String()])
            )
            ->assertCreated()
            ->assertJson([
                'due_date' => $date->clone()->setTimezone(config('app.timezone'))->toJSON(),
                'end_date' => $date->clone()->setTimezone(config('app.timezone'))->toJSON(),
            ]);

        $this
            ->postJson(
                $this->createEndpoint(),
                array_merge($this->samplePayload, ['due_date' => '2023-11-24T00:00:00+01:00', 'end_date' => '2023-11-24T00:00:00+01:00'])
            )
            ->assertCreated()
            ->assertJson([
                'due_date' => '2023-11-23T23:00:00.000000Z',
                'end_date' => '2023-11-23T23:00:00.000000Z',
            ]);
    }

    public function test_it_uses_the_due_date_when_end_date_is_not_present()
    {
        $payload = $this->samplePayload;
        unset($payload['end_date']);

        $this->signIn();

        $this->postJson($this->createEndpoint(), array_merge($payload, [
            'due_date' => '2021-12-14 15:00',
        ]))->assertCreated()->assertJson([
            'end_date' => '2021-12-14',
        ]);
    }

    public function test_user_can_create_full_day_activity_with_same_ending_day()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), array_merge($this->samplePayload, [
            'due_date' => '2021-12-14',
            'end_date' => '2021-12-14',
        ]))->assertCreated()->assertJson([
            'end_date' => '2021-12-14',
            'end_date' => '2021-12-14',
        ]);
    }

    public function test_user_can_create_full_day_activity_with_different_ending_day()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), array_merge($this->samplePayload, [
            'due_date' => '2021-12-14',
            'end_date' => '2021-12-17',
        ]))->assertCreated()->assertJson([
            'end_date' => '2021-12-14',
            'end_date' => '2021-12-17',
        ]);
    }

    public function test_activity_due_and_end_date_are_properly_validated()
    {
        $this->signIn();
        $payload = $this->samplePayload;
        unset($payload['due_date'],$payload['end_date']);

        // Does not fails on creation when the dates are equal
        $this->postJson($this->createEndpoint(), array_merge($payload, [
            'due_date' => '2021-12-14 15:00:00',
            'end_date' => '2021-12-14 15:00:00',
        ]))->assertCreated();

        $this->postJson($this->createEndpoint(), array_merge($payload, [
            'due_date' => '2021-12-14',
            'end_date' => '2021-12-14',
        ]))->assertCreated();

        // End date is less then due date
        $this->postJson($this->createEndpoint(), array_merge($payload, [
            'due_date' => '2021-12-14 15:00:00',
            'end_date' => '2021-12-13 14:00:00',
        ]))->assertJsonValidationErrors([
            'end_date' => __('activities::activity.validation.end_date.less_than_due'),
        ]);

        // end time is required when end date is in future
        $this->postJson($this->createEndpoint(), array_merge($payload, [
            'due_date' => '2021-12-14 15:00:00',
            'end_date' => '2021-12-13',
        ]))->assertJsonValidationErrors([
            'end_date' => __('activities::activity.validation.end_time.required_when_end_date_is_in_future'),
        ]);

        // Time is required when the date is in future
        $this->postJson($this->createEndpoint(), array_merge($payload, [
            'due_date' => '2021-12-14 15:00:00',
            'end_date' => '2021-12-16',
        ]))->assertJsonValidationErrors([
            'end_date' => __('activities::activity.validation.end_time.required_when_end_date_is_in_future'),
        ]);

        // object based payload
        // Requires due_date when time is provided
        $this->postJson($this->createEndpoint(), array_merge($payload, [
            'due_time' => '15:00:00',
        ]))->assertJsonValidationErrorFor('due_date');

        // Required end_date when time is provided
        $this->postJson($this->createEndpoint(), array_merge($payload, [
            'end_time' => '15:00:00',
        ]))->assertJsonValidationErrorFor('end_date');
    }

    public function test_it_uses_the_default_type_when_is_activity_type_id_is_not_present()
    {
        $this->signIn();
        $payload = $this->samplePayload;
        unset($payload['activity_type_id']);
        ActivityType::setDefault(ActivityType::first()->id);

        $this->postJson($this->createEndpoint(), $payload)->assertJson([
            'activity_type_id' => ActivityType::first()->id,
        ]);
    }

    public function test_it_requires_type_when_present()
    {
        $this->signIn();
        $payload = $this->samplePayload;
        $activity = $this->factory()->create();
        $payload['activity_type_id'] = null;

        $this->putJson($this->updateEndpoint($activity), $payload)
            ->assertJsonValidationErrors(['activity_type_id' => __('validation.filled', [
                'attribute' => 'Activity Type',
            ])]);

        $this->postJson($this->createEndpoint(), $payload)
            ->assertJsonValidationErrors(['activity_type_id' => __('validation.filled', [
                'attribute' => 'Activity Type',
            ])]);
    }

    public function test_on_creation_it_requires_type_when_there_is_no_default_type()
    {
        $this->signIn();
        $payload = $this->samplePayload;
        unset($payload['activity_type_id']);

        $this->postJson($this->createEndpoint(), $payload)->assertJsonValidationErrorFor('activity_type_id');
    }

    public function test_user_can_update_activity()
    {
        $user = $this->signIn();
        $type = ActivityType::factory()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create();
        $record = $this->factory()->has(Company::factory())->create();

        $dueDate = Carbon::parse()->addMonth()->seconds(0)->milliseconds(0);
        $endDate = Carbon::parse()->addMonth()->seconds(0)->milliseconds(0);

        $response = $this->putJson($this->updateEndpoint($record), [
            'title' => 'Activity Title',
            'description' => 'Description',
            'note' => 'Note',
            'due_date' => $dueDate->toISOString(),
            'end_date' => $endDate->toISOString(),
            'reminder_minutes_before' => 60,
            'activity_type_id' => $type->id,
            'user_id' => $user->id,
            'companies' => [$company->id],
            'contacts' => [$contact->id],
            'deals' => [$deal->id],
            'guests' => ['users' => [$user->id], 'contacts' => [$contact->id]],
        ])
            ->assertOk();

        $this->assertResourceJsonStructure($response);

        $response->assertJsonCount(count($this->resource()->resolveActions(app(ResourceRequest::class))), 'actions')
            ->assertJsonCount(1, 'companies')
            ->assertJsonCount(1, 'contacts')
            ->assertJsonCount(1, 'deals')
            ->assertJsonCount(2, 'guests')
            ->assertJson([
                'companies' => [
                    ['id' => $company->id],
                ],
                'contacts' => [
                    ['id' => $contact->id],
                ],
                'deals' => [
                    ['id' => $deal->id],
                ],
                'is_completed' => false,
                'is_due' => false,
                'is_reminded' => false,
                'title' => 'Activity Title',
                'description' => 'Description',
                'note' => 'Note',
                'due_date' => $dueDate->toJSON(),
                'end_date' => $endDate->toJSON(),
                'reminder_minutes_before' => 60,
                'activity_type_id' => $type->id,
                'user_id' => $user->id,
                'display_name' => 'Activity Title',
            ]);
    }

    public function test_user_can_retrieve_activities()
    {
        $this->signIn();

        $this->factory()->count(5)->create();

        $this->getJson($this->indexEndpoint())->assertJsonCount(5, 'data');
    }

    public function test_user_can_retrieve_activity()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_globally_search_activities()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson("/api/search?q={$record->title}&only=activities")
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path())
            ->assertJsonPath('0.data.0.display_name', $record->displayName());
    }

    public function test_an_unauthorized_user_can_global_search_only_activities()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own activities')->signIn();
        $user1 = $this->createUser();

        $this->factory()->for($user1)->create(['title' => 'KONKORD DIGITAL']);
        $record = $this->factory()->for($user)->create(['title' => 'KONKORD ONLINE']);

        $this->getJson('/api/search?q=KONKORD&only=activities')
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path())
            ->assertJsonPath('0.data.0.display_name', $record->displayName());
    }

    public function test_user_can_export_activities()
    {
        $this->performExportTest();
    }

    public function test_user_can_import_activities()
    {
        $this->createUser();

        $this->performImportTest();
    }

    public function test_user_can_load_the_activities_table()
    {
        $this->performTestTableLoad();
    }

    public function test_activities_table_loads_all_fields()
    {
        $this->performTestTableCanLoadWithAllFields();
    }

    public function test_activities_table_can_be_customized()
    {
        $user = $this->signIn();

        $this->postJson($this->tableEndpoint().'/settings', [
            'maxHeight' => '120px',
            'columns' => [
                ['attribute' => 'created_at', 'order' => 2, 'hidden' => false],
                ['attribute' => 'title', 'order' => 3, 'hidden' => false],
            ],
            'order' => [
                ['attribute' => 'created_at', 'direction' => 'asc'],
                ['attribute' => 'title', 'direction' => 'desc'],
            ],
        ])->assertOk();

        $settings = $this->resource()->resolveTable($this->createRequestForTable($user))->settings();

        $this->assertSame('120px', $settings->maxHeight());
        $this->assertCount(2, $settings->getCustomizedColumns());
        $this->assertCount(2, $settings->getCustomizedOrderBy());
    }

    public function test_user_can_view_attends_and_owned_including_team_activities()
    {
        // Ticket #461
        $user = $this->asRegularUser()
            ->withPermissionsTo(['view attends and owned activities', 'view team activities'])
            ->createUser();

        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $teamActivity = $this->factory()->for($teamUser)->create();
        $guestActivity = $this->factory()->for($user)->create();
        $attendsActivity = $this->factory()->create();
        $guest = $user->guests()->create([]);
        $guest->activities()->attach($attendsActivity);

        $this->signIn($user);
        $this->getJson($this->showEndpoint($teamActivity))->assertOk();
        $this->getJson($this->showEndpoint($guestActivity))->assertOk();
        $this->getJson($this->showEndpoint($attendsActivity))->assertOk();
    }

    public function test_user_can_retrieve_activities_related_to_associations_authorized_to_view()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own contacts')->signIn();
        $activity = $this->factory()->has(Contact::factory()->for($user))->create();
        $contact = $activity->contacts[0];

        $this->getJson("/api/activities/$activity->id?via_resource=contacts&via_resource_id=$contact->id")->assertOk();
    }

    public function test_user_can_retrieve_activity_associated_with_related_record_the_user_is_authorized_to_see()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own contacts')->createUser();
        $this->signIn($user);
        $otherUser = $this->createUser();
        $activity = $this->factory()->for($otherUser)->has(Contact::factory()->for($user))->create();

        $endpoint = $this->showEndpoint($activity).'?via_resource=contacts&via_resource_id='.$activity->contacts->first()->getKey();

        $this->getJson($endpoint)->assertOk();
    }

    public function test_it_eager_loads_relations_when_retrieving_via_associated_record()
    {
        $this->signIn();

        $call = $this->factory()->has(Contact::factory())->create();
        $call->comments()->create(['body' => 'Test']);

        $contact = $call->contacts[0];

        $this->getJson("/api/contacts/$contact->id/activities")->assertJsonStructure([
            'data' => [
                ['comments_count', 'type', 'media', 'guests'],
            ],
        ])->assertJsonPath('data.0.comments_count', 1);
    }

    public function test_it_eager_loads_relations_when_retrieving_via_timeline()
    {
        $this->signIn();

        $call = $this->factory()->has(Contact::factory())->create();

        $call->comments()->create(['body' => 'Test']);

        $contact = $call->contacts[0];

        $this->getJson("/api/contacts/$contact->id/activities?timeline=1")->assertJsonStructure([
            'data' => [
                ['comments_count', 'type', 'media', 'guests', 'associations_count'],
            ],
        ])->assertJsonPath('data.0.comments_count', 1);
    }

    public function test_it_can_mark_the_activity_as_completed_on_creation()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), array_merge($this->samplePayload, [
            'is_completed' => true,
        ]))->assertJson([
            'is_completed' => true,
        ]);
    }

    public function test_it_can_mark_the_activity_as_completed_on_update()
    {
        $this->signIn();
        $activity = $this->factory()->create();

        $this->putJson($this->updateEndpoint($activity), array_merge($this->samplePayload, [
            'is_completed' => true,
        ]))->assertJson([
            'is_completed' => true,
        ]);
    }

    public function test_it_can_mark_the_activity_as_incompleted_on_update()
    {
        $this->signIn();

        $activity = $this->factory()->completed()->create();

        $this->putJson($this->updateEndpoint($activity), array_merge($this->samplePayload, [
            'is_completed' => false,
        ]))->assertJson([
            'is_completed' => false,
        ]);
    }

    public function test_activity_guests_can_be_saved_on_creation()
    {
        $user = $this->signIn();
        $contact = Contact::factory()->create();
        $attributes = $this->factory()->raw();

        $attributes['guests'] = [
            'users' => [$user->id],
            'contacts' => [$contact->id],
        ];

        $this->postJson($this->createEndpoint(), $attributes)->assertCreated();

        $this->assertCount(2, Activity::first()->guests);
    }

    public function test_it_send_notifications_to_guests()
    {
        $this->signIn();
        $user = $this->createUser();
        $contact = Contact::factory()->create();
        $attributes = $this->factory()->raw();
        settings()->set('send_contact_attends_to_activity_mail', true);

        $attributes['guests'] = [
            'users' => [$user->id],
            'contacts' => [$contact->id],
        ];

        Mail::fake();
        Notification::fake();

        $this->postJson($this->createEndpoint(), $attributes)->assertCreated();

        Notification::assertSentTo($user, $user->getAttendeeNotificationClass());
        Mail::assertQueued($contact->getAttendeeNotificationClass(), function ($mail) use ($contact) {
            return $mail->hasTo($contact->email);
        });
    }

    public function test_it_does_not_send_notification_when_current_user_is_added_as_guest()
    {
        $currentUser = $this->signIn();
        $user = $this->createUser();
        $attributes = $this->factory()->raw();

        $attributes['guests'] = [
            'users' => [$user->id, $currentUser->id],
        ];

        Notification::fake();

        $this->postJson($this->createEndpoint(), $attributes)->assertCreated();

        Notification::assertSentTo($user, $user->getAttendeeNotificationClass());
        Notification::assertNotSentTo($currentUser, $user->getAttendeeNotificationClass());
    }

    public function test_it_does_not_send_notification_when_contact_send_notification_is_false()
    {
        $this->signIn();

        $contact = Contact::factory()->create();
        $attributes = $this->factory()->raw();
        settings()->set('send_contact_attends_to_activity_mail', false);

        $attributes['guests'] = [
            'contacts' => [$contact->id],
        ];

        Mail::fake();

        $this->postJson($this->createEndpoint(), $attributes)->assertCreated();

        Mail::assertNothingSent();
    }

    public function test_activity_guests_can_be_saved_on_update()
    {
        $user = $this->signIn();
        $contact = Contact::factory()->create();
        $activity = $this->factory()->create();

        $this->putJson($this->updateEndpoint($activity), [
            'guests' => [
                'users' => [$user->id],
                'contacts' => [$contact->id],
            ],
        ]);

        $this->assertSame(2, $activity->guests->count());

        // detach
        $this->putJson($this->updateEndpoint($activity), [
            'guests' => [
                'users' => [$user->id],
            ],
        ]);

        $this->assertSame(1, $activity->guests()->count());
    }

    public function test_it_send_notifications_to_mentioned_users_when_activity_is_created()
    {
        Notification::fake();

        $this->signIn();

        $user = $this->createUser();

        $response = $this->postJson($this->createEndpoint(), array_merge($this->samplePayload, [
            'note' => 'Text - '.$this->mentionText($user->id, $user->name),
        ]))->getData();

        Notification::assertSentTo($user, UserMentioned::class, function (UserMentioned $notification) use ($response) {
            return $notification->mentionUrl === "/activities/{$response->id}";
        });
    }

    public function test_it_send_notifications_to_mentioned_users_when_activity_is_updated()
    {
        Notification::fake();

        $this->signIn();

        $user = $this->createUser();
        $activity = Activity::factory()->create();

        $this->putJson($this->updateEndpoint($activity), [
            'note' => 'Text - '.$this->mentionText($user->id, $user->name),
        ]);

        Notification::assertSentTo($user, UserMentioned::class, function (UserMentioned $notification) use ($activity) {
            return $notification->mentionUrl === "/activities/{$activity->id}";
        });
    }

    public function test_it_send_notifications_to_mentioned_users_when_activity_is_created_via_resource()
    {
        Notification::fake();

        $this->signIn();

        $user = $this->createUser();
        $contact = Contact::factory()->create();

        $response = $this->postJson($this->createEndpoint(), array_merge($this->samplePayload, [
            'note' => 'Text - '.$this->mentionText($user->id, $user->name),
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ]))->getData();

        Notification::assertSentTo($user, UserMentioned::class, function (UserMentioned $notification) use ($response, $contact) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=activities&resourceId={$response->id}";
        });
    }

    public function test_it_send_notifications_to_mentioned_users_when_activity_is_updated_via_resource()
    {
        Notification::fake();

        $this->signIn();

        $user = $this->createUser();
        $contact = Contact::factory()->create();
        $activity = Activity::factory()->create();

        $this->putJson($this->updateEndpoint($activity), [
            'note' => 'Text - '.$this->mentionText($user->id, $user->name),
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ]);

        Notification::assertSentTo($user, UserMentioned::class, function (UserMentioned $notification) use ($activity, $contact) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=activities&resourceId={$activity->id}";
        });
    }

    public function test_user_can_force_delete_activity()
    {
        $user = $this->signIn();

        $record = $this->factory()
            ->has(Contact::factory())
            ->has(Company::factory())
            ->has(Deal::factory())
            ->create();

        $guest = $user->guests()->create([]);
        $guest->activities()->attach($record);

        $record->delete();

        $this->deleteJson($this->forceDeleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 0);
    }

    public function test_activity_can_be_viewed_without_own_permissions()
    {
        $user = $this->asRegularUser()->signIn();
        $record = $this->factory()->for($user)->create();

        $this->getJson($this->showEndpoint($record))->assertOk()->assertJson(['id' => $record->id]);
    }

    public function test_user_can_soft_delete_activity()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 1);
    }

    public function test_edit_all_activities_permission()
    {
        $this->asRegularUser()->withPermissionsTo('edit all activities')->signIn();
        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), $this->samplePayload())->assertOk();
    }

    public function test_edit_own_activities_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('edit own activities')->signIn();
        $record1 = $this->factory()->for($user)->create();
        $record2 = $this->factory()->create();

        $payload = $this->samplePayload();
        $this->putJson($this->updateEndpoint($record1), $payload)->assertOk();
        $this->putJson($this->updateEndpoint($record2), $payload)->assertForbidden();
    }

    public function test_edit_team_activities_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('edit team activities')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record = $this->factory()->for($teamUser)->create();

        $this->putJson($this->updateEndpoint($record))->assertOk();
    }

    public function test_unauthorized_user_cannot_update_activity()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), $this->samplePayload())->assertForbidden();
    }

    public function test_view_all_activities_permission()
    {
        $this->asRegularUser()->withPermissionsTo('view all activities')->signIn();
        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_view_team_activities_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view team activities')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record = $this->factory()->for($teamUser)->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_view_own_activity()
    {
        $user = $this->asRegularUser()->signIn();
        $record = $this->factory()->for($user)->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_activity()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertForbidden();
    }

    public function test_delete_any_activity_permission()
    {
        $this->asRegularUser()->withPermissionsTo('delete any activity')->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
    }

    public function test_delete_own_activities_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('delete own activities')->signIn();

        $record1 = $this->factory()->for($user)->create();
        $record2 = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record1))->assertNoContent();
        $this->deleteJson($this->deleteEndpoint($record2))->assertForbidden();
    }

    public function test_delete_team_activities_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('delete team activities')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record1 = $this->factory()->for($teamUser)->create();
        $record2 = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record1))->assertNoContent();
        $this->deleteJson($this->deleteEndpoint($record2))->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete_activity()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->deleteJson($this->showEndpoint($record))->assertForbidden();
    }

    public function test_it_empties_activities_trash()
    {
        $this->signIn();

        $this->factory()->count(2)->trashed()->create();

        $this->deleteJson('/api/trashed/activities?limit=2')->assertJson(['deleted' => 2]);
        $this->assertDatabaseEmpty('activities');
    }

    public function test_it_excludes_unauthorized_records_from_empty_activities_trash()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own activities', 'delete own activities', 'bulk delete activities'])->signIn();

        $this->factory()->trashed()->create();
        $this->factory()->trashed()->for($user)->create();

        $this->deleteJson('/api/trashed/activities')->assertJson(['deleted' => 1]);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_it_does_not_empty_activities_trash_if_delete_own_activities_permission_is_not_applied()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own activities', 'bulk delete activities'])->signIn();

        $this->factory()->trashed()->for($user)->create();

        $this->deleteJson('/api/trashed/activities')->assertJson(['deleted' => 0]);
        $this->assertDatabaseCount('activities', 1);
    }

    protected function samplePayload()
    {
        return $this->factory()->make()->toArray();
    }

    protected function assertResourceJsonStructure($response)
    {
        $response->assertJsonStructure([
            'actions', 'activity_type_id', 'comments_count', 'companies', 'completed_at', 'contacts', 'created_at', 'created_by', 'deals', 'description', 'display_name', 'due_date', 'end_date', 'guests', 'id', 'is_completed', 'is_due', 'is_reminded', 'media', 'note', 'owner_assigned_date', 'reminded_at', 'reminder_minutes_before', 'timeline_component', 'timeline_key', 'associations_count', 'timeline_relation', 'title', 'type', 'updated_at', 'path', 'user', 'user_id', 'was_recently_created', 'authorizations' => [
                'create', 'delete', 'update', 'view', 'viewAny',
            ],
        ]);
    }
}
