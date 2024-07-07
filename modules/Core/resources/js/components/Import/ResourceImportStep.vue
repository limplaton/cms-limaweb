<template>
  <li class="relative overflow-hidden lg:flex-1">
    <div
      :class="[
        index === 0 ? 'border-b-0' : '',
        index === total - 1 ? 'border-t-0' : '',
        'overflow-hidden border-y border-neutral-200 dark:border-neutral-500/30 lg:border-0',
      ]"
    >
      <div v-if="step.status === 'complete'">
        <ResourceImportStepTail />

        <ResourceImportStepItem :index="index">
          <ResourceImportStepItemCircle
            :class="index !== total - 1 ? 'bg-primary-600' : 'bg-success-500'"
          >
            <Icon icon="Check" class="size-6 text-white" />
          </ResourceImportStepItemCircle>

          <ResourceImportStepItemInfo :step="step" />
        </ResourceImportStepItem>
      </div>

      <div v-else-if="step.status === 'current'" aria-current="step">
        <ResourceImportStepTail is-current />

        <ResourceImportStepItem :index="index">
          <ResourceImportStepItemCircle
            class="border-2 border-primary-600 dark:border-primary-300"
          >
            <span class="text-primary-600 dark:text-primary-300">
              {{ step.id }}
            </span>
          </ResourceImportStepItemCircle>

          <ResourceImportStepItemInfo :step="step" />
        </ResourceImportStepItem>
      </div>

      <div v-else>
        <ResourceImportStepTail />

        <ResourceImportStepItem :index="index">
          <ResourceImportStepItemCircle
            class="border-2 border-neutral-300 dark:border-neutral-400"
          >
            <span class="text-neutral-500 dark:text-neutral-300">
              {{ step.id }}
            </span>
          </ResourceImportStepItemCircle>

          <ResourceImportStepItemInfo :step="step" />
        </ResourceImportStepItem>
      </div>

      <template v-if="index !== 0">
        <!-- Separator -->
        <div
          class="absolute inset-0 left-0 top-0 hidden w-3 lg:block"
          aria-hidden="true"
        >
          <svg
            class="h-full w-full text-neutral-300 dark:text-neutral-600"
            viewBox="0 0 12 82"
            fill="none"
            preserveAspectRatio="none"
          >
            <path
              d="M0.5 0V31L10.5 41L0.5 51V82"
              stroke="currentcolor"
              vector-effect="non-scaling-stroke"
            />
          </svg>
        </div>
      </template>
    </div>
  </li>
</template>

<script setup>
import ResourceImportStepItem from './ResourceImportStepItem.vue'
import ResourceImportStepItemCircle from './ResourceImportStepItemCircle.vue'
import ResourceImportStepItemInfo from './ResourceImportStepItemInfo.vue'
import ResourceImportStepTail from './ResourceImportStepTail.vue'

defineProps({
  step: { type: Object, required: true },
  total: { type: Number, required: true },
  index: { type: Number, required: true },
})
</script>
