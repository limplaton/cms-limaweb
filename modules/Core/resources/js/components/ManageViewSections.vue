<template>
  <div v-if="show">
    <div class="mb-4">
      <ITextDisplay :text="$t('core::app.record_view.sections.edit_heading')" />

      <IText :text="$t('core::app.record_view.sections.edit_subheading')" />
    </div>

    <SortableDraggable
      item-key="id"
      class="space-y-2"
      handle=".section-reorder-handle"
      :model-value="sections"
      v-bind="$draggable.common"
      @update:model-value="$emit('update:sections', $event)"
    >
      <template #item="{ element }">
        <div
          class="flex items-center rounded-lg border border-neutral-200 bg-white px-4 py-3 dark:border-neutral-500/30 dark:bg-neutral-900"
        >
          <div class="grow">
            <IFormCheckboxField>
              <IFormCheckbox v-model:checked="checked[element.id]" />

              <IFormCheckboxLabel :text="element.heading || element.id" />
            </IFormCheckboxField>
          </div>

          <Icon
            icon="Selector"
            class="section-reorder-handle size-5 cursor-move text-neutral-500"
          />
        </div>
      </template>
    </SortableDraggable>

    <div class="mt-3 flex items-center justify-end space-x-1.5">
      <IButton
        :text="$t('core::app.cancel')"
        basic
        @click="$emit('update:show', false)"
      />

      <IButton
        variant="primary"
        :disabled="sectionsAreBeingSaved"
        :loading="sectionsAreBeingSaved"
        :text="$t('core::app.save')"
        soft
        @click="save"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  sections: { type: Array, required: true },
  show: Boolean,
  identifier: { type: String, required: true },
})

const emit = defineEmits(['saved', 'update:show', 'update:sections'])

const checked = ref({})

props.sections.forEach(section => {
  checked.value[section.id] = section.enabled
})

const sectionsAreBeingSaved = ref(false)

function save() {
  sectionsAreBeingSaved.value = true

  Innoclapps.request()
    .post('/settings', {
      [props.identifier + '_view_sections']: props.sections.map(
        (section, index) => ({
          id: section.id,
          order: index + 1,
          enabled: checked.value[section.id],
        })
      ),
    })
    .then(() => {
      emit('saved')

      const newValue = props.sections.map((section, index) =>
        Object.assign({}, section, {
          order: index + 1,
          enabled: checked.value[section.id],
        })
      )

      emit('update:sections', newValue)
    })
    .finally(() => (sectionsAreBeingSaved.value = false))
}
</script>
