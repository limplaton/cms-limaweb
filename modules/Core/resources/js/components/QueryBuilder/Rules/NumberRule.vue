<template>
  <IFormInput
    v-if="!isBetween"
    type="number"
    class="w-full sm:w-auto"
    :placeholder="placeholder"
    :disabled="readOnly"
    :model-value="query.value"
    @input="updateValue($event)"
  />

  <div v-else class="flex items-center space-x-2">
    <IFormInput
      type="number"
      :placeholder="placeholder"
      :disabled="readOnly"
      :model-value="query.value[0]"
      @input="updateValue([$event, query.value[1]])"
    />

    <Icon icon="ArrowRight" class="size-4 shrink-0 text-neutral-600" />

    <IFormInput
      type="number"
      :placeholder="placeholder"
      :disabled="readOnly"
      :model-value="query.value[1]"
      @input="updateValue([query.value[0], $event])"
    />
  </div>
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

const placeholder = computed(() =>
  t('core::filters.placeholders.enter', {
    label: props.operand ? props.operand.label : props.rule.label,
  })
)
</script>
