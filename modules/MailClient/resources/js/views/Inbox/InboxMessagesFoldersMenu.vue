<template>
  <IVerticalNavigation>
    <InboxFoldersMenuItem
      v-for="folder in folders"
      :key="folder.id"
      :folder="folder"
    />
  </IVerticalNavigation>

  <!-- Creates placeholders when not active folders are found -->
  <div v-if="!folders.length">
    <div
      v-for="placeholder in [1, 2, 3, 4]"
      :key="'p-' + placeholder"
      class="mb-2 rounded-lg bg-neutral-200 p-4 dark:bg-neutral-500"
    />
  </div>
</template>

<script setup>
import InboxFoldersMenuItem from './InboxMessagesFoldersMenuItem.vue'

defineProps({
  folders: { type: Array, required: true },
})
</script>
