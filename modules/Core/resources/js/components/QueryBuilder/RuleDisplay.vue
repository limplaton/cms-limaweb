<template>
  <span
    v-if="index > 0"
    class="rounded-lg border border-neutral-300 px-3 py-[0.22rem] text-base text-neutral-600 dark:border-neutral-500/30 dark:text-neutral-300 sm:text-sm"
    v-text="$t('core::filters.conditions.' + condition)"
  />

  <div
    v-if="rule.type === 'rule' && rule.query.rule"
    class="shrink-0 snap-end rounded-lg border border-neutral-300 px-3 py-[0.22rem] text-base text-neutral-500 dark:border-neutral-500/30 dark:text-neutral-300 sm:text-sm"
  >
    <span v-if="!hasCustomDisplayAs">
      <span v-if="query.operand">
        {{ availableRules.find(r => query.rule === r.id)?.label }}
      </span>
      {{ original.label }}
      <span
        class="text-info-500 dark:text-info-400"
        v-text="labels.operatorLabels[query.operator]"
      />

      &nbsp;<span class="font-medium" v-text="parsedLabel" />
    </span>

    <span v-else>
      {{ parsedCustomDisplayAs }}
    </span>
  </div>

  <div v-if="isGroup" class="flex shrink-0 space-x-1">
    <RuleDisplay
      v-for="(groupRule, childIndex) in query.children"
      :key="groupRule.query.rule"
      :index="childIndex"
      :condition="query.condition"
      :identifier="identifier"
      :view="view"
      :rule="groupRule"
    />
  </div>
</template>

<script></script>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import find from 'lodash/find'
import map from 'lodash/map'
import pickBy from 'lodash/pickBy'

import { useAccounting } from '@/Core/composables/useAccounting'
import { useDates } from '@/Core/composables/useDates'
import { isBlank } from '@/Core/utils'

import {
  useQueryBuilder,
  useQueryBuilderLabels,
} from '../../composables/useQueryBuilder'

import { isBetweenOperator } from './utils'

defineOptions({
  name: 'RuleDisplay',
})

const props = defineProps({
  rule: { type: Object, required: true },
  identifier: { type: String, required: true },
  view: { type: String, required: true },
  index: { type: Number, required: true },
  condition: String,
})

const { formatMoney, formatNumber } = useAccounting()

const { availableRules } = useQueryBuilder(props.identifier, props.view)
const { labels } = useQueryBuilderLabels()

const parsedLabel = ref('')

const { localizedDate } = useDates()

const isGroup = computed(() => {
  return props.rule.type === 'group'
})

const parsedCustomDisplayAs = computed(() => {
  const displayAs = getRuleAttribute('displayAs')

  const replacer = string => {
    return string
      .replace(':value:', parsedLabel.value)
      .replace(':operator:', labels.operatorLabels[query.value.operator])
  }

  if (typeof displayAs === 'string') {
    return replacer(displayAs)
  }

  if (
    displayAs[0] &&
    Object.keys(displayAs).indexOf(query.value.value) === -1
  ) {
    return replacer(displayAs[0])
  }

  return replacer(displayAs[query.value.value])
})

const query = computed(() => {
  return props.rule.query
})

const original = computed(() => {
  const originalObject = find(availableRules.value, ['id', query.value.rule])

  if (query.value.operand) {
    return find(
      originalObject.operands,
      operand => operand.rule.id == query.value.operand
    )
  }

  return originalObject
})

const isBetween = computed(() => {
  return isBetweenOperator(query.value.operator)
})

const hasCustomDisplayAs = computed(() => {
  if (isGroup.value) {
    return false
  }

  return Boolean(getRuleAttribute('displayAs'))
})

watch(
  () => props.condition,
  () => {
    // We don't parse any values when a group, as the RuleDisplay is either OR or AND
    if (isGroup.value) {
      return
    }

    parsedLabel.value = valueLabel()
  }
)

watch(
  query,
  () => {
    // We don't parse any values when a group, as the RuleDisplay is either OR or AND
    if (isGroup.value) {
      return
    }

    // User hasn't selected any rule yet
    if (!query.value.type) {
      return
    }

    // Wait till everything is updated in the store e.q. values
    nextTick(() => (parsedLabel.value = valueLabel()))
  },
  { immediate: true, deep: true }
)

function getRuleAttribute(attribute) {
  return query.value.operand
    ? original.value.rule[attribute]
    : original.value[attribute]
}

function valueLabel() {
  let type = getRuleAttribute('type')

  if (['multi-select', 'checkbox'].indexOf(type) > -1) {
    return valueLabelWhenAcceptsMultiOptions()
  } else if (['radio', 'select'].indexOf(type) > -1) {
    return valueLabelWhenAcceptsOptions()
  } else if (type === 'date') {
    return valueLabelWhenDate()
  } else if (type === 'numeric') {
    return valueLabelWhenNumeric()
  } else if (type === 'number') {
    return valueLabelWhenNumber()
  } else if (isBetween.value) {
    return valueLabelWhenBetween()
  }

  return query.value.value
}

function valueLabelWhenAcceptsOptions() {
  let selected =
    getRuleAttribute('options').filter(
      option => option[getRuleAttribute('valueKey')] == query.value.value
    )[0] || null

  return selected ? selected[getRuleAttribute('labelKey')] : ''
}

function valueLabelWhenAcceptsMultiOptions() {
  let selected = !query.value.value
    ? []
    : getRuleAttribute('options').filter(
        option =>
          query.value.value.indexOf(option[getRuleAttribute('valueKey')]) > -1
      )

  return map(selected, getRuleAttribute('labelKey')).join(', ')
}

function valueLabelWhenBetween() {
  return query.value.value ? query.value.value.join(' - ') : ''
}

function formattedValueLabel(formatter) {
  if (isBetween.value) {
    if (isBlank(query.value.value)) {
      return ''
    }

    return [
      query.value.value[0] ? formatter(query.value.value[0]) : '',
      query.value.value[1] ? formatter(query.value.value[1]) : '',
    ].join(' - ')
  }

  return query.value.value ? formatter(query.value.value) : ''
}

function valueLabelWhenNumber() {
  return formattedValueLabel(formatNumber)
}

function valueLabelWhenNumeric() {
  return formattedValueLabel(formatMoney)
}

function valueLabelWhenDate() {
  if (query.value.operator === 'is' || query.value.operator === 'was') {
    let operatorOptions = []

    if (query.value.operand) {
      operatorOptions =
        original.value.rule.operatorsOptions[query.value.operator]
    } else {
      operatorOptions = original.value.operatorsOptions[query.value.operator]
    }

    return pickBy(operatorOptions, (value, key) => key == query.value.value)[
      query.value.value
    ]
  }

  return formattedValueLabel(localizedDate)
}
</script>
