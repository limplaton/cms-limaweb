<template>
  <!-- Sidebar for mobile -->
  <TransitionRoot as="template" :show="sidebarOpen">
    <Dialog
      as="div"
      class="fixed inset-0 z-50 flex md:hidden"
      :open="sidebarOpen"
      static
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
          class="relative flex w-56 max-w-xs flex-col bg-neutral-800 pb-4 pt-5 dark:bg-neutral-900"
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
                @click="setSidebarOpenState(false)"
              >
                <span class="sr-only">Close sidebar</span>

                <Icon icon="XSolid" class="size-6 text-white" />
              </button>
            </div>
          </TransitionChild>

          <div class="flex shrink-0 items-center px-4">
            <ILink class="whitespace-normal" :to="{ name: 'dashboard' }" plain>
              <span v-if="!logo" class="font-bold text-white">
                {{ companyName }}
              </span>

              <img v-else class="h-10 max-h-14 w-auto" :src="logo" />
            </ILink>
          </div>

          <UserProfileDropdown />

          <div class="mt-5 h-0 flex-1 overflow-y-auto">
            <nav id="sidebar-sm" class="space-y-0.5 px-2 text-base sm:text-sm">
              <ILink
                v-for="item in sidebarItems"
                :key="item.id"
                active-class="bg-neutral-700 dark:bg-neutral-800 text-white"
                inactive-class="text-neutral-50 hover:bg-neutral-700 dark:hover:bg-neutral-800"
                :class="[
                  'group relative flex items-center rounded-md px-2 py-2 focus:outline-none',
                  'sidebar-sm-item-' + item.id,
                ]"
                :to="item.route"
                plain
              >
                <Icon
                  v-if="item.icon"
                  class="mr-4 size-6 shrink-0 text-neutral-300"
                  :icon="item.icon"
                />

                {{ item.name }}

                <IBadge
                  v-if="item.badge"
                  class="absolute -left-px -top-px dark:bg-neutral-900"
                  :variant="item.badgeVariant"
                  :text="item.badge"
                  pill
                />

                <ILink
                  v-if="item.inQuickCreate"
                  :to="item.quickCreateRoute"
                  :class="[
                    'ml-auto rounded-md hover:bg-neutral-800 dark:hover:bg-neutral-900',
                    $route.path === item.quickCreateRoute ? 'hidden' : '',
                  ]"
                  plain
                >
                  <Icon icon="PlusSolid" class="size-5" />
                </ILink>
              </ILink>
            </nav>
          </div>

          <TheSidebarMetrics />
        </DialogPanel>
      </TransitionChild>
    </Dialog>
  </TransitionRoot>

  <!-- Static sidebar for desktop -->
  <div
    v-show="['404', '403', 'not-found'].indexOf($route.name) === -1"
    class="hidden bg-neutral-800 dark:bg-neutral-900 md:flex md:shrink-0"
  >
    <div class="flex w-56 flex-col">
      <!-- Sidebar component, swap this element with another sidebar if you like -->
      <div class="flex grow flex-col overflow-y-auto pb-4 pt-5">
        <div class="flex shrink-0 items-center px-4">
          <ILink class="whitespace-normal" :to="{ name: 'dashboard' }" plain>
            <span v-if="!logo" class="font-bold text-white">
              {{ companyName }}
            </span>

            <img v-else class="h-10 max-h-14 w-auto" :src="logo" />
          </ILink>
        </div>

        <UserProfileDropdown />

        <!-- Sidebar links -->
        <div class="mt-6 flex h-0 flex-1 flex-col overflow-y-auto">
          <nav id="sidebar-lg" class="flex-1 space-y-1 px-2">
            <ILink
              v-for="item in sidebarItems"
              :key="item.id"
              active-class="bg-neutral-700 dark:bg-neutral-800 text-white"
              inactive-class="text-neutral-50 hover:bg-neutral-700 dark:hover:bg-neutral-800"
              :class="[
                'group relative flex items-center rounded-md px-2 py-2 text-sm focus:outline-none',
                'sidebar-lg-item-' + item.id,
              ]"
              :to="item.route"
              plain
            >
              <Icon
                v-if="item.icon"
                class="mr-3 size-6 shrink-0 text-neutral-300"
                :icon="item.icon"
              />

              {{ item.name }}

              <IBadge
                v-if="item.badge"
                class="absolute -left-px -top-px dark:bg-neutral-900"
                :variant="item.badgeVariant"
                :text="item.badge"
                pill
              />

              <ILink
                v-if="item.inQuickCreate"
                :to="item.quickCreateRoute"
                :class="[
                  'ml-auto hidden rounded-md hover:bg-neutral-800 dark:hover:bg-neutral-900',
                  $route.path !== item.quickCreateRoute
                    ? 'group-hover:block'
                    : '',
                ]"
                plain
              >
                <Icon icon="PlusSolid" class="size-5" />
              </ILink>
            </ILink>
          </nav>
        </div>

        <TheSidebarMetrics />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

import { useApp } from '@/Core/composables/useApp'

import TheSidebarMetrics from './TheSidebarMetrics.vue'
import UserProfileDropdown from './UserProfileDropdown.vue'

const { sidebarItems, sidebarOpen, setting } = useApp()

const companyName = computed(() => setting('company_name'))
const logo = setting('logo_light')
</script>
