
let idCount = 0

/**
 * Simple unique ID implementation.
 * @return {number}
 */
function uniqueId() {
  return ++idCount
}

export default uniqueId
