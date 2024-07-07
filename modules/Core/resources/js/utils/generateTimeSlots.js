
import { DateTime, Duration } from 'luxon'

const generateTimeSlots = (
  desiredStartTime,
  interval,
  period,
  maxHour = null
) => {
  // Calculate the total number of periods in a day
  const oneDay = Duration.fromObject({ days: 1 })
  const periodDuration = Duration.fromObject({ [period]: interval })
  const periodsInADay = oneDay.as(period) / interval

  const slots = []
  let startTime = DateTime.fromFormat(desiredStartTime, 'hh:mm')

  if (maxHour) {
    maxHour = DateTime.fromFormat(maxHour + ':00', 'HH:mm')
  }

  for (let i = 0; i < periodsInADay; i++) {
    if (i !== 0) {
      startTime = startTime.plus(periodDuration)
    }

    if (!maxHour || (maxHour && startTime <= maxHour.startOf('hour'))) {
      slots.push(
        startTime.toISOTime({
          suppressSeconds: true,
          suppressMilliseconds: true,
          includeOffset: false,
        })
      )
    }
  }

  return slots
}

export default generateTimeSlots
