<template>
  <div>
    <QueryBuilderGroup
      :index="0"
      :query="queryBuilderRules"
      :available-rules="mergedAvailableRules"
      :max-depth="maxDepth"
      :read-only="readOnly"
      :depth="depth"
      :labels="mergedLabels"
    />

    <slot />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

import {
  useQueryBuilder,
  useQueryBuilderLabels,
} from '../../composables/useQueryBuilder'

import QueryBuilderGroup from './QueryBuilderGroup.vue'
import ruleTypes from './ruleTypes'

const props = defineProps({
  identifier: { type: String, required: true },
  view: { type: String, required: true },
  readOnly: Boolean,
  labels: Object,
  // max 3 is supported ATM
  maxDepth: {
    type: Number,
    default: 3,
    validator: value => value >= 1,
  },
})

const { resetQueryBuilderRules, queryBuilderRules, availableRules } =
  useQueryBuilder(props.identifier, props.view)

const { labels: defaultLabels } = useQueryBuilderLabels()

const depth = ref(1)

/**
 * Merged labels in case additional labels are passed as prop
 */
const mergedLabels = computed(() =>
  Object.assign({}, defaultLabels, props.labels)
)

const mergedAvailableRules = computed(() => {
  let rules = []

  availableRules.value.forEach(rule => {
    if (typeof ruleTypes[rule.type] !== 'undefined') {
      rules.push(Object.assign({}, ruleTypes[rule.type], rule))
    } else {
      rules.push(rule)
    }
  })

  return rules
})

if (Object.keys(queryBuilderRules.value).length === 0) {
  resetQueryBuilderRules()
}
</script>
