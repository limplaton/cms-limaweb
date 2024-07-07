
import { formatMoney, formatNumber, toFixed } from 'accounting-js'

import { useApp } from './useApp'

export function useAccounting() {
  const { scriptConfig } = useApp()

  /**
   * Formats a number with specified formatting options. It allows setting the
   * precision, thousands separator, and decimal mark. Default options are derived
   * from script configurations for currency but can be overridden.
   *
   * @param {number} value - The numeric value to be formatted.
   * @param {Object} [options={}] - Optional formatting options to override default settings.
   * @returns {string} - The formatted number as a string.
   */
  function _formatNumber(value, options = {}) {
    return formatNumber(
      value,
      Object.assign(
        {
          precision: scriptConfig('currency.precision'),
          thousand: scriptConfig('currency.thousands_separator'),
          decimal: scriptConfig('currency.decimal_mark'),
        },
        options
      )
    )
  }

  /**
   * Formats a numeric value into a currency string. It customizes the format based
   * on the provided currency settings or default script configurations. Settings
   * include currency symbol, precision, thousands separator, decimal mark, and the
   * position of the currency symbol.
   *
   * @param {number} value - The monetary value to be formatted.
   * @param {Object|null} [currency=null] - Optional currency settings to use for formatting.
   * @returns {string} - The formatted monetary value as a string.
   */
  function _formatMoney(value, currency = null) {
    currency = currency || scriptConfig('currency')

    return formatMoney(value, {
      symbol: currency.symbol,
      precision: currency.precision,
      thousand: currency.thousands_separator,
      decimal: currency.decimal_mark,
      format: currency.symbol_first == true ? '%s%v' : '%v%s',
    })
  }

  return {
    toFixed,
    formatNumber: _formatNumber,
    formatMoney: _formatMoney,
  }
}
