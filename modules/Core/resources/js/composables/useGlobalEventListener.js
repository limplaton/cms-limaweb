
import { onUnmounted } from 'vue'
import castArray from 'lodash/castArray'

/**
 * @param  {String|Array} events
 * @param  {Function} callback
 */
export function useGlobalEventListener(events, callback) {
  castArray(events).forEach(eventName => {
    Innoclapps.$on(eventName, callback)

    onUnmounted(() => {
      Innoclapps.$off(eventName, callback)
    })
  })
}

/**
 * @param  {String} eventName
 * @param  {Mixed} params
 */
export function emitGlobal(...args) {
  Innoclapps.$emit(...args)
}
