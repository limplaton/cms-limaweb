<?php
 

namespace Modules\Core\App\Fields;

use Akaunting\Money\Currency;
use Exception;
use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Facades\Innoclapps;

class Numeric extends Field implements Customfieldable
{
    /**
     * Field component.
     */
    public static $component = 'numeric-field';

    /**
     * Field currency
     */
    public ?Currency $currency = null;

    /**
     * Append short text before the field.
     */
    public ?string $appendText = null;

    /**
     * Prepend short text after the field.
     */
    public ?string $prependText = null;

    /**
     * Initialize Numeric field
     *
     * @param  string  $attribute
     * @param  string|null  $label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label);

        $this
            ->prepareForValidation(function (mixed $value) {
                return $this->parsePreValidationValue($value);
            })
            ->withMeta(['attributes' => ['placeholder' => '--']])
            ->provideSampleValueUsing(fn () => rand(20000, 40000))
            ->useSearchColumn([$this->attribute => '='])
            ->resolveUsing(fn ($model, $attribute) => is_null($model->{$attribute}) ? 0 : (float) $model->{$attribute});
    }

    /**
     * Resolve the displayable field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function resolveForDisplay($model)
    {
        $value = $this->resolve($model);

        if (is_callable($this->displayCallback)) {
            return call_user_func_array($this->displayCallback, [$model, $value, $this->attribute]);
        }

        if (! is_float($value)) {
            $value = (float) $value;
        }

        if ($this->currency) {
            return $this->currency->toMoney($value)->format();
        }

        return $this->appendText.$value.$this->prependText;
    }

    /**
     * Enable minus value.
     */
    public function allowMinus(): static
    {
        $this->withMeta(['attributes' => ['minus' => true]]);

        return $this;
    }

    /**
     * Prepend short text before the field.
     */
    public function prependText(string $text): static
    {
        throw_if(
            ! is_null($this->currency),
            new Exception('Method "prependText" cannot be used in combination with currency.')
        );

        $this->prependText = $text;

        return $this;
    }

    /**
     * Append short text after the field.
     */
    public function appendText(string $text): static
    {
        throw_if(
            ! is_null($this->currency),
            new Exception('Method "appendText" cannot be used in combination with currency.')
        );

        $this->appendText = $text;

        return $this;
    }

    /**
     * Set the field currency
     */
    public function currency(string|Currency|null $currency = null): static
    {
        throw_if(
            ! is_null($this->appendText) || ! is_null($this->prependText),
            new Exception('Method "currency" cannot be used in combination with "prependText" or "appendText".')
        );

        $this->currency = Innoclapps::currency($currency);

        return $this;
    }

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        $table->decimal($fieldId, 15, 3)->index()->nullable();
    }

    /**
     * Set the numeric field decimal precision, applicable when no currency is provided.
     */
    public function precision(int $precision): static
    {
        $this->withMeta(['attributes' => ['precision' => $precision]]);

        return $this;
    }

    /**
     * The function formats a numeric field for .csv export
     * The format will be performed with the currency iso code
     * for better readibility and e.q. usage on other systems
     *
     * @param  \Modules\Core\App\Models\Model  $model
     * @return string
     */
    public function resolveForExport($model)
    {
        if (is_callable($this->exportCallback)) {
            return call_user_func_array($this->exportCallback, [$model, $this->resolve($model), $this->attribute]);
        }

        $value = $this->resolve($model);

        if (is_null($value)) {
            $value = 0;
        }

        if (! $this->currency) {
            return $value;
        }

        $currency = $this->currency;

        $negative = $value < 0;
        $currencyName = $currency->getCurrency();

        $amount = $negative ? -$value : $value;
        $thousands = $currency->getThousandsSeparator();
        $decimals = $currency->getDecimalMark();

        $prefix = ! $currency->isSymbolFirst() ? '' : $currencyName;
        $suffix = $currency->isSymbolFirst() ? '' : ' '.$currencyName;

        $value = number_format($amount, $currency->getPrecision(), $decimals, $thousands);

        return ($negative ? '-' : '').$prefix.$value.$suffix;
    }

    /**
     * Unformat the given number
     *
     * @param  mixed  $number
     * @param  bool  $forceNumber
     * @param  string  $decimalPoint
     * @param  string  $thousandSeparator
     * @return mixed
     */
    protected function unformatNumber($number, $forceNumber = true, $decimalPoint = '.', $thousandSeparator = ',')
    {
        if ($forceNumber) {
            $number = preg_replace('/^[^\d]+/', '', $number);
        } elseif (preg_match('/^[^\d]+/', $number)) {
            return null;
        }

        $type = (strpos($number, $decimalPoint) === false) ? 'int' : 'float';
        $number = str_replace([$decimalPoint, $thousandSeparator], ['.', ''], $number);

        settype($number, $type);

        return $number;
    }

    /**
     * Ensure that the given value is parsed.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function parsePreValidationValue($value)
    {
        if (is_null($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        // First, we will check if the value starts with currency
        $startingCurrency = substr(trim($value), 0, 3);
        $currencyCode = null;
        if (is_string($startingCurrency) && ctype_alpha($startingCurrency)) {
            $currencyCode = $startingCurrency;
        } else {
            // If the value does not starts with currency
            // let's check if it ends with currency
            $endingCurrency = substr(trim($value), -3);
            if (is_string($endingCurrency) && ctype_alpha($endingCurrency)) {
                $currencyCode = $endingCurrency;
            }
        }

        // If currency is found, we will strip the currency code from the
        // value and return only the unformatted number for storage
        if ($currencyCode && $currency = Currency::getCurrencies()[strtoupper($currencyCode)] ?? null) {
            return $this->unformatNumber($value, true, $currency['decimal_mark'], $currency['thousands_separator']);
        }

        return $value;
    }

    /**
     * Serialize for front end
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'currency' => $this->currency ? with($this->currency, function ($currency) {
                return array_merge(
                    $currency->toArray()[$isoCode = $currency->getCurrency()],
                    ['iso_code' => $isoCode]
                );
            }) : null,
            'prependText' => $this->prependText,
            'appendText' => $this->appendText,
        ]);
    }
}
