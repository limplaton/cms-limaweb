<template>
  <component
    :is="section.field.formComponent"
    :field="section.field"
    :validation-errors="form.errors.groupByField()[section.field.attribute]"
    :model-value="form[section.field.attribute]"
    @update:model-value="
      $emit('fillFormAttribute', {
        attribute: section.field.attribute,
        value: $event,
      })
    "
    @set-initial-value="
      $emit('setFormAttribute', {
        attribute: section.field.attribute,
        value: $event,
      })
    "
  />
</template>

<script setup>
import propsDefinition from './props'

defineProps(propsDefinition)

defineEmits(['fillFormAttribute', 'setFormAttribute'])
</script>
