<template>
  <div
    class="border-b border-neutral-200 dark:border-neutral-500/30"
    data-slot="list"
  >
    <div :class="['flex items-center', { 'justify-center': centered }]">
      <a
        href="#"
        class="block px-1 text-neutral-500 dark:text-neutral-200 sm:hidden"
        :class="{ 'pointer-events-none opacity-50': scrolledToFirstTab }"
        @click.prevent="scrollLeft"
      >
        <Icon icon="ChevronLeftSolid" class="size-6" />
      </a>

      <TabList
        ref="listRef"
        :class="[
          'overlow-y-hidden -mb-px flex grow snap-x snap-mandatory overflow-x-auto scrollbar-thin scrollbar-track-neutral-200 scrollbar-thumb-neutral-300 sm:space-x-4 sm:px-4',
          { 'justify-around': fill, 'sm:grow-0': !fill },
        ]"
      >
        <slot />
      </TabList>

      <a
        href="#"
        class="block px-1 text-neutral-500 dark:text-neutral-200 sm:hidden"
        :class="{ 'pointer-events-none opacity-50': scrolledToLastTab }"
        @click.prevent="scrollRight"
      >
        <Icon icon="ChevronRightSolid" class="size-6" />
      </a>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { TabList } from '@headlessui/vue'

defineProps({
  responsive: { type: Boolean, default: true },
  fill: Boolean,
  centered: Boolean,
})

let observer = null

const scrolledToLastTab = ref(false)
const scrolledToFirstTab = ref(true)

const listRef = ref(null)

function firstTabElm() {
  return listRef.value.$el.querySelector('button:first-child')
}

function lastTabElm() {
  return listRef.value.$el.querySelector('button:last-child')
}

function scrollLeft() {
  listRef.value.$el.scrollLeft -= firstTabElm().offsetWidth
}

function scrollRight() {
  listRef.value.$el.scrollLeft += lastTabElm().offsetWidth
}

function unobserve() {
  observer.unobserve(firstTabElm())
  observer.unobserve(lastTabElm())
}

function observerCallback(entries) {
  entries.forEach(entry => {
    if (entry.target == lastTabElm()) {
      scrolledToLastTab.value = entry.isIntersecting
    } else if (entry.target == firstTabElm()) {
      scrolledToFirstTab.value = entry.isIntersecting
      scrolledToLastTab.value = false
    }
  })
}

function createObserver() {
  observer = new IntersectionObserver(observerCallback, {
    root: listRef.value.$el,
    threshold: 1.0,
  })
}

function observe() {
  createObserver()
  let firstEl = firstTabElm()
  let lastEl = lastTabElm()

  nextTick(() => {
    if (firstEl) {
      observer.observe(firstEl)
    }

    if (lastEl) {
      observer.observe(lastEl)
    }
  })
}

onMounted(() => {
  observe()
})

onBeforeUnmount(() => {
  unobserve()
  observer = null
})
</script>
