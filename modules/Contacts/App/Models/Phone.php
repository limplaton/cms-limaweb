<?php
 

namespace Modules\Contacts\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Contacts\App\Enums\PhoneType;
use Modules\Contacts\Database\Factories\PhoneFactory;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Import\Import;
use Modules\Core\App\Support\CountryCallingCode;

class Phone extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'number', 'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => PhoneType::class,
    ];

    /**
     * The relationships that should be touched on save.
     *
     * @var array
     */
    protected $touches = ['phoneable'];

    /**
     * Get the phoneable
     */
    public function phoneable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Generate random phone number
     */
    public static function generateRandomNumber(): string
    {
        return CountryCallingCode::random().mt_rand(100, 1000).'-'.mt_rand(100, 1000).'-'.mt_rand(100, 1000);
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            // For table serialization, will show the string value on the front-end
            'type' => $this->type->name,
        ]);
    }

    /**
     * Touch the owning relations of the model.
     *
     * @return void
     */
    public function touchOwners()
    {
        if (Import::$running) {
            return;
        }

        return parent::touchOwners();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PhoneFactory
    {
        return PhoneFactory::new();
    }
}
