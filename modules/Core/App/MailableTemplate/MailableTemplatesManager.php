<?php
 

namespace Modules\Core\App\MailableTemplate;

use Illuminate\Support\Collection;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Models\MailableTemplate as MailableTemplateModel;
use ReflectionMethod;

class MailableTemplatesManager
{
    /**
     * All of the registered mailable templates.
     */
    protected array $templates = [];

    /**
     * Database templates cache.
     */
    protected ?Collection $dbTemplates = null;

    /**
     * Register new mailable class.
     */
    public function register(string|array $mailable): static
    {
        $this->templates = array_unique(array_merge($this->templates, (array) $mailable));

        return $this;
    }

    /**
     * Get all available/registered mailables.
     */
    public function get(): array
    {
        return $this->templates;
    }

    /**
     * Seed mailable(s) templates for the given locale, optionally provide a single template to seed.
     */
    public function seedForLocale(string $locale, ?string $template = null): static
    {
        $templates = $template ? [$template] : $this->get();

        foreach ($templates as $template) {
            $method = new ReflectionMethod($template, 'seed');

            $method->invoke(null, $locale);
        }

        return $this;
    }

    /**
     * Check whether mailable tmeplates should be seeded.
     */
    public function shouldSeed(): bool
    {
        $dbTemplates = $this->getMailableTemplates();

        $totalAvailable = count($this->get());

        foreach (Innoclapps::locales() as $locale) {
            if ($dbTemplates->where('locale', $locale)->count() < $totalAvailable) {
                return true;
            }
        }

        // Check if the template in local does not exists
        return ! is_null($this->getStaleDatabaseTemplates());
    }

    /**
     * Seed the mailable templates.
     */
    public function seed(): static
    {
        if (! $this->shouldSeed()) {
            return $this;
        }

        if ($deletedTemplateFiles = $this->getStaleDatabaseTemplates()) {
            $deletedTemplateFiles->each->delete();
        }

        foreach (Innoclapps::locales() as $locale) {
            $this->seedForLocale($locale);
        }

        return $this;
    }

    /**
     * Forget the registered mailable templates cache.
     */
    public function forget(): static
    {
        $this->templates = [];
        $this->dbTemplates = null;

        return $this;
    }

    /**
     * Get the database mailable templates.
     */
    protected function getMailableTemplates(): Collection
    {
        return $this->dbTemplates ??= MailableTemplateModel::get();
    }

    /**
     * Get the database templates that are without local template.
     *
     * In this case, the local file template is deleted but the one in database is still hanging there.
     */
    protected function getStaleDatabaseTemplates(): ?Collection
    {
        $local = $this->get();

        $dbMailables = $this->getMailableTemplates()->unique('mailable');

        $removed = array_diff($dbMailables->pluck('mailable')->all(), $local);

        if (count($removed) > 0) {
            return $dbMailables->filter(function ($template) use ($removed) {
                return in_array($template->mailable, $removed);
            })->values();
        }

        return null;
    }
}
