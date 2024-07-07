<template>
  <ICustomSelect
    v-model="value"
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
import find from 'lodash/find'

import { isBlank } from '@/Core/utils'

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
  t('core::filters.placeholders.choose', {
    label: props.operand ? props.operand.label : props.rule.label,
  })
)

const value = computed({
  get() {
    return find(options.value, [props.rule.valueKey, props.query.value]) || null
  },
  set(newValue) {
    updateValue(newValue ? newValue[props.rule.valueKey] : null)
  },
})

// First option selected by default
if (isBlank(props.query.value)) {
  value.value = options.value[0] ? options.value[0] : null
}
</script>
