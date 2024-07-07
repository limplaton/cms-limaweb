<?php
 

namespace Modules\MailClient\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\MailClient\App\Client\FolderType;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountFolder;

class EmailAccountFolderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmailAccountFolder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_account_id' => EmailAccount::factory(),
            'remote_id' => Str::uuid()->__toString(),
            'type' => FolderType::INBOX,
            'name' => 'INBOX',
            'display_name' => 'INBOX',
            'syncable' => true,
        ];
    }

    public function inbox()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => FolderType::INBOX,
                'name' => 'INBOX',
                'display_name' => 'INBOX',
            ];
        });
    }

    public function trash()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => FolderType::TRASH,
                'name' => 'TRASH',
                'display_name' => 'TRASH',
            ];
        });
    }

    public function sent()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => FolderType::SENT,
                'name' => 'SENT',
                'display_name' => 'SENT',
            ];
        });
    }
}
