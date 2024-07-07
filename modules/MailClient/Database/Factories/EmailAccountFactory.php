<?php
 

namespace Modules\MailClient\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\App\Common\Synchronization\SyncState;
use Modules\Core\Database\Factories\OAuthAccountFactory;
use Modules\MailClient\App\Client\ConnectionType;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\Users\App\Models\User;

class EmailAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmailAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'connection_type' => ConnectionType::Imap,
            'requires_auth' => false,
            'initial_sync_from' => now(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the account requires authentication
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function requiresAuth()
    {
        return $this->state(function (array $attributes) {
            return [
                'requires_auth' => true,
            ];
        });
    }

    /**
     * Indicate that the account is personal
     *
     * @param  \Modules\Users\App\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function personal($user = null)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user ?: User::factory(),
            ];
        });
    }

    /**
     * Indicate that the account is shared
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function shared()
    {
        return $this->state(function (array $attributes) {
            return [
                'user_id' => null,
            ];
        });
    }

    /**
     * Indicate that the account is of type IMAP
     *
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function imap(array $overwrite = [])
    {
        return $this->state(function (array $attributes) use ($overwrite) {
            return array_merge([
                'password' => 'test',
                'imap_server' => 'imap.example.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_server' => 'smtp.example.com',
                'smtp_port' => 465,
                'smtp_encryption' => 'ssl',
                'validate_cert' => false,
            ], $overwrite, ['connection_type' => ConnectionType::Imap]);
        });
    }

    /**
     * Indicate that the account is of type Gmail
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function gmail()
    {
        return $this->state(function (array $attributes) {
            return [
                'connection_type' => ConnectionType::Gmail,
            ];
        });
    }

    /**
     * Indicate that the account is of type Outlook
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function outlook()
    {
        return $this->state(function (array $attributes) {
            return [
                'connection_type' => ConnectionType::Outlook,
            ];
        });
    }

    /**
     * Indicate that the account sync is disabled
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function syncDisabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'sync_state' => SyncState::DISABLED,
            ];
        });
    }

    /**
     * Indicate that the account sync is stopped
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function syncStopped()
    {
        return $this->state(function (array $attributes) {
            return [
                'sync_state' => SyncState::STOPPED,
            ];
        });
    }

    /**
     * Add OAuth account to the email account.
     *
     * @return EmailAccountFactory
     */
    public function hasOAuth(OAuthAccountFactory $OAuthAccountFactory)
    {
        return $this->afterCreating(function (EmailAccount $account) use ($OAuthAccountFactory) {
            $account->access_token_id = $OAuthAccountFactory->create()->getKey();
            $account->save();
        });
    }
}
