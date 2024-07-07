
import { computed, ref, unref, watch, watchEffect } from 'vue'

export function useTypeAheadPointer(
  options,
  selectable,
  autoscroll,
  dropdownMenuRef,
  select
) {
  const typeAheadPointer = ref(-1)
  let isSelectable = null

  const totalOptions = computed(() => options.value.length)

  watchEffect(() => {
    isSelectable = unref(selectable)
  })

  watch(typeAheadPointer, () => {
    if (unref(autoscroll)) {
      maybeAdjustScroll()
    }
  })

  watch(totalOptions, newVal => {
    for (let i = 0; i < newVal; i++) {
      if (isSelectable(unref(options)[i])) {
        typeAheadPointer.value = i
        break
      }
    }
  })

  /**
   * Move the typeAheadPointer visually up the list by
   * setting it to the previous selectable option.
   */
  function typeAheadUp() {
    for (let i = typeAheadPointer.value - 1; i >= 0; i--) {
      if (isSelectable(unref(options)[i])) {
        typeAheadPointer.value = i
        break
      }
    }
  }

  /**
   * Move the typeAheadPointer visually down the list by
   * setting it to the next selectable option.
   */
  function typeAheadDown() {
    for (let i = typeAheadPointer.value + 1; i < totalOptions.value; i++) {
      if (isSelectable(unref(options)[i])) {
        typeAheadPointer.value = i
        break
      }
    }
  }

  /**
   * Select the option at the current typeAheadPointer position.
   * Optionally clear the search input on selection.
   */
  function typeAheadSelect() {
    const typeAheadOption = unref(options)[typeAheadPointer.value]

    if (typeAheadOption) {
      select(typeAheadOption)
    }
  }

  /**
   * Adjust the scroll position of the dropdown list if the current pointer is outside of the overflow bounds.
   */
  function maybeAdjustScroll() {
    const optionEl =
      dropdownMenuRef.value?.targetDomElement.children[
      typeAheadPointer.value
      ] || false

    if (optionEl) {
      const bounds = getDropdownViewport()
      const { top, bottom, height } = optionEl.getBoundingClientRect()

      if (top < bounds.top) {
        return (dropdownMenuRef.value.targetDomElement.scrollTop =
          optionEl.offsetTop)
      } else if (bottom > bounds.bottom) {
        return (dropdownMenuRef.value.targetDomElement.scrollTop =
          optionEl.offsetTop - (bounds.height - height))
      }
    }
  }

  /**
   * The currently viewable portion of the dropdownMenu.
   */
  function getDropdownViewport() {
    return dropdownMenuRef.value
      ? dropdownMenuRef.value.targetDomElement.getBoundingClientRect()
      : {
        height: 0,
        top: 0,
        bottom: 0,
      }
  }

  return {
    typeAheadPointer,
    typeAheadSelect,
    typeAheadDown,
    typeAheadUp,
  }
}
