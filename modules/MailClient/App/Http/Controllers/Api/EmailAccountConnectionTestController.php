<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use App\Installer\RequirementsChecker;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Rules\StringRule;
use Modules\MailClient\App\Client\ClientManager;
use Modules\MailClient\App\Client\ConnectionType;
use Modules\MailClient\App\Client\Exceptions\ConnectionErrorException;
use Modules\MailClient\App\Client\Imap\Config as ImapConfig;
use Modules\MailClient\App\Client\Imap\SmtpConfig;
use Modules\MailClient\App\Models\EmailAccount;

class EmailAccountConnectionTestController extends ApiController
{
    protected $imapFolders = [];

    /**
     * Test the account connection.
     */
    public function handle(Request $request, RequirementsChecker $requirementsChecker): JsonResponse
    {
        if ($requirementsChecker->fails('imap')) {
            abort(409, 'In order to use IMAP account type, you will need to enable the PHP extension "imap".');
        }

        if (! function_exists('imap_open')) {
            abort(409, 'The PHP "imap" extension is enabled, but the "imap_open" PHP function is disabled, remove all the imap_* related PHP functions from the php.ini "disable_functions" configuration directive.');
        }

        $validator = Validator::make($request->all(), [
            'connection_type' => 'required:in:'.ConnectionType::Imap->value,
            'email' => ['required', 'email', StringRule::make()],
            'password' => $request->has('id') ? 'nullable' : 'required',
            'imap_server' => ['required', StringRule::make()],
            'imap_port' => 'required|numeric',
            'imap_encryption' => ['nullable', Rule::in(ClientManager::ENCRYPTION_TYPES)],
            'smtp_server' => ['required', StringRule::make()],
            'smtp_port' => 'required|numeric',
            'smtp_encryption' => ['nullable', Rule::in(ClientManager::ENCRYPTION_TYPES)],
        ]);

        $validator->after(function ($validator) use ($request) {
            // Validation passes, now we can validate the connections
            $this->testConnection(
                $validator,
                [
                    'username' => $request->input('username'),
                    'validate_cert' => $request->input('validate_cert'),
                    'email' => $request->input('email'),
                    'password' => $this->getPassword($request),
                    'imap_server' => $request->input('imap_server'),
                    'imap_port' => $request->input('imap_port'),
                    'imap_encryption' => $request->input('imap_encryption'),
                ],
                [
                    'username' => $request->input('username'),
                    'validate_cert' => $request->input('validate_cert'),
                    'email' => $request->input('email'),
                    'password' => $this->getPassword($request),
                    'smtp_server' => $request->input('smtp_server'),
                    'smtp_port' => $request->input('smtp_port'),
                    'smtp_encryption' => $request->input('smtp_encryption'),
                ]
            );
        });

        $validator->validate();

        return $this->response(['folders' => $this->imapFolders]);
    }

    /**
     * Determine which password should be used for the test configuration
     *
     * @return string
     */
    protected function getPassword(Request $request)
    {
        $account = $request->input('id') ? EmailAccount::find($request->input('id')) : false;

        if (! $account) {
            return $request->input('password');
        }

        // User inputted password for testing
        if ($password = $request->input('password')) {
            return $password;
        }

        return $account->password;
    }

    /**
     * Test the actual connection after all validation passes
     *
     *
     * @return void
     */
    protected function testConnection(ValidatorContract $validator, array $imapConfig, array $smtpConfig)
    {
        if ($validator->errors()->isEmpty()) {
            try {
                $client = ClientManager::createSmtpClient(new SmtpConfig(
                    $smtpConfig['smtp_server'],
                    $smtpConfig['smtp_port'],
                    $smtpConfig['smtp_encryption'],
                    $smtpConfig['email'],
                    $smtpConfig['validate_cert'],
                    $smtpConfig['username'],
                    $smtpConfig['password']
                ));

                ClientManager::testConnection($client);
            } catch (ConnectionErrorException $e) {
                $validator->errors()->add('smtp-connection', 'SMTP: '.$e->getMessage());
            }

            try {
                $client = ClientManager::createImapClient(new ImapConfig(
                    $imapConfig['imap_server'],
                    $imapConfig['imap_port'],
                    $imapConfig['imap_encryption'],
                    $imapConfig['email'],
                    $imapConfig['validate_cert'],
                    $imapConfig['username'],
                    $imapConfig['password']
                ));

                ClientManager::testConnection($client);

                $this->imapFolders = $client->getFolders();
            } catch (ConnectionErrorException $e) {
                $validator->errors()->add('imap-connection', 'IMAP: '.$e->getMessage());
            }
        }
    }
}
