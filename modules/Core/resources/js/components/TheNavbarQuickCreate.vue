<template>
  <IDropdown v-slot="{ hide }" placement="bottom-end">
    <IDropdownButton variant="primary" icon="PlusSolid" soft pill no-caret />

    <IDropdownMenu class="w-56">
      <div class="flex items-center justify-between px-3 py-2">
        <ITextDark class="font-medium" :text="$t('core::app.quick_create')" />

        <span
          class="rounded-md bg-neutral-700 px-1.5 text-base text-neutral-100 dark:bg-neutral-600 dark:text-neutral-200"
        >
          <span class="-mt-0.5 block">+</span>
        </span>
      </div>

      <IDropdownSeparator />

      <IDropdownItem
        v-for="(item, index) in quickCreateMenuItems"
        v-show="$route.path !== item.quickCreateRoute"
        :key="index"
        :icon="item.icon"
        :to="item.quickCreateRoute"
        @click="hide"
      >
        {{ item.quickCreateName }}
        <span
          class="col-start-5 row-start-1 flex justify-self-end rounded-md bg-neutral-100 px-1.5 uppercase text-neutral-500 dark:bg-neutral-700 dark:text-neutral-300"
          v-text="item.keyboardShortcutChar"
        />
      </IDropdownItem>
    </IDropdownMenu>
  </IDropdown>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import { useApp } from '../composables/useApp'

const router = useRouter()
const { sidebarItems } = useApp()

const quickCreateMenuItems = computed(() =>
  sidebarItems.value.filter(item => item.inQuickCreate)
)

const itemsWithKeyboardShortcut = computed(() =>
  quickCreateMenuItems.value.filter(item => item.keyboardShortcutChar !== null)
)

registerKeyboardShortcuts()

/**
 * Register the quick create keyboard shortcuts
 * NOTE: They don't need to be unbinded as this is a global component
 */
function registerKeyboardShortcuts() {
  itemsWithKeyboardShortcut.value.forEach(item => {
    Innoclapps.addShortcut(
      '+ ' + item.keyboardShortcutChar.toLowerCase(),
      () => {
        // TODO: If the dropdown is open and the user uses keyboard shortcut
        // it won't be closed as the popper component is expecting click in order to close the component
        router.push(item.quickCreateRoute)
      }
    )
  })
}
</script>
