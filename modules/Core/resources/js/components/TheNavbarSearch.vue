<template>
  <div class="flex w-full">
    <div class="relative flex w-full">
      <label for="navSearchInput" class="sr-only">Search</label>

      <div
        class="relative w-full text-neutral-400 focus-within:text-neutral-600 dark:focus-within:text-neutral-200"
      >
        <div
          class="pointer-events-none absolute inset-y-0 left-3 flex items-center sm:left-6"
        >
          <Icon
            icon="MagnifyingGlassSolid"
            :class="['size-5', isSearching ? 'animate-pulse' : '']"
          />
        </div>

        <input
          id="navSearchInput"
          ref="inputRef"
          v-model="searchValue"
          type="search"
          name="search"
          autocomplete="off"
          class="peer block h-full w-full appearance-none border-transparent bg-transparent py-2 pl-12 pr-10 text-base text-neutral-900 placeholder-neutral-500 focus:border-transparent focus:placeholder-neutral-400 focus:outline-none focus:ring-0 dark:text-neutral-200 dark:placeholder-neutral-400 dark:focus:placeholder-neutral-500 sm:pl-16 sm:pr-16 sm:text-sm [&::-webkit-search-cancel-button]:hidden"
          :placeholder="$t('core::app.search')"
          @click="showResult = true"
          @keydown.enter="performSearch(searchValue)"
        />

        <div
          ref="clearButtonWrapper"
          class="absolute left-0 top-[1.2rem]"
          :style="{ left: initialClearButtonGap }"
        >
          <IButton
            icon="XSolid"
            :style="{
              display: !hasSearchValue || isSearching ? 'none' : null,
            }"
            basic
            small
            @click="searchValue = ''"
          />

          <ISpinner
            v-if="isSearching"
            class="ml-1 mt-1 size-5 text-primary-500"
          />
        </div>

        <div
          v-if="shouldUseSearchKeyboardShortcut"
          v-memo="[shouldUseSearchKeyboardShortcut, hasSearchValue]"
          class="absolute left-56 top-[1.1rem] hidden peer-focus:hidden lg:block"
        >
          <kbd
            v-show="!hasSearchValue"
            class="inline-flex items-center rounded border border-neutral-300 px-2 font-sans text-sm font-bold text-neutral-500 dark:border-neutral-500 dark:text-neutral-400"
          >
            {{ keyboardShortcutMainKey }}&nbsp;{{ keyboardShortcutKey }}
          </kbd>
        </div>
      </div>
    </div>

    <TheNavbarSearchResult
      v-model:visible="showResult"
      :history="history"
      :result="result"
      @history-choosen="searchValue = $event"
      @history-removed="removeHistory"
    />
  </div>
</template>

<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  shallowRef,
  watch,
} from 'vue'
import { useStorage } from '@vueuse/core'
import filter from 'lodash/filter'

import { debounce, getTextWidth } from '@/Core/utils'

import TheNavbarSearchResult from './TheNavbarSearchResult.vue'

const globallySearchableResources = filter(
  Innoclapps.resources(),
  'globallySearchable'
)

const debounceWait = 700
const mediumDeviceBreakpoint = 768 // Width in pixels for medium devices
const initialClearButtonGap = 240

const shouldUseSearchKeyboardShortcut =
  !/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(window.navigator.userAgent)

const isMacintosh = /mac/.test(window.userAgent)
const keyboardShortcutMainKey = isMacintosh ? '⌘' : 'Ctrl'
const keyboardShortcutKey = 'K'

const historyLimit = 5
const history = useStorage('globalSearchHistory', [])

const result = shallowRef(
  globallySearchableResources.map(resource => ({
    icon: resource.icon,
    title: resource.label,
    data: [],
  }))
)

const inputRef = ref(null)
const clearButtonWrapper = ref(null)
const searchValue = ref('')
const showResult = ref(false)
const isSearching = ref(false)

const hasSearchValue = computed(() => searchValue.value !== '')

const performSearch = debounce(async function (q) {
  if (!q) {
    showResult.value = false

    return
  }

  isSearching.value = true

  try {
    const { data } = await Innoclapps.request('/search', {
      params: { q },
    })

    syncHistory(q)
    result.value = data
    showResult.value = true
  } finally {
    isSearching.value = false
  }
}, debounceWait)

function syncHistory(value) {
  let historyIndex = history.value.indexOf(value)

  if (historyIndex === -1) {
    history.value.unshift(value)
  } else if (historyIndex > 0) {
    removeHistory(historyIndex)
    history.value.unshift(value)
  }

  if (history.value.length > historyLimit) {
    history.value.pop()
  }
}

function removeHistory(index) {
  history.value.splice(index, 1)
}

function updateClearButtonPosition() {
  const textWidth = getTextWidth(
    inputRef.value.value,
    getComputedStyle(inputRef.value).font
  )

  const padding = 80

  if (window.innerWidth < mediumDeviceBreakpoint) {
    // On medium devices, always position the button at the edge of the input
    clearButtonWrapper.value.style.left =
      inputRef.value.clientWidth - clearButtonWrapper.value.offsetWidth + 'px'
  } else {
    // Calculate new position for the button
    let newLeftPosition = initialClearButtonGap

    // Move the button only if the text width plus padding reaches the initial gap
    if (textWidth + padding > initialClearButtonGap) {
      newLeftPosition = textWidth + padding
    }

    // Ensure the button does not go beyond the input's width minus a specific distance from the right edge
    newLeftPosition = Math.min(newLeftPosition, inputRef.value.clientWidth - 40)

    clearButtonWrapper.value.style.left = newLeftPosition + 'px'
  }
}

function escapeKeyDownHandler(e) {
  if (e.key === 'Escape' && showResult.value) {
    showResult.value = false
  }
}

function shortcutKeyDownHandle(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === keyboardShortcutKey.toLowerCase()) {
    e.preventDefault()

    if (document.activeElement !== inputRef.value) {
      // Move cursor at end of text
      if (searchValue.value) {
        const end = searchValue.value.length
        inputRef.value.setSelectionRange(end, end)
      }
      inputRef.value.focus()
      showResult.value = true
    }
  }
}

watch(searchValue, updateClearButtonPosition, { flush: 'post' })
watch(searchValue, performSearch)

onMounted(() => {
  window.addEventListener('resize', updateClearButtonPosition)
  window.addEventListener('keydown', escapeKeyDownHandler)

  if (shouldUseSearchKeyboardShortcut) {
    document.addEventListener('keydown', shortcutKeyDownHandle)
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateClearButtonPosition)
  window.removeEventListener('keydown', escapeKeyDownHandler)

  if (shouldUseSearchKeyboardShortcut) {
    window.removeEventListener('keydown', shortcutKeyDownHandle)
  }
})
</script>
