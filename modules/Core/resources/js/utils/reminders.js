
/**
 * Determine the type based on the given minutes
 *
 * @param  {number} minutes
 *
 * @returns {string}
 */
function determineReminderTypeBasedOnMinutes(minutes) {
  if (minutes < 59) {
    return 'minutes'
  } else if (minutes >= 10080) {
    return 'weeks'
  } else if (minutes >= 1440) {
    return 'days'
  }

  return 'hours'
}

/**
 * Determine the field value based on the given minutes
 *
 * @param  {number} minutes
 *
 * @returns {number}
 */
function determineReminderValueBasedOnMinutes(minutes) {
  const type = determineReminderTypeBasedOnMinutes(minutes)

  if (type === 'minutes') {
    return minutes
  } else if (type === 'hours') {
    return minutes / 60
  } else if (type === 'days') {
    return minutes / 1440
  } else if (type === 'weeks') {
    return minutes / 10080
  }
}

export {
  determineReminderTypeBasedOnMinutes,
  determineReminderValueBasedOnMinutes,
}
