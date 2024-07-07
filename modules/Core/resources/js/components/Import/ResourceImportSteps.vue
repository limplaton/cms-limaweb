<template>
  <div
    class="-mt-px lg:border-b lg:border-t lg:border-neutral-200 lg:dark:border-neutral-500/30"
  >
    <nav class="mx-auto max-w-7xl lg:px-8" aria-label="Progress">
      <ol
        role="list"
        class="overflow-hidden lg:flex lg:border-l lg:border-r lg:border-neutral-200 lg:dark:border-neutral-500/30"
      >
        <ResourceImportStep
          v-for="(step, stepIdx) in steps"
          :key="step.id"
          :step="step"
          :total="steps.length"
          :index="stepIdx"
        />
      </ol>
    </nav>
  </div>
</template>

<script setup>
import ResourceImportStep from './ResourceImportStep.vue'

defineProps({ steps: { type: Object, required: true } })
</script>
