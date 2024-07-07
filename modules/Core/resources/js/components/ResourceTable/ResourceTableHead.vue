<template>
  <ITableHead>
    <slot />
  </ITableHead>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { nextTick } from 'vue'
import { useParentElement } from '@vueuse/core'

const props = defineProps({
  columns: { type: Array, required: true },
  isLoaded: { type: Boolean, required: true },
  disabled: { type: Boolean, default: true },
})

const emit = defineEmits(['resized'])

const tableEl = useParentElement()
const totalCols = computed(() => props.columns.length)
const colsWidthsKey = computed(() => props.columns.map(c => c.width).join('|'))

let pageX,
  oldX = 0,
  curCol,
  curColIndex,
  nextCol,
  curColWidth,
  nextColWidth

function getHeadings() {
  return Array.prototype.slice.call(
    tableEl.value.querySelectorAll('thead>tr>th')
  )
}

function getGrips() {
  return tableEl.value.querySelectorAll('.resizer')
}

function setHeadingsWidth() {
  if (!tableEl.value) {
    return
  }

  let tableWidth = 0
  let headings = getHeadings()

  // Allow auto resizing.
  tableEl.value.style.width = null
  tableEl.value.style.minWidth = null

  // Then set the width to auto or defined width so the browser can perform re-calculation by itself.
  for (let i = 0; i < headings.length; i++) {
    headings[i].style.width = props.columns[i].width
  }

  if (props.disabled) {
    return
  }

  // After that, set their actual width based on how they are rendered.
  for (let i = 0; i < headings.length; i++) {
    let minWidth = getColMinWidth(i)

    let width = removePx(getStyleVal(headings[i], 'width'))

    // Is hidden in a scrollbar?
    if (width === 0) {
      width = getActualWidth(headings[i])
    }

    if (minWidth && width < minWidth) {
      width = minWidth
    }

    if (!isActionsCol(headings[i])) {
      headings[i].style.width = width + 'px'
    }

    tableWidth += width
  }

  tableEl.value.style.width = tableWidth + 'px'
  tableEl.value.style.minWidth = '100%'
}

function isActionsCol(elm) {
  return elm.classList.contains('table-actions-column')
}

function createGripDiv() {
  let div = document.createElement('div')

  div.classList.add(
    'resizer',
    'absolute',
    'w-[0.2rem]',
    'h-full',
    'top-0',
    'right-0',
    'cursor-col-resize',
    'select-none',
    'group-hover/th:bg-neutral-400',
    'dark:group-hover/th:bg-neutral-600'
  )

  return div
}

function init() {
  if (props.disabled) return

  let headings = getHeadings()
  if (!headings.length) return

  setHeadingsWidth()
  let totalHeadings = headings.length

  //   let lastResizableColumnIndex = isActionsCol(headings[totalHeadings - 1])
  //     ? totalHeadings - 2
  //     : totalHeadings - 1

  for (let i = 0; i < totalHeadings; i++) {
    // if (i !== lastResizableColumnIndex) {
    //   continue;
    // }

    if (!props.columns[i].customizeable) {
      continue
    }

    let grip = createGripDiv()
    headings[i].appendChild(grip)

    grip.addEventListener('mousedown', onMouseDownHandler)
  }

  document.addEventListener('mousemove', onMouseMoveHandler, { passive: true })
  document.addEventListener('mouseup', onMouseUpHandler, { passive: true })
}

function destroy() {
  document.removeEventListener('mousemove', onMouseMoveHandler)
  document.removeEventListener('mouseup', onMouseUpHandler)

  if (tableEl.value) {
    getGrips().forEach(grip => {
      grip.removeEventListener('mousedown', onMouseDownHandler)
      grip.remove()
    })
  }
}

function onMouseDownHandler(e) {
  curCol = e.target.parentElement
  curColIndex = getHeadings().indexOf(curCol)
  nextCol = curCol.nextElementSibling

  curCol.querySelector('.resizer').classList.add('resizing')

  if (nextCol && isActionsCol(nextCol)) {
    nextCol = null
  }

  pageX = e.pageX

  curColWidth = curCol.offsetWidth

  if (nextCol) {
    nextColWidth = nextCol.offsetWidth
  }
}

function isOverflowing(elem) {
  const elemWidth = elem.getBoundingClientRect().width
  const parentWidth = elem.parentElement.getBoundingClientRect().width

  return elemWidth > parentWidth
}

