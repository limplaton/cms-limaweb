
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { DateTime, Duration, Settings } from 'luxon'

import { getLocale } from '../utils'

import { useApp } from './useApp'

export function useDates() {
  const { scriptConfig, currentUser, setting } = useApp()
  const { t } = useI18n()

  /**
   * Get the user's local timezone.
   *
   * @type {import('vue').ComputedRef<string>}
   */
  const userTimezone = computed(
    () => currentUser.value?.timezone || guessTimezone()
  )

  /**
   * Determine if the user is using 12-hour time.
   *
   * @type {import('vue').ComputedRef<boolean>}
   */
  const usesTwelveHourTime = computed(
    () => currentTimeFormat.value.indexOf('H:i') === -1
  )

  /**
   * Determine the application current date format.
   *
   * @type {import('vue').ComputedRef<string>}
   */
  const currentDateFormat = computed(
    () => currentUser.value?.date_format || setting('date_format')
  )

  /**
   * Determine the application current date and time format.
   *
   * @type {import('vue').ComputedRef<string>}
   */
  const currentDateTimeFormat = computed(() =>
    [currentDateFormat.value, currentTimeFormat.value].join(' ')
  )

  /**
   * Determine the application current time format.
   *
   * @type {import('vue').ComputedRef<string>}
   */
  const currentTimeFormat = computed(
    () => currentUser.value?.time_format || setting('time_format')
  )

  /**
   * @type {import('vue').ComputedRef<string>}
   */
  const timeFormatForLuxon = computed(() =>
    convertPhpToLuxonFormat(currentTimeFormat.value)
  )

  /**
   * Get a localized date and time.
   *
   * @param {string} value Date in ISO format.
   * @param {string|undefined} format One of the available PHP formats.
   * @returns {string}
   */
  function localizedDateTime(value, format) {
    if (!value) return value

    format = format || currentDateTimeFormat.value
    const luxonFormat = convertPhpToLuxonFormat(format)
    let dateTimeInstance

    if (isISODate(value)) {
      dateTimeInstance = DateTime.fromISO(value)
    } else if (isStandardDateTime(value)) {
      dateTimeInstance = DateTime.fromFormat(value, 'yyyy-MM-dd HH:mm:ss')
    } else if (value instanceof Date) {
      dateTimeInstance = DateTime.fromJSDate(value)
    } else {
      // Cannot recognize format.
      return value
    }

    return amPMToLowerOrUpper(
      dateTimeInstance.setZone(userTimezone.value).toFormat(luxonFormat),
      format
    )
  }

  /**
   * Get a localized date.
   *
   * @param {string} value Date in ISO format.
   * @param {string|undefined} format One of the available PHP formats.
   * @returns {string}
   */
  function localizedDate(value, format) {
    if (!value) return value

    const luxonFormat = convertPhpToLuxonFormat(
      format || currentDateFormat.value
    )

    let dateTimeInstance

    if (isISODate(value)) {
      dateTimeInstance = DateTime.fromISO(value)
    } else if (isStandardDateTime(value)) {
      dateTimeInstance = DateTime.fromFormat(value, 'yyyy-MM-dd HH:mm:ss')
    } else if (isStandardDate(value)) {
      dateTimeInstance = DateTime.fromFormat(value, 'yyyy-MM-dd')
    } else if (value instanceof Date) {
      dateTimeInstance = DateTime.fromJSDate(value)
    } else {
      // cannot recognize format.
      return value
    }

    return dateTimeInstance.setZone(userTimezone.value).toFormat(luxonFormat)
  }

  /**
   * Get a localized time.
   *
   * @param {string} value Date in ISO format.
   * @param {string|undefined} format One of the available PHP formats.
   * @returns {string}
   */
  function localizedTime(value, format) {
    if (!value) return value

    format = format || currentTimeFormat.value
    const luxonFormat = convertPhpToLuxonFormat(format)
    let dateTimeInstance

    if (isISODate(value)) {
      dateTimeInstance = DateTime.fromISO(value)
    } else if (isStandardDateTime(value)) {
      dateTimeInstance = DateTime.fromFormat(value, 'yyyy-MM-dd HH:mm:ss')
    } else if (value instanceof Date) {
      dateTimeInstance = DateTime.fromJSDate(value)
    } else {
      // cannot recognize format.
      return value
    }

    return amPMToLowerOrUpper(
      dateTimeInstance.setZone(userTimezone.value).toFormat(luxonFormat),
      format
    )
  }

  /**
   * Determine if the given string is date in ISO format.
   *
   * @param {string} str
   * @returns {boolean}
   */
  function isISODate(str) {
    if (typeof str !== 'string') {
      return false
    }

    // Matches ISO 8601 format with 3 or 6 digits for milliseconds,
    // e.g., 2020-04-02T03:39:56.123Z or 2020-04-02T03:39:56.123456Z or with timezone offset
    return /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3,6}(Z|[+-]\d{2}:\d{2})/.test(
      str
    )
  }

  /**
   * Determine if the given string is standard date time format.
   *
   * @param {string} str
   * @returns {boolean}
   */
  function isStandardDateTime(str) {
    // First perform the checks below, less IQ
    if (typeof str !== 'string') {
      return false
    }

    if (![' ', ':', '-'].some(t => str.indexOf(t) > -1)) {
      return false
    }

    return /\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(str)
  }

  /**
   * Determine if the given string is standard date.
   *
   * @param {string} str
   */
  function isStandardDate(str) {
    if (typeof str !== 'string' || str.indexOf('-') === 1) {
      return false
    }

    return /\d{4}-\d{2}-\d{2}$/.test(str)
  }

  /**
   * Determine if the given UTC date is on midnight.
   * @param {string} date
   * @returns {boolean}
   */
  function isMidnight(UTCDate) {
    return /\d{4}-\d{2}-\d{2}[T\s]?00:00:00(\.\d{3,6}(Z|[+-]\d{2}:\d{2}))?/.test(
      UTCDate
    )
  }

  /**
   * Format Input If It's a Recognized Date Format
   *
   * This function checks if the provided value is in a recognized date format and,
   * if so, formats it accordingly. It specifically handles:
   * - ISO date strings, formatting them as localized dates if they represent
   *   midnight in UTC (ending in T00:00:00.000000Z), or as localized date-times otherwise.
   * - Standard date or date-time strings, formatting them as localized dates or
   *   date-times respectively.
   * If the value is not in a recognized date format, a specified fallback value is returned.
   *
   * @param {string} value - The value to potentially format as a date.
   * @param {any} [fallbackValue=null] - The fallback value to return if the value is not a recognized date format.
   * @return {string|any} - The formatted date string if the value is a recognized date format, or the fallback value otherwise.
   */
  function localizeIfDate(value, fallbackValue = null) {
    if (isISODate(value)) {
      return isMidnight(value) ? localizedDate(value) : localizedDateTime(value)
    } else if (isStandardDate(value)) {
      return localizedDate(value)
    } else if (isStandardDateTime(value)) {
      return localizedDateTime(value)
    }

    return fallbackValue
  }

  /**
   * Determine if the given date string has time.
   *
   * @param {string} dateString
   * @returns {boolean}
   */
  function hasTime(dateString) {
    return /(\d{2}:\d{2}:\d{2})|(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})/.test(
      dateString
    )
  }

  /**
   * Guess the current timezone.
   *
   * @returns {string}
   */
  function guessTimezone() {
    return DateTime.local().zoneName
  }

  /**
   * @param {number} seconds
   * @returns {string}
   */
  function humanizeDuration(seconds) {
    const dur = Duration.fromObject({ seconds: seconds })

    if (seconds < 60) {
      return t('core::dates.duration.seconds')
    } else if (seconds < 3600) {
      let minutes = Math.round(dur.as('minutes'))

      return minutes + ' ' + t('core::dates.duration.minutes', minutes)
    } else if (seconds < 86400) {
      let hours = Math.round(dur.as('hours'))

      return hours + ' ' + t('core::dates.duration.hours', hours)
    } else {
      let days = Math.round(dur.as('days'))

      return days + ' ' + t('core::dates.duration.days', days)
    }
  }

  /**
   * Check if the given javascript dates are on the different day.
   *
   * @param {Date} date1
   * @param {Date} date2
   * @returns {boolean}
   */
  function onDifferentDay(date1, date2) {
    const year1 = date1.getFullYear()
    const month1 = date1.getMonth()
    const day1 = date1.getDate()

    const year2 = date2.getFullYear()
    const month2 = date2.getMonth()
    const day2 = date2.getDate()

    return year1 !== year2 || month1 !== month2 || day1 !== day2
  }

  /**
   * Luxon does not provide ability to format am/pm to lowercase or uppercase.
   * In this case, we will custom check if the original PHP format includes to a or A token
   * and perform any modification of the formatted date.
   *
   * @param {string} date
   * @param {string} format
   * @returns {string}
   */
  function amPMToLowerOrUpper(date, format) {
    if (format.includes('A')) {
      return date.replace(/AM|PM/, match => match.toUpperCase())
    } else if (format.includes('a')) {
      return date.replace(/AM|PM/, match => match.toLowerCase())
    }

    return date
  }

  // Perform a test if the locale is valid for luxon,
  // otherwise fallback to the config fallback locale (en).
  try {
    Settings.defaultLocale = getLocale().replace('_', '-')
    Settings.defaultZone = userTimezone.value
    DateTime.now().toLocaleString()
  } catch (e) {
    if (e.message == 'Incorrect locale information provided') {
      Settings.defaultLocale = scriptConfig('fallback_locale')
    }
  }

  return {
    DateTime, // first local, create UTC instance using DateTime.utc()
    UTCDateTimeInstance: DateTime.now().setZone(scriptConfig('timezone')),
    LocalDateTimeInstance: DateTime.now(),
    localizedDate,
    localizedDateTime,
    localizedTime,
    userTimezone,
    usesTwelveHourTime,
    timeFormatForLuxon,
    humanizeDuration,
    localizeIfDate,
    guessTimezone,
    hasTime,
    isMidnight,
    isISODate,
    isStandardDateTime,
    isStandardDate,
    onDifferentDay,
  }
}

