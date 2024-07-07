<template>
  <IFormInput
    ref="inputRef"
    v-model="amount"
    type="tel"
    :placeholder="placeholder"
    :disabled="disabled"
    @update:model-value="processAmountNumber"
    @blur="onBlurHandler"
    @focus="onFocusHandler"
  />
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useTimeoutFn } from '@vueuse/core'
import { formatMoney, toFixed, unformat } from 'accounting-js'

import IFormInput from './IFormInput.vue'

const props = defineProps({
  /**
   * Currency symbol.
   */
  currency: { type: String, default: '' },

  /**
   * Maximum value allowed.
   */
  max: { type: Number, default: Number.MAX_SAFE_INTEGER || 9007199254740991 },

  /**
   * Minimum value allowed.
   */
  min: { type: Number, default: Number.MIN_SAFE_INTEGER || -9007199254740991 },

  /**
   * Enable/Disable minus value.
   */
  minus: Boolean,

  /**
   * Input placeholder.
   */
  placeholder: { type: String, default: '' },

  /**
   * Value when the input is empty
   */
  emptyValue: { type: [Number, String], default: '' },

  /**
   * Number of decimals.
   * Decimals symbol are the opposite of separator symbol.
   */
  precision: {
    type: Number,
    default: () => Number(Innoclapps.scriptConfig('currency.precision')),
  },

  /**
   * Thousand separator type.
   * Separator props accept either . or , (default).
   */
  separator: { type: String, default: ',' },

  /**
   * Forced thousand separator.
   * Accepts any string.
   */
  thousandSeparator: {
    default: () => Innoclapps.scriptConfig('currency.thousands_separator'),
    type: String,
  },

  /**
   * Forced decimal separator.
   * Accepts any string.
   */
  decimalSeparator: {
    default: () => Innoclapps.scriptConfig('currency.decimal_mark'),
    type: String,
  },
  /**
   * The output type used for v-model.
   * It can either be String or Number (default).
   */
  outputType: { type: String, default: 'Number' },

  /**
   * v-model value.
   */
  modelValue: { type: [Number, String], default: '' },

  disabled: Boolean,

  /**
   * Position of currency symbol
   * Symbol position props accept either 'suffix' or 'prefix' (default).
   */
  currencySymbolPosition: { type: String, default: 'prefix' },
})

const emit = defineEmits(['blur', 'focus', 'update:modelValue'])

const amount = ref('')
const inputRef = ref(null)

/**
 * Number type of formatted value.
 * @return {Number}
 */
const amountNumber = computed(() => unformatValue(amount.value))

/**
 * Number type of value props.
 * @return {Number}
 */
const valueNumber = computed(() => unformatValue(props.modelValue, '.'))

/**
 * Define decimal separator based on separator props.
 * @return {String} '.' or ','
 */
const decimalSeparatorSymbol = computed(() => {
  if (typeof props.decimalSeparator !== 'undefined')
    return props.decimalSeparator
  if (props.separator === ',') return '.'

  return ','
})

/**
 * Define thousand separator based on separator props.
 * @return {String} '.' or ','
 */
const thousandSeparatorSymbol = computed(() => {
  if (typeof props.thousandSeparator !== 'undefined')
    return props.thousandSeparator
  if (props.separator === '.') return '.'
  if (props.separator === 'space') return ' '

  return ','
})

/**
 * Define format position for currency symbol and value.
 * @return {String} format
 */
const symbolPosition = computed(() => {
  if (!props.currency) return '%v'

  return props.currencySymbolPosition === 'suffix' ? '%v %s' : '%s %v'
})

/**
 * Watch for value change from other input with same v-model.
 * @param {Number} newValue
 */
watch(
  valueNumber,
  newVal => {
    if (inputRef.value.inputRef !== document.activeElement) {
      amount.value = format(newVal)
    }
  },
  { flush: 'post' }
)

/**
 * Immediately reflect props changes
 */
watch(
  [() => props.separator, () => props.currency, () => props.precision],
  () => {
    process(valueNumber.value)
    amount.value = format(valueNumber.value)
  }
)

onMounted(() => {
  // Set default value props when valueNumber has some value
  if (valueNumber.value || isDeliberatelyZero()) {
    process(valueNumber.value)
    amount.value = format(valueNumber.value)

    // In case of delayed props value.
    useTimeoutFn(() => {
      process(valueNumber.value)
      amount.value = format(valueNumber.value)
    }, 500)
  }
})

/**
 * Handle blur event.
 * @param {Object} e
 */
function onBlurHandler(e) {
  emit('blur', e)
  amount.value = format(valueNumber.value)
}

/**
 * Handle focus event.
 * @param {Object} e
 */
function onFocusHandler(e) {
  emit('focus', e)

  if (valueNumber.value === 0) {
    amount.value = null
  } else {
    amount.value = formatMoney(valueNumber.value, {
      symbol: '',
      format: '%v',
      thousand: '',
      decimal: decimalSeparatorSymbol.value,
      precision: Number(props.precision),
    })
  }
}

/**
 * Process the changed amount.
 */
function processAmountNumber() {
  process(amountNumber.value)
}

/**
 * Validate value before update the component.
 * @param {Number} value
 */
function process(value) {
  if (value >= props.max) update(props.max)
  if (value <= props.min) update(props.min)
  if (value > props.min && value < props.max) update(value)
  if (!props.minus && value < 0) props.min >= 0 ? update(props.min) : update(0)
}

/**
 * Update parent component model value.
 * @param {Number} value
 */
function update(value) {
  const fixedValue = toFixed(value, props.precision)

  const output =
    props.outputType.toLowerCase() === 'string'
      ? fixedValue
      : Number(fixedValue)

  emit('update:modelValue', output)
}

/**
 * Format value using symbol and separator.
 * @param {Number} value
 * @return {String}
 */
function format(value) {
  return formatMoney(value, {
    symbol: props.currency,
    format: symbolPosition.value,
    precision: Number(props.precision),
    decimal: decimalSeparatorSymbol.value,
    thousand: thousandSeparatorSymbol.value,
  })
}

/**
 * Remove symbol and separator.
 * @param {Number} value
 * @param {String} decimalSeparator
 * @return {Number}
 */
function unformatValue(value, decimalSeparator) {
  const toUnformat =
    typeof value === 'string' && value === '' ? props.emptyValue : value

  return unformat(toUnformat, decimalSeparator || decimalSeparatorSymbol.value)
}

/**
 * Check if value was deliberately set to zero and not just evaluated
 * @return {boolean}
 */
function isDeliberatelyZero() {
  return valueNumber.value === 0 && props.modelValue !== ''
}
</script>
