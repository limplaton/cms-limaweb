<?php
 

namespace Modules\Users\App\Mention;

use Illuminate\Support\Facades\Auth;
use KubAT\PhpSimple\HtmlDomParser;
use Modules\Users\App\Models\User;
use Modules\Users\App\Notifications\UserMentioned;
use Spatie\Url\Url;

class PendingMention
{
    /**
     * Mentionable url
     */
    protected string $url;

    protected array $urlQueryParameters = [];

    /**
     * Mentioned users
     */
    protected array $users = [];

    /**
     * Initialize new PendingMention instance.
     */
    public function __construct(protected string $text)
    {
        $this->users = $this->findMentionedUsers();
    }

    /**
     * Set the URL for the mentionable
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Notify the mentioned users
     */
    public function notify(?User $mentioner = null): void
    {
        collect($this->users)->each(function (User $user) use ($mentioner) {
            $user->notify(
                new UserMentioned((string) $this->getMentionUrl(), $mentioner ?? Auth::user())
            );
        });
    }

    /**
     * Add query parameter to the mention url
     */
    public function withUrlQueryParameter(array|string $key, ?string $value = null): static
    {
        if (is_array($key)) {
            foreach ($key as $parameter => $value) {
                $this->withUrlQueryParameter($parameter, $value);
            }
        } elseif (! is_null($value)) {
            $this->urlQueryParameters[$key] = $value;
        }

        return $this;
    }

    /**
     * Get the mention URL
     */
    public function getMentionUrl(): Url
    {
        $url = $this->createUrlInstance();

        foreach ($this->urlQueryParameters as $key => $value) {
            $url = $url->withQueryParameter($key, $value);
        }

        return $url;
    }

    /**
     * Create new URL instance
     */
    protected function createUrlInstance(): Url
    {
        return Url::fromString($this->url);
    }

    /**
     * Find the mentioned users from the text
     */
    protected function findMentionedUsers(): array
    {
        if ($this->text === '') {
            return [];
        }

        $mentioneduserIds = [];
        $dom = HtmlDomParser::str_get_html($this->text);

        foreach ($dom->find('[data-mention-id]') as $element) {
            if ($element->getAttribute('data-notified') == 'false') {
                $mentioneduserIds[] = $element->getAttribute('data-mention-id');
            }
        }

        return User::findMany(
            array_map('intval', array_unique($mentioneduserIds))
        )->all();
    }

    /**
     * check whether there are mentioned users
     */
    public function hasMentions(): bool
    {
        return count($this->users) > 0;
    }

    /**
     * Get the updated text with content attribute data-notified to true so the next time these users won't be notified.
     */
    public function getUpdatedText(): string
    {
        if (! $this->hasMentions()) {
            return $this->text;
        }

        $dom = HtmlDomParser::str_get_html($this->text);

        foreach ($dom->find('[data-mention-id]') as $element) {
            $element->setAttribute('data-notified', 'true');
        }

        return $dom->save();
    }
}