/**
 * @param {string} phpFormat
 * @returns {string}
 */
function convertPhpToLuxonFormat(phpFormat) {
  const formatMap = {
    // Days
    d: 'dd',
    D: 'ccc',
    j: 'd',
    l: 'cccc',
    N: 'E',
    S: 'o',
    w: 'c',
    z: 'o',
    // Weeks
    W: 'W',
    // Months
    F: 'MMMM',
    m: 'MM',
    M: 'MMM',
    n: 'M',
    t: '',
    // Years
    L: '',
    o: 'kkkk',
    Y: 'yyyy',
    y: 'yy',
    // Time
    a: 'a',
    A: 'a',
    g: 'h',
    G: 'H',
    h: 'hh',
    H: 'HH',
    i: 'mm',
    s: 'ss',
    u: 'SSS',
    // Timezone
    e: 'z',
    I: '',
    O: 'ZZ',
    P: 'ZZZZ',
    T: 'zz',
    Z: '',
    // Full Date/Time
    c: "yyyy-MM-dd'T'HH:mm:ssZZZZ",
    r: 'EEE, dd MMM yyyy HH:mm:ss ZZ',
    U: 'X',
    // Other characters
    ',': ',',
    ' ': ' ',
    '.': '.',
    '/': '/',
    '-': '-',
    ':': ':',
  }

  return phpFormat
    .split('')
    .map(character => formatMap[character] || character)
    .join('')
}
