
import isEmpty from 'lodash/isEmpty'
import isNaN from 'lodash/isNaN'
import isNumber from 'lodash/isNumber'

function isBlank(value) {
  return (
    (isEmpty(value) && typeof value !== 'boolean' && !isNumber(value)) ||
    isNaN(value)
  )
}

export default isBlank
