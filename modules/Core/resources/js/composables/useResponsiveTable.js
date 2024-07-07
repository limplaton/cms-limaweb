
import { onBeforeUnmount } from 'vue'
// We will store the columns elements and their rect in a WeakMap instance because the column may
// get hidden and getBoundingClientRect values will be zero, in this case, if the column is
// re-positioned, the cache must be cleared but it won't work again if the column is hidden

export function useResponsiveTable() {
  let columnsElementsCache = new WeakMap()

  const clearCache = function () {
    columnsElementsCache = new WeakMap()
  }

  const isColumnVisible = function (el, container, fullVisible = true) {
    if (el.tagName == 'HTML') return true
    let parentRect = container.getBoundingClientRect()
    let rect, elParentNode

    if (columnsElementsCache.has(el)) {
      const cache = columnsElementsCache.get(el)
      rect = cache.rect
      elParentNode = cache.parentNode
    } else {
      rect = arguments[3] || el.getBoundingClientRect()
      elParentNode = el.parentNode

      columnsElementsCache.set(el, {
        rect: rect,
        parentNode: elParentNode,
      })
    }

    return (
      (fullVisible
        ? rect.left >= parentRect.left
        : rect.right > parentRect.left) &&
      (fullVisible
        ? rect.right <= parentRect.right
        : rect.left < parentRect.right)
      // && isColumnVisible(elParentNode, container, fullVisible, rect)
    )
  }

  onBeforeUnmount(clearCache)

  return {
    isColumnVisible,
    clearCache,
  }
}
