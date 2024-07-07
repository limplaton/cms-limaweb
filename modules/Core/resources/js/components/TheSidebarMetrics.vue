<template>
  <div class="group mt-8 px-2">
    <h3
      v-once
      id="metrics-headline"
      v-i-tooltip.right="
        $t('core::app.menu.metrics.refresh_interval', { interval: 10 })
      "
      class="hidden px-3 text-xs font-medium uppercase tracking-wider text-neutral-50 group-hover:inline-flex group-hover:items-center"
    >
      <Icon icon="QuestionMarkCircle" class="mr-2 size-5"></Icon>
      {{ $t('core::app.menu.metrics.metrics') }}
    </h3>

    <div class="mt-1 space-y-1" role="group" aria-labelledby="metrics-headline">
      <ILink
        v-for="metric in metrics"
        :key="metric.name"
        v-memo="[metric.count]"
        class="group flex items-center rounded-md px-3 py-2 text-base font-medium text-neutral-50 hover:bg-neutral-600 sm:text-sm"
        :to="metric.route"
        plain
      >
        <span
          aria-hidden="true"
          :class="[
            metric.count > 0
              ? getBackgroundColorClass(metric)
              : 'bg-success-500',
            'mr-4 size-2.5 rounded-full',
          ]"
        />

        <span class="mr-1 truncate" v-text="metric.name"></span>
        ({{ metric.count }})
      </ILink>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, shallowRef } from 'vue'

import { useApp } from '../composables/useApp'

const { scriptConfig } = useApp()

const metrics = shallowRef(scriptConfig('menu.metrics'))

let intervalId = null

function fetch() {
  Innoclapps.request('/menu/metrics').then(({ data }) => {
    metrics.value = data
  })
}

function getBackgroundColorClass(metric) {
  if (metric.backgroundColorVariant === 'warning') return 'bg-warning-500'
  if (metric.backgroundColorVariant === 'danger') return 'bg-danger-500'
  if (metric.backgroundColorVariant === 'info') return 'bg-info-500'
  if (metric.backgroundColorVariant === 'primary') return 'bg-primary-500'
  if (metric.backgroundColorVariant === 'neutral') return 'bg-neutral-500'
}

onMounted(() => {
  intervalId = setInterval(fetch, 1000 * 60 * 10)
})

onBeforeUnmount(() => {
  clearInterval(intervalId)
})
</script>
