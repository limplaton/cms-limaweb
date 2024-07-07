<template>
  <VDatePicker
    title-position="left"
    color="primary"
    :locale="locale"
    :is24hr="!usesTwelveHourTime"
    :is-dark="isDarkMode"
    :timezone="userTimezone"
    :hide-time-header="true"
    :popover="{
      visibility: 'focus',
      positionFixed: true,
    }"
  >
    <template v-for="(_, name) in $slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData" />
    </template>
  </VDatePicker>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue'

import { useApp } from '@/Core/composables/useApp'
import { getLocale } from '@/Core/utils'

import AsyncComponentLoader from '../AsyncComponentLoader.vue'

const props = defineProps({
  isDate: { type: Boolean, required: true },
  isDateTime: { type: Boolean, required: true },
})

const VDatePicker = defineAsyncComponent({
  loader: () => import('v-calendar').then(module => module.DatePicker),
  loadingComponent: AsyncComponentLoader,
})

import { useDates } from '@/Core/composables/useDates'

import 'v-calendar/dist/style.css'

const { usesTwelveHourTime, userTimezone } = useDates()
const { isDarkMode } = useApp()

const masks = computed(() => {
  let masks = {}

  if (props.isDate) {
    // masks.input = dateFormatForMoment.value
    masks.modelValue = 'YYYY-MM-DD'
  } else if (props.isDateTime) {
    masks.modelValue = 'YYYY-MM-DD HH:mm:ss'
  } else {
    // TODO time, not yet used
  }

  return masks
})

const locale = computed(() => {
  return {
    masks: masks.value,
    id: getLocale().replace('_', '-'),
  }
})
</script>

<style>
.vc-time-picker {
  @apply bg-neutral-50 dark:bg-neutral-800;
}
/* https://vcalendar.io/calendar/theme.html#css-variables */
.vc-primary {
  --vc-accent-50: rgba(var(--color-primary-50));
  --vc-accent-100: rgba(var(--color-primary-100));
  --vc-accent-200: rgba(var(--color-primary-200));
  --vc-accent-300: rgba(var(--color-primary-300));
  --vc-accent-400: rgba(var(--color-primary-400));
  --vc-accent-500: rgba(var(--color-primary-500));
  --vc-accent-600: rgba(var(--color-primary-600));
  --vc-accent-700: rgba(var(--color-primary-700));
  --vc-accent-800: rgba(var(--color-primary-800));
  --vc-accent-900: rgba(var(--color-primary-900));
}

.vc-light {
  --vc-weekday-color: rgba(var(--color-neutral-400), 1);
  --vc-time-picker-border: rgba(var(--color-neutral-300), 1);
  /* Time select group */
  --vc-time-select-group-bg: rgba(var(--color-neutral-50), 1);
  --vc-time-select-group-border: rgba(var(--color-neutral-300), 1);
  /* Base select */
  --vc-select-color: rgba(var(--color-neutral-900), 1);
  --vc-select-bg: rgba(var(--color-neutral-100), 1);
  --vc-select-hover-bg: rgba(var(--color-neutral-200), 1);
}

.vc-time-select-group select {
  border: 0;
  border-radius: 0;
}
</style>
