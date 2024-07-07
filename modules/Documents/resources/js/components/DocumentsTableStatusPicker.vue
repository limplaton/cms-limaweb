<template>
  <div class="flex w-full items-center overflow-x-auto">
    <ILink
      v-show="model && !isDisabled"
      class="mr-3 border-r-2 border-neutral-200 pr-3 dark:border-neutral-500/30"
      :text="$t('core::app.all')"
      @click="model = null"
    />

    <div
      v-i-tooltip.bottom.light="
        statusRuleIsApplied
          ? $t('documents::document.filters.status_disabled')
          : ''
      "
      class="flex space-x-2"
    >
      <template v-for="status in statuses" :key="status.name">
        <ScopedVarColorPalette
          :class="[
            'inline-flex items-center justify-center gap-x-1.5 rounded-lg bg-[rgba(var(--color-custom-100))] px-3 py-1.5 text-base/5 text-[rgba(var(--color-custom-700))] dark:bg-[rgba(var(--color-custom-400),0.1)] dark:text-[rgba(var(--color-custom-300))] sm:text-sm/5',
            status.name === model
              ? 'ring-1 ring-inset ring-[rgba(var(--color-custom-600),0.1)] dark:ring-[rgba(var(--color-custom-400),0.2)]'
              : '',
            isDisabled ? 'pointer-events-none opacity-70' : 'cursor-pointer',
          ]"
          :color="status.color"
          @click="model = status.name"
        >
          <Icon class="size-4" :icon="status.icon" />
          {{ status.display_name }}
        </ScopedVarColorPalette>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'

import ScopedVarColorPalette from '@/Core/components/ScopedVarColorPalette.vue'
import { useApp } from '@/Core/composables/useApp'
import { useQueryBuilder } from '@/Core/composables/useQueryBuilder'

const model = defineModel()

const { scriptConfig } = useApp()

const { hasAnyBuilderRules, rulesAreValid, findRule } =
  useQueryBuilder('documents')

const statuses = scriptConfig('documents.statuses')

const statusRuleIsApplied = computed(() => Boolean(findRule('status')))

const isDisabled = computed(
  () => statusRuleIsApplied.value || !rulesAreValid.value
)

// Remove selected type when the builder has rules and they are valid
// to prevent errors in the filters
watch(hasAnyBuilderRules, newVal => {
  if (newVal && rulesAreValid.value) {
    model.value = undefined
  }
})

// The same for when rules become valid, when valid and has builder rules remove selected type
watch(rulesAreValid, newVal => {
  if (hasAnyBuilderRules.value && newVal) {
    model.value = undefined
  }
})
</script>
