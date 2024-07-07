<template>
  <ICustomSelect
    v-model="value"
    :multiple="true"
    :disabled="readOnly"
    :input-id="'rule-' + rule.id + '-' + index"
    :placeholder="placeholder"
    :label="rule.labelKey"
    :option-key="rule.valueKey"
    :options="options"
  />
</template>

<script setup>
import { computed, toRef } from 'vue'
import { useI18n } from 'vue-i18n'

import propsDefinition from './props'
import { useType } from './useType'

defineOptions({ inheritAttrs: false })

const props = defineProps(propsDefinition)

const { t } = useI18n()

const { updateValue } = useType(
  toRef(props, 'query'),
  toRef(props, 'operator'),
  props.isNullable
)

const options = computed(() => props.rule.options)

const placeholder = computed(() =>
  t('core::filters.placeholders.choose_with_multiple', {
    label: props.operand ? props.operand.label : props.rule.label,
  })
)

const value = computed({
  get() {
    return (props.query.value || []).map(v =>
      options.value.find(o => o[props.rule.valueKey] == v)
    )
  },
  set(newValue) {
    updateValue(newValue.map(o => o[props.rule.valueKey]))
  },
})
</script>
