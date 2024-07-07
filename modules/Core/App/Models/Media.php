<?php
 

namespace Modules\Core\App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\App\Concerns\HasMeta;
use Modules\Core\App\Contracts\Metable;
use Modules\Core\Database\Factories\MediaFactory;
use Plank\Mediable\Media as BaseMedia;

class Media extends BaseMedia implements Metable
{
    use HasFactory, HasMeta;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media';

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        /**
         * On media creation, we will add random key identifier
         */
        static::creating(function (Media $model) {
            $model->token = Str::uuid()->toString();
        });

        /**
         * On media deletition, remove the created folder for the resource
         */
        static::deleted(function (Media $model) {
            $disk = $model->disk();

            if (count($disk->files($model->directory)) === 0) {
                $disk->deleteDirectory($model->directory);
            }
        });
    }

    /**
     * Get a count of all of the related models.
     */
    public function totalModels(): int
    {
        return DB::table(config('mediable.mediables_table', 'mediables'))
            ->where('media_id', $this->id)
            ->count();
    }

    /**
     * Get the media filesystem disk instance.
     */
    public function disk(): Filesystem
    {
        return Storage::disk($this->disk);
    }

    /**
     * Mark the current media instance as pending media.
     */
    public function markAsPending(string $draftId): PendingMedia
    {
        return PendingMedia::create([
            'media_id' => $this->id,
            'draft_id' => $draftId,
        ]);
    }

    /**
     * Determine if the media is pending.
     */
    public function isPending(): bool
    {
        return ! is_null($this->pendingData);
    }

    /**
     * Get the media pending instance.
     */
    public function pendingData(): BelongsTo
    {
        return $this->belongsTo(PendingMedia::class, 'id', 'media_id');
    }

    /**
     * Check whether the media video is HTML5 supported video
     *
     * @see https://www.w3schools.com/html/html5_video.asp
     */
    public function isHtml5SupportedVideo(): bool
    {
        return in_array($this->extension, ['mp4', 'webm', 'ogg']);
    }

    /**
     * Check whether the media audio is HTML5 supported audio
     *
     * @see https://www.w3schools.com/html/html5_audio.asp
     */
    public function isHtml5SupportedAudio(): bool
    {
        return in_array($this->extension, ['mp3', 'wav', 'ogg']);
    }

    /**
     * Get the directory where media will be stored when used via string attributes.
     */
    public static function attributeMediableDirectory(): string
    {
        return 'editor';
    }

    /**
     * Check if the media is via text attribute.
     */
    public function viaTextAttribute(): bool
    {
        return $this->directory === static::attributeMediableDirectory();
    }

    /**
     * Get the media item view path
     */
    public function viewPath(): string
    {
        return "/media/{$this->token}";
    }

    /**
     * Get the media item view URL
     */
    public function getViewUrl(): string
    {
        return url($this->viewPath());
    }

    /**
     * Get the media item preview URI
     */
    public function previewPath(): string
    {
        return "/media/{$this->token}/preview";
    }

    /**
     * Get the media item preview URL
     */
    public function getPreviewUrl(): string
    {
        return url($this->previewPath());
    }

    /**
     * Get the media item download URI
     */
    public function downloadPath(): string
    {
        return "/media/{$this->token}/download";
    }

    /**
     * Get the media item preview URL
     */
    public function getDownloadUrl(): string
    {
        return url($this->downloadPath());
    }

    /**
     * Scope a query to only include media by given token.
     */
    public function scopeByToken(Builder $query, string $token): void
    {
        $query->where('token', $token);
    }

    /**
     * Scope a query to only include media by given tokens.
     */
    public function scopeByTokens(Builder $query, mixed $tokens): void
    {
        $query->whereIn('token', $tokens);
    }

    /**
     *  Delete model media by id's
     */
    public function purgeByMediableIds(string $mediable, iterable $ids): bool
    {
        if (count($ids) === 0) {
            return false;
        }

        $this
            ->whereIn($this->getKeyName(), function ($query) use ($mediable, $ids) {
                $query->select('media_id')
                    ->from(config('mediable.mediables_table'))
                    ->where('mediable_type', $mediable)
                    ->whereIn('mediable_id', $ids);
            })
            ->get()
            ->each(function (Media $media) {
                $media->delete();
            });

        return true;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }
}
