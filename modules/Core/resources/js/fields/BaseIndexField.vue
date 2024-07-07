<template>
  <FieldInlineEdit
    class="hidden group-hover/td:block"
    :field="field"
    :field-fetcher="fetchField"
    :resource="row"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :edit-action="editAction"
    @updated="$emit('reload')"
  >
    <template v-for="(_, name) in $slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData" />
    </template>
  </FieldInlineEdit>

  <slot :has-value="fieldHasValue" :value="field.value" />
</template>

<script setup>
import { computed } from 'vue'

import { isBlank } from '@/Core/utils'

import { useResourceFields } from '../composables/useResourceFields'

const props = defineProps([
  'resourceName',
  'resourceId',
  'row',
  'field',
  'editAction',
])

defineEmits(['reload'])

const { getIndexFields } = useResourceFields()

const fieldHasValue = computed(() => !isBlank(props.field.value))

// We will provide fetch field function so the resource model is set in the resource
// This will allow the field to provide information based on the model being updated
// e.q. can be used in callbacks where the data returned is based on the model being edited.
async function fetchField() {
  const fields = await getIndexFields(props.resourceName, {
    intent: 'update',
    params: {
      resourceId: props.resourceId,
    },
  })

  return fields.find(field => props.field.attribute === field.attribute)
}
</script>
