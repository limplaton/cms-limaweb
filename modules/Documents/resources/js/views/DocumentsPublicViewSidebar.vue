<template>
  <div>
    <TransitionRoot as="template" :show="sidebarOpen">
      <Dialog
        as="div"
        class="relative z-40 md:hidden"
        @close="sidebarOpen = false"
      >
        <TransitionChild
          as="template"
          enter="transition-opacity ease-linear duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="transition-opacity ease-linear duration-300"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div
            class="fixed inset-0 bg-neutral-900/75 transition-opacity dark:bg-neutral-700/90"
          />
        </TransitionChild>

        <div class="fixed inset-0 z-40 flex">
          <TransitionChild
            as="template"
            enter="transition ease-in-out duration-300 transform"
            enter-from="-translate-x-full"
            enter-to="translate-x-0"
            leave="transition ease-in-out duration-300 transform"
            leave-from="translate-x-0"
            leave-to="-translate-x-full"
          >
            <DialogPanel
              class="relative flex w-full max-w-xs flex-1 flex-col"
              :style="{ backgroundColor: backgroundColor }"
            >
              <TransitionChild
                as="template"
                enter="ease-in-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in-out duration-300"
                leave-from="opacity-100"
                leave-to="opacity-0"
              >
                <div class="absolute right-0 top-0 -mr-12 pt-2">
                  <button
                    type="button"
                    class="ml-1 flex size-10 items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                    @click="sidebarOpen = false"
                  >
                    <span class="sr-only">Close sidebar</span>

                    <Icon icon="XSolid" class="size-6 text-white" />
                  </button>
                </div>
              </TransitionChild>

              <div class="h-0 flex-1 overflow-y-auto py-10">
                <div class="flex shrink-0 items-center px-4">
                  <a class="focus:outline-none" :href="publicUrl">
                    <img
                      v-if="logo"
                      class="h-8 w-auto"
                      :src="logo"
                      :alt="brandName"
                    />

                    <Icon
                      v-else
                      icon="Document"
                      class="size-8 text-neutral-400"
                    />
                  </a>
                </div>

                <nav class="mt-5 space-y-1 px-3">
                  <a
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :style="{ color: getContrast(backgroundColor) }"
                    :class="[
                      currentHash === item.href ? 'font-medium' : 'font-normal',
                      'flex px-2 py-2 text-base focus:outline-none',
                    ]"
                    v-text="item.name"
                  />
                </nav>
              </div>

              <div class="flex shrink-0 space-x-3 p-4">
                <slot name="actions" />
              </div>
            </DialogPanel>
          </TransitionChild>

          <div class="w-14 shrink-0">
            <!-- Force sidebar to shrink to fit close icon -->
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Static sidebar for desktop -->
    <div class="z-10 hidden md:fixed md:inset-y-0 md:flex md:w-72 md:flex-col">
      <div
        class="flex min-h-0 flex-1 flex-col shadow-[0px_0px_25px_0px_rgba(0,0,0,0.3)]"
        :style="{ backgroundColor: backgroundColor }"
      >
        <div class="flex flex-1 flex-col overflow-y-auto py-10">
          <div class="flex shrink-0 items-center px-4">
            <a class="focus:outline-none" :href="publicUrl">
              <img
                v-if="logo"
                class="h-8 w-auto"
                :src="logo"
                :alt="brandName"
              />

              <Icon v-else icon="Document" class="size-8 text-neutral-400" />
            </a>
          </div>

          <nav
            class="mt-5 flex-1 space-y-1 px-3"
            :style="{ backgroundColor: backgroundColor }"
          >
            <a
              v-for="item in navigation"
              :key="item.name"
              :href="item.href"
              :style="{ color: getContrast(backgroundColor) }"
              :class="[
                currentHash === item.href ? 'font-medium' : 'font-normal',
                'flex px-2 py-2 text-base focus:outline-none sm:text-sm',
              ]"
              v-text="item.name"
            />
          </nav>
        </div>

        <div class="flex shrink-0 justify-center space-x-3 p-4">
          <slot name="actions" />
        </div>
      </div>
    </div>

    <div class="flex flex-1 flex-col md:pl-72">
      <div
        class="sticky top-0 z-10 bg-white pl-1 pt-1 sm:pl-3 sm:pt-3 md:hidden"
      >
        <div class="size-12">
          <button
            type="button"
            class="mt-1.5 inline-flex items-center justify-center rounded-md p-1 text-neutral-500 hover:text-neutral-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
            @click="sidebarOpen = true"
          >
            <span class="sr-only">Open sidebar</span>

            <Icon icon="Bars3BottomLeft" class="size-6" />
          </button>
        </div>
      </div>

      <main class="flex-1">
        <div :class="{ 'py-6': !full }">
          <div :class="{ 'mx-auto max-w-7xl px-4 sm:px-6 md:px-8': !full }">
            <slot name="content" />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import find from 'lodash/find'

import { debounce, getContrast } from '@/Core/utils'

const props = defineProps([
  'navigation',
  'publicUrl',
  'brandName',
  'logo',
  'backgroundColor',
  'full',
  'navigationHeadingTagName',
])

const sidebarOpen = ref(false)
const currentHash = ref(null)

document.body.classList.add('h-full')

var root = document.getElementsByTagName('html')[0]
root.className += ' h-full scroll-smooth'

currentHash.value = window.location.hash

if (!currentHash.value && props.navigation.length > 0) {
  currentHash.value = props.navigation[0].href
}

window.addEventListener('hashchange', e => {
  currentHash.value = e.currentTarget.location.hash
})

onMounted(() => {
  let domHeadings = document.querySelectorAll(props.navigationHeadingTagName)
  let realDomHeadings = []

  domHeadings.forEach(element => {
    const navigationHeading = find(props.navigation, {
      name: element.innerText,
    })

    if (navigationHeading) {
      if (!element.getAttribute('id')) {
        element.setAttribute('id', navigationHeading.id)
      }

      realDomHeadings.push({
        ...navigationHeading,
        element: element,
      })
    }
  })

  const isInViewport = function (element) {
    const rect = element.getBoundingClientRect()

    return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <=
        (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    )
  }

  window.addEventListener(
    'scroll',
    debounce(() => {
      let visibleHeadings = []

      realDomHeadings.forEach(heading => {
        if (isInViewport(heading.element)) {
          visibleHeadings.push(heading)
        }

        if (visibleHeadings.length > 0) {
          currentHash.value = `${visibleHeadings[0].href}`
        }
      })
    }, 150)
  )
})
</script>
