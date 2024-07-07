<?php
 

namespace Modules\Core\App\Console\Commands;

use App\Installer\RequirementsChecker;
use Illuminate\Console\Command;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Updater\Patcher;
use Modules\Users\App\Models\User;
use Throwable;

class PatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updater:patch {--key= : Purchase key} {--force} {--delete-source=true}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply any available patches.';

    /**
     * Execute the console
     */
    public function handle(RequirementsChecker $requirements, Patcher $patcher): void
    {
        if ($requirements->fails('zip')) {
            $this->error(__('core::update.patch_zip_is_required'));

            return;
        }

        $this->info('Configuring purchase key.');
        $patcher->usePurchaseKey($this->option('key') ?: '');

        $force = $this->option('force');
        $deleteSource = filter_var($this->option('delete-source'), FILTER_VALIDATE_BOOL);

        $patches = $patcher->getAvailablePatches()->reject->isApplied();

        if ($patches->isEmpty()) {
            $this->info('No patches available for the current installation version.');

            return;
        }

        if (! $force && User::anyActiveRecently()) {
            $this->info('Skipping patching, the last active user was in less than 30 minutes, try later.');

            return;
        }

        $this->down();

        try {
            foreach ($patches as $patch) {
                $this->info('Applying patch with token: '.$patch->token());
                $patcher->apply($patcher->fetch($patch), $deleteSource);
            }
        } catch (Throwable $e) {
            $this->up();

            throw $e;
        } finally {
            $this->up();

            if (config('updater.restart_queue')) {
                Innoclapps::restartQueue();
            }
        }
    }

    /**
     * Bring the application out of maintenance mode
     */
    protected function up(): void
    {
        $this->info('Bringing the application out of maintenance mode.');
        $this->callSilently('up');
    }

    /**
     * Put the application into maintenance mode
     */
    protected function down(): void
    {
        $this->info('Putting the application into maintenance mode.');
        $this->callSilently('down', ['--render' => 'core::errors.patching']);
    }
}
