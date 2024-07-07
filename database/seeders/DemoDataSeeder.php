<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Models\ActivityType;
use Modules\Billable\App\Models\Product;
use Modules\Brands\App\Models\Brand;
use Modules\Calls\App\Models\Call;
use Modules\Calls\App\Models\CallOutcome;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Contacts\App\Models\Source;
use Modules\Core\App\Environment;
use Modules\Core\App\Models\Country;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Pipeline;
use Modules\Deals\Database\Seeders\LostReasonSeeder;
use Modules\Documents\App\Enums\DocumentViewType;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentType;
use Modules\Notes\App\Models\Note;
use Modules\Users\App\Models\User;

class DemoDataSeeder extends Seeder
{
    /**
     * Demo data pipeline.
     */
    protected ?Pipeline $pipeline = null;

    /**
     * Demo data products.
     */
    protected array $products = [
        'SEO Optimization',
        'Web Design',
        'Consultant Services',
        'MacBook Pro',
        'Marketing Services',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        settings(['company_country_id' => $this->getCountry()->getKey()]);

        $this->callSilent(LostReasonSeeder::class);

        $users = $this->createUsers();
        $firstUser = $users->first();

        $this->configureDefaultUser($firstUser);

        foreach ($users as $index => $user) {
            // For activity log causer and created_by
            Auth::loginUsingId($user->id);

            Product::factory()->for($user, 'creator')->create([
                'name' => $this->products[$index],
            ]);

            Company::factory(5)->for($user)->for($user, 'creator')
                ->hasPhones()
                ->has($this->makeContactsFactories($user))
                ->for(Source::inRandomOrder()->first())
                ->has($this->makeDealFactories($user))
                ->create(['owner_assigned_date' => now()])
                ->each(function (Company $company) use ($user) {
                    $this->seedCommonRelations($company, $user);

                    $company->deals->each(fn (Deal $deal) => $this->seedCommonRelations($deal, $user));

                    $company->contacts->each(function (Contact $contact) use ($user) {
                        $this->seedCommonRelations($contact, $user);

                        $contact->deals()->get()->each(fn (Deal $deal) => $this->seedCommonRelations($deal, $user));
                    });
                });
        }

        $this->createSampleDocument($firstUser);
        $this->markRandomDealsAsLostOrWon();

        Environment::capture([
            '_server_ip' => '',
            '_prev_app_url' => null,
        ]);
    }

    /**
     * Create users for the demo.
     */
    protected function createUsers()
    {
        return User::factory(5)->create(
            ['super_admin' => collect([0, 1])->random()]
        );
    }

    /**
     * Make contacts factories for the given user.
     */
    protected function makeContactsFactories(User $user)
    {
        return Contact::factory(['owner_assigned_date' => now()])->for($user)->for($user, 'creator')
            ->hasPhones()
            ->has(Deal::factory()->for($this->getPipeline())->for($user)->for($user, 'creator'))
            ->for(Source::inRandomOrder()->first())
            ->count(collect([1, 2])->random());
    }

    /**
     * Make deals factories for the given user.
     */
    protected function makeDealFactories(User $user)
    {
        return Deal::factory(['owner_assigned_date' => now()])
            ->for($this->getPipeline())
            ->for($user)
            ->for($user, 'creator');
    }

    /**
     * Get the pipeline intended for the demo data.
     */
    protected function getPipeline(): Pipeline
    {
        return $this->pipeline ??= Pipeline::first();
    }

    /**
     * Add demo document with template.
     */
    protected function createSampleDocument(User $user): void
    {
        $document = Document::factory()->signable()->create([
            'content' => file_get_contents(
                module_path('documents', 'resources/templates/proposals/branding-proposal.html')
            ),
            'view_type' => DocumentViewType::NAV_LEFT_FULL_WIDTH,
            'user_id' => $user->getKey(),
            'owner_assigned_date' => now(),
            'created_by' => $user->getKey(),
            'document_type_id' => DocumentType::where('flag', 'proposal')->first()->getKey(),
            'title' => 'Branding Proposal',
            'brand_id' => Brand::first()->getKey(),
        ]);

        $contact = Contact::first();
        $document->contacts()->attach(Contact::first());
        $document->companies()->attach($contact->companies->first());
    }

    /**
     * Configure default user for the demo.
     */
    protected function configureDefaultUser(User $user): void
    {
        $user->name = 'Admin';
        $user->email = 'admin@test.com';
        $user->password = bcrypt('-demo');
        $user->remember_token = Str::random(10);
        $user->timezone = 'Europe/Berlin';
        $user->access_api = true;
        $user->super_admin = true;
        $user->save();
    }

    /**
     * Seed the resources common relations.
     */
    protected function seedCommonRelations($model, User $user): void
    {
        $model->changelog()->update([
            'causer_id' => $user->id,
            'causer_type' => $user::class,
            'causer_name' => $user->name,
        ]);

        $model->notes()->save(
            Note::factory()->for($user)->make()
        );

        $model->calls()->save(
            Call::factory()
                ->for(CallOutcome::inRandomOrder()->first(), 'outcome')
                ->for($user)
                ->make()
        );

        $activity = $model->activities()->save(
            Activity::factory()->for($user)
                ->for($user, 'creator')
                ->for(ActivityType::inRandomOrder()->first(), 'type')
                ->make(['note' => null])
        );

        $activity->load('guests.guestable')->addGuest($user);

        if ($contact = $model instanceof Contact ? $model : $model->contacts?->first()) {
            $activity->addGuest($contact);
        }
    }

    /**
     * Get the country for the demo.
     */
    protected function getCountry(): Country
    {
        return Country::where('name', 'United States')->first();
    }

    /**
     * Mark random deals as won and lost.
     */
    protected function markRandomDealsAsLostOrWon(): void
    {
        $lost = Deal::take(5)->inRandomOrder()->get()->each->markAsLost('Probable cause');
        Deal::take(5)->whereNotIn('id', $lost->modelKeys())->inRandomOrder()->get()->each->markAsWon();
    }
}
