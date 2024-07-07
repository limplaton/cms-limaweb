<template>
  <div class="flex w-full max-w-2xl items-center overflow-x-auto p-px">
    <ILink
      v-show="model && !isDisabled"
      class="mr-3 border-r-2 border-neutral-200 pr-3 dark:border-neutral-500/30"
      :text="$t('core::app.all')"
      @click="model = null"
    />

    <span
      v-i-tooltip.bottom.light="
        typeRuleIsApplied
          ? $t('activities::activity.filters.activity_type_disabled')
          : ''
      "
    >
      <IIconPicker
        v-model="model"
        class="min-w-max"
        value-field="id"
        :icons="typesForIconPicker"
        :disabled="isDisabled"
      />
    </span>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'

import { useQueryBuilder } from '@/Core/composables/useQueryBuilder'

import { useActivityTypes } from '../composables/useActivityTypes'

const model = defineModel()

const { hasAnyBuilderRules, rulesAreValid, findRule } =
  useQueryBuilder('activities')

const { typesForIconPicker } = useActivityTypes()

const typeRuleIsApplied = computed(() => Boolean(findRule('activity_type_id')))

const isDisabled = computed(
  () => typeRuleIsApplied.value || !rulesAreValid.value
)

// Remove selected type when the builder has rules and they are valid
// to prevent errors in the filters
watch(hasAnyBuilderRules, newVal => {
  if (newVal && rulesAreValid.value) {
    model.value = undefined
  }
})

// The same for when rules become valid, when valid and has builder rules
// remove selected type
watch(rulesAreValid, newVal => {
  if (hasAnyBuilderRules.value && newVal) {
    model.value = undefined
  }
})
</script>
