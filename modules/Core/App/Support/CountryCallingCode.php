<?php
 

namespace Modules\Core\App\Support;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\App\Models\Country;

class CountryCallingCode
{
    protected static ?Collection $countries = null;

    /**
     * Guess phone prefix.
     */
    public static function guess(): ?string
    {
        return ($country ?? static::getCompanyCountry())?->calling_code;
    }

    /**
     * Get calling code from the given country.
     */
    public static function fromCountry(int $countryId): ?string
    {
        return static::findCountry($countryId)?->calling_code;
    }

    /**
     * Get the company country.
     */
    public static function getCompanyCountry(): ?Country
    {
        if ($countryId = settings('company_country_id')) {
            return static::findCountry((int) $countryId);
        }

        return null;
    }

    /**
     * Check whether the given number starts with any calling code.
     */
    public static function startsWithAny(string $number): bool
    {
        static::loadCountriesInCache();

        return (bool) static::$countries->first(
            fn ($country) => str_starts_with($number, '+'.$country->calling_code)
        );
    }

    /**
     * Get random calling code.
     */
    public static function random(): string
    {
        static::loadCountriesInCache();

        return '+'.static::$countries->random()->calling_code;
    }

    /**
     * Find country by given ID.
     */
    protected static function findCountry(int $countryId): ?Country
    {
        static::loadCountriesInCache();

        return static::$countries->find($countryId);
    }

    /**
     * Load the counties in cache.
     */
    protected static function loadCountriesInCache(): void
    {
        if (! static::$countries) {
            static::$countries = Country::get(['id', 'calling_code']);
        }
    }
}
