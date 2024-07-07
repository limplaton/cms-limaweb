<template>
  <div
    class="relative z-40 flex shrink-0 bg-white shadow dark:bg-neutral-900/50 dark:shadow-neutral-700/50"
    :style="{ height: 'var(--navbar-height)' }"
  >
    <button
      v-once
      type="button"
      class="border-r border-neutral-200 px-3 text-neutral-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:border-neutral-500/30 dark:text-neutral-200 md:hidden"
      @click="sidebarOpen = true"
    >
      <span class="sr-only">Open sidebar</span>

      <Icon icon="Bars3BottomLeft" class="size-6" />
    </button>

    <div class="flex flex-1 justify-between pr-4 sm:pr-6 lg:pr-8">
      <div class="flex flex-1">
        <div v-show="hasTitle" class="mx-8 hidden max-w-xs py-5 lg:block">
          <h1
            class="truncate font-semibold uppercase text-neutral-800 dark:text-neutral-100"
            v-text="navbarTitle"
          />
        </div>

        <span
          v-show="hasTitle"
          class="hidden border-l border-neutral-200 dark:border-neutral-500/30 lg:block"
          :style="{ height: 'var(--navbar-height)' }"
        />

        <TheNavbarSearch />
      </div>

      <div v-once class="ml-3 flex items-center lg:ml-6">
        <IButton
          id="header__moon"
          v-i-tooltip.bottom="$t('core::app.theme.switch_light')"
          icon="Moon"
          basic
          @click="toLightMode"
        />

        <IButton
          id="header__sun"
          v-i-tooltip.bottom="$t('core::app.theme.switch_system')"
          icon="Sun"
          basic
          @click="toSystemMode"
        />

        <IButton
          id="header__indeterminate"
          v-i-tooltip.bottom="$t('core::app.theme.switch_dark')"
          basic
          @click="toDarkMode"
        >
          <svg viewBox="0 0 24 24" data-slot="icon">
            <path
              fill="currentColor"
              d="M12 2A10 10 0 0 0 2 12A10 10 0 0 0 12 22A10 10 0 0 0 22 12A10 10 0 0 0 12 2M12 4A8 8 0 0 1 20 12A8 8 0 0 1 12 20V4Z"
            />
          </svg>
        </IButton>

        <NavbarSeparator v-once />

        <div>
          <TheNavbarNotifications />
        </div>

        <div v-once class="ml-1 hidden md:block lg:ml-2">
          <TheNavbarQuickCreate />
        </div>

        <!-- Teleport target -->
        <div id="navbar-actions" class="hidden lg:flex lg:items-center"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import { useApp } from '@/Core/composables/useApp'

import { usePageTitle } from '../composables/usePageTitle'

import TheNavbarNotifications from './TheNavbarNotifications.vue'
import TheNavbarQuickCreate from './TheNavbarQuickCreate.vue'
import TheNavbarSearch from './TheNavbarSearch.vue'

const navbarTitle = usePageTitle()
const { sidebarOpen } = useApp()

const hasTitle = computed(() => Boolean(navbarTitle.value))

function toLightMode() {
  localStorage.theme = 'light'
  window.updateTheme()
}

function toDarkMode() {
  localStorage.theme = 'dark'
  window.updateTheme()
}

function toSystemMode() {
  localStorage.theme = 'system'
  window.updateTheme()
}
</script>

<style>
#header__sun,
#header__moon,
#header__indeterminate {
  display: none;
}

html[color-theme='dark'] #header__moon {
  display: block;
}

html[color-theme='light'] #header__sun {
  display: block;
}

html[color-theme='system'] #header__indeterminate {
  display: block;
}
</style>
