
/**
 * Get the passive third arguemtn and check whether the browser supports it
 *
 * @returns {boolean|object}
 */
function passiveEventArg() {
  // Cache checks
  if (Object.hasOwn(window, '__passiveEvt')) {
    return window.__passiveEvt
  }

  let result = false

  try {
    const arg = Object.defineProperty({}, 'passive', {
      get() {
        result = {
          passive: true,
        }

        return true
      },
    })

    window.addEventListener('testpassive', arg, arg)
    window.remove('testpassive', arg, arg)
  } catch (e) {
    /* */
  }

  window.__passiveEvt = result

  return result
}

export default passiveEventArg
