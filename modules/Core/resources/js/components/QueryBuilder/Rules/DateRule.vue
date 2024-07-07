<template>
  <div
    v-if="!isDateIsOperator && !isDateWasOperator"
    class="flex items-center space-x-1 sm:space-x-2"
  >
    <div v-if="!isBetween" class="w-full sm:w-auto">
      <DatePicker
        :placeholder="$t('core::filters.placeholders.select_date')"
        :model-value="query.value"
        :disabled="readOnly"
        @input="updateValue($event)"
      />
    </div>

    <DatePicker
      v-if="isBetween"
      :placeholder="$t('core::filters.placeholders.select_date')"
      :model-value="query.value[0]"
      :disabled="readOnly"
      @input="updateValue([$event, query.value[1]])"
    />

    <Icon
      v-if="isBetween"
      icon="ArrowRight"
      class="size-4 shrink-0 text-neutral-600"
    />

    <DatePicker
      v-if="isBetween"
      :placeholder="$t('core::filters.placeholders.select_date')"
      :min-date="query.value[0] || null"
      :disabled="readOnly || !query.value[0]"
      :model-value="query.value[1]"
      @input="updateValue([query.value[0], $event])"
    />
  </div>

  <IFormSelect
    v-else
    class="w-full sm:w-auto"
    :model-value="query.value"
    :disabled="readOnly"
    @input="updateValue($event)"
  >
    <option value=""></option>

    <option
      v-for="operator in operatorIsOrWasOptions"
      :key="operator.value"
      :value="operator.value"
      v-text="operator.text"
    />
  </IFormSelect>
</template>

<script setup>
import { computed, toRef } from 'vue'
import map from 'lodash/map'

import propsDefinition from './props'
import { useType } from './useType'

defineOptions({ inheritAttrs: false })

const props = defineProps(propsDefinition)

const { updateValue } = useType(
  toRef(props, 'query'),
  toRef(props, 'operator'),
  props.isNullable
)

/**
 * Indicates whether the operator is IS
 *
 * @return {Boolean}
 */
const isDateIsOperator = computed(() => props.query.operator === 'is')

/**
 * Get the IS operator options
 *
 * @return {Array}
 */
const isOperatorOptions = computed(() =>
  map(props.rule.operatorsOptions['is'], (option, value) => ({
    value: value,
    text: option,
  }))
)

/**
 * Get the WAS operator options
 *
 * @return {Array}
 */
const wasOperatorOptions = computed(() =>
  map(props.rule.operatorsOptions['was'], (option, value) => ({
    value: value,
    text: option,
  }))
)

/**
 * Get the IS or WAS operator options
 *
 * @return {Array}
 */
const operatorIsOrWasOptions = computed(() =>
  isDateIsOperator.value ? isOperatorOptions.value : wasOperatorOptions.value
)

/**
 * Indicates whether the operator is WAS
 *
 * @return {Boolean}
 */
const isDateWasOperator = computed(() => props.query.operator === 'was')
</script>
