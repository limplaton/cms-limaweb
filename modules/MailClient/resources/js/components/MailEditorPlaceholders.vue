<template>
  <div>
    <ITabGroup>
      <ITabList>
        <ITab
          v-for="(group, groupName) in placeholders"
          :key="groupName"
          class="first:sm:-ml-2.5"
        >
          <Icon :icon="group.icon" />

          {{ group.label }}
        </ITab>
      </ITabList>

      <ITabPanels>
        <ITabPanel v-for="(group, groupName) in placeholders" :key="groupName">
          <MailEditorPlacehodersList
            :placeholders="group.placeholders"
            @insert-requested="handleInsert($event, group.label)"
          />
        </ITabPanel>
      </ITabPanels>
    </ITabGroup>
  </div>
</template>

<script setup>
import { useTimeoutFn } from '@vueuse/core'

import { insertPlaceholder } from '../composables/useMessagePlaceholders'

import MailEditorPlacehodersList from './MailEditorPlacehodersList.vue'

defineProps({
  placeholders: { type: Object },
})

const emit = defineEmits(['inserted'])

function handleInsert(placeholder, groupLabel) {
  insertPlaceholder(placeholder, groupLabel)

  // Wait till the editor content is updated
  useTimeoutFn(() => emit('inserted'), 500)
}
</script>
