<template>
  <div
    class="fixed left-auto right-1 z-50 w-full max-w-xs rounded-lg border border-neutral-500/30 bg-neutral-800 px-6 py-5 dark:bg-neutral-900 md:left-80 md:right-auto md:max-w-md"
    :class="totalResults <= 5 ? 'bottom-36' : 'bottom-[3.3rem]'"
  >
    <div class="flex flex-col md:flex-row md:items-center">
      <div class="mr-8 flex shrink-0 items-center">
        <IFormCheckboxField>
          <IFormCheckbox
            :checked="ids.length > 0"
            @change="$emit('unselect')"
          />

          <IFormCheckboxLabel class="text-white">
            {{
              $t('core::actions.records_count', {
                count: ids.length,
              })
            }}
          </IFormCheckboxLabel>
        </IFormCheckboxField>
      </div>

      <div class="md:w-68 mt-3 w-full md:mt-0">
        <ActionSelector
          type="select"
          view="index"
          :ids="ids"
          :action-request-params="requestParams"
          :actions="actions || []"
          :resource-name="resourceName"
          @run="$emit('run', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import ActionSelector from '@/Core/components/Actions/ActionSelector.vue'

defineProps([
  'ids',
  'actions',
  'requestParams',
  'resourceName',
  'tableId',
  'totalResults',
])

defineEmits(['run', 'unselect'])
</script>
