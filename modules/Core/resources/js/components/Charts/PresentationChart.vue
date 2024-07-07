<template>
  <BaseCard
    class="has-[[data-slot=empty]]:h-52 md:h-52"
    :card="card"
    :request-query-string="requestQueryString"
    @retrieved="result = $event.card.value"
  >
    <template v-for="(_, name) in $slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData" />
    </template>

    <div class="relative" :class="variant">
      <BasePresentationChart
        v-if="hasChartData"
        class="px-1"
        :chart-data="chartData"
        :amount-value="card.amount_value"
        :horizontal="card.horizontal"
        :only-integer="card.onlyInteger"
        :axis-y-offset="card.axisYOffset"
        :axis-x-offset="card.axisXOffset"
      />

      <IText
        v-else
        data-slot="empty"
        class="mt-10 text-center sm:mt-12"
        :text="$t('core::app.not_enough_data')"
      />
    </div>
  </BaseCard>
</template>

<script setup>
import { computed, shallowRef } from 'vue'

import BaseCard from '../Cards/BaseCard.vue'

import BasePresentationChart from './Base/BasePresentationChart.vue'
import { hasData, resultToChartData } from './utils'

const props = defineProps({
  card: { required: true, type: Object },
  requestQueryString: {
    type: Object,
    default: () => ({}),
  },
})

const result = shallowRef(props.card.value)
const variant = computed(() => props.card.color || 'chart-primary')
const chartData = computed(() => resultToChartData(result.value))
const hasChartData = computed(() => hasData(chartData.value))
</script>