function onMouseMoveHandler(e) {
  if (!curCol) {
    return
  }

  let diffX = e.pageX - pageX
  let moveDirection = ''

  if (e.pageX < oldX) {
    moveDirection = 'left'
  } else if (e.pageX > oldX) {
    moveDirection = 'right'
  }

  oldX = e.pageX

  let curColFinalWidth,
    minWidth = getColMinWidth(curColIndex),
    minWidthReached = false,
    curColNewWidth = curColWidth + diffX

  if (!minWidth || curColNewWidth >= minWidth) {
    curColFinalWidth = curColNewWidth
  } else {
    curColFinalWidth = minWidth
    minWidthReached = true
  }

  if (!minWidthReached && nextCol) {
    let nextColNewWidth = nextColWidth - diffX

    let nextColMinWidth = getColMinWidth(curColIndex + 1)

    if (!nextColMinWidth || nextColNewWidth >= nextColMinWidth) {
      nextCol.style.width = nextColNewWidth + 'px'
    }
  } else if (
    moveDirection === 'left' &&
    !nextCol &&
    isOverflowing(tableEl.value)
  ) {
    // Shrink the table width if is last column and it's overflowing, gives the ability
    // for the user to remove the scrollbar by adjusting the size of the columns.
    tableEl.value.style.width =
      removePx(tableEl.value.style.width) + diffX + 'px'
  }

  curCol.style.width = curColFinalWidth + 'px'
}

// eslint-disable-next-line no-unused-vars
function onMouseUpHandler(e) {
  if (curCol) {
    curCol.querySelector('.resizer').classList.remove('resizing')

    emitResizedEvent()
  }

  curCol = undefined
  nextCol = undefined
  pageX = undefined
  oldX = undefined
  nextColWidth = undefined
  curColWidth = undefined
  curColIndex = undefined
}

function emitResizedEvent() {
  emit(
    'resized',
    props.columns.map((column, index) => ({
      ...column,
      width: getHeadingWidthByIndex(index),
    }))
  )
}

function getColMinWidth(index) {
  return props.columns[index].minWidth
    ? removePx(props.columns[index].minWidth)
    : null
}

function getStyleVal(elm, css) {
  return window.getComputedStyle(elm, null).getPropertyValue(css)
}

function getHeadingWidthByIndex(index) {
  return getStyleVal(getHeadings()[index], 'width')
}

/**
 * Calculates the actual width of an element, even if it's currently hidden or not in the viewport.
 *
 * This function is useful for getting the width of the heading that are hidden and
 * outside of the visible scrolling area, for example, hidden in responsive table scrollbar.
 *
 * Since `offsetWidth` returns 0 for elements that are not, rendered or visible in the document layout,
 * this function temporarily alters the element's styles to
 * make it visible off-screen, measures its width, and then restores its original styles.
 *
 * @param {HTMLElement} element - The element whose width is to be measured.
 * @returns {number} The width of the element in pixels.
 */
function getActualWidth(element) {
  const originalStyle = {
    display: element.style.display,
    position: element.style.position,
    visibility: element.style.visibility,
    left: element.style.left,
  }

  // Temporarily modify styles to make the element visible, but off-screen
  element.style.display = 'block'
  element.style.position = 'absolute'
  element.style.visibility = 'hidden'
  element.style.left = '-9999px'

  const width = element.offsetWidth

  // Restore the original styles
  element.style.display = originalStyle.display
  element.style.position = originalStyle.position
  element.style.visibility = originalStyle.visibility
  element.style.left = originalStyle.left

  return width
}

function removePx(width) {
  return Number(width.replace('px', '')).valueOf()
}

async function initAfterNextTick() {
  await nextTick()
  destroy()
  init()
}

watch(
  colsWidthsKey,
  () => {
    setHeadingsWidth()
  },
  { flush: 'post' }
)

watch(
  () => props.isLoaded,
  loaded => {
    if (loaded) {
      initAfterNextTick()
    }
  },
  { flush: 'post', immediate: true }
)

watch(
  totalCols,
  newVal => {
    if (newVal > 0) {
      initAfterNextTick()
    }
  },
  {
    flush: 'post',
  }
)

onBeforeUnmount(destroy)
onMounted(() => props.disabled && setHeadingsWidth())
</script>

<style>
table th:has(.resizing) {
  @apply pointer-events-none;
}
table th > .resizing {
  @apply bg-neutral-400 dark:bg-neutral-600;
}
</style>
