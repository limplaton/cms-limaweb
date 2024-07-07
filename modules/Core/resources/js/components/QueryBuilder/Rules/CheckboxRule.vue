<template>
  <div :class="totalOptions <= 3 ? 'inline-flex space-x-3' : 'space-y-1'">
    <IFormCheckboxField
      v-for="option in rule.options"
      :key="option[rule.valueKey]"
    >
      <IFormCheckbox
        v-model:checked="value"
        :value="option[rule.valueKey]"
        :disabled="readOnly"
      />

      <IFormCheckboxLabel :text="option[rule.labelKey]" />
    </IFormCheckboxField>
  </div>
</template>

<script setup>
import { computed, toRef } from 'vue'

import propsDefinition from './props'
import { useType } from './useType'

defineOptions({ inheritAttrs: false })

const props = defineProps(propsDefinition)

const value = computed({
  get() {
    return props.query.value
  },
  set(newValue) {
    updateValue(newValue)
  },
})

const totalOptions = computed(() => props.rule.options.length)

const { updateValue } = useType(
  toRef(props, 'query'),
  toRef(props, 'operator'),
  props.isNullable
)

if (props.query.value === null) {
  updateValue([])
}
</script>
