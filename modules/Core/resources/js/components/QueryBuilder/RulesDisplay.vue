<template>
  <div
    class="flex snap-x space-x-1 overflow-x-auto text-[0.96rem] scrollbar-thin scrollbar-track-neutral-200 scrollbar-thumb-neutral-300 dark:scrollbar-thumb-neutral-600"
  >
    <RuleDisplay
      v-for="(rule, index) in rules"
      :key="rule.query.rule"
      :index="index"
      :condition="group.condition"
      :identifier="identifier"
      :view="view"
      :rule="rule"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

import { useQueryBuilder } from '../../composables/useQueryBuilder'

import RuleDisplay from './RuleDisplay.vue'

const props = defineProps({
  view: { required: true, type: String },
  identifier: { required: true, type: String },
})

const { queryBuilderRules: group } = useQueryBuilder(
  props.identifier,
  props.view
)

// We will filter any rules empty rules
const rules = computed(() =>
  (group.value?.children || []).filter(r => {
    if (r.type === 'group') {
      return true
    }

    return Boolean(r.query.type)
  })
)
</script>
