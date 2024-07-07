<template>
  <div :class="totalOptions <= 3 ? 'inline-flex space-x-3' : 'space-y-1'">
    <IFormRadioField
      v-for="option in rule.options"
      :key="option[rule.valueKey]"
    >
      <IFormRadio
        v-model="value"
        :value="option[rule.valueKey]"
        :disabled="readOnly"
        :name="rule.id"
      />

      <IFormRadioLabel :text="option[rule.labelKey]" />
    </IFormRadioField>
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
</script>
