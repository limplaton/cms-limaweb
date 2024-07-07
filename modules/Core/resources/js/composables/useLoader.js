
import { ref } from 'vue'

/**
 * Composable for managing loading states.
 *
 * @param {boolean} [defaultValue=false] - The default loading state.
 * @returns {{ setLoading: (value?: boolean) => void, isLoading: import('vue').Ref<boolean> }}
 */
export function useLoader(defaultValue = false) {
  const isLoading = ref(defaultValue)

  /**
   * Sets the loading state.
   *
   * @param {boolean} [value=true] - The new loading state.
   * @returns {void}
   */
  function setLoading(value = true) {
    isLoading.value = value
  }

  return { setLoading, isLoading }
}
