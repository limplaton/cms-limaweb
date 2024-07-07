<template>
  <div ref="chartRef" class="ct-chart" />
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Interpolation, LineChart } from 'chartist'
import ChartistTooltip from 'chartist-plugin-tooltips-updated'

import { useAccounting } from '@/Core/composables/useAccounting'

import 'chartist/dist/index.css'
import 'chartist-plugin-tooltips-updated/dist/chartist-plugin-tooltip.css'

const props = defineProps(['chartData', 'amountValue'])

let chartist = null
const chartRef = ref(null)

const { formatMoney } = useAccounting()

function refreshChart() {
  chartist.update(props.chartData)
}

function destroy() {
  if (chartist) {
    chartist.detach()
  }
}

watch(() => props.chartData, refreshChart)

onMounted(() => {
  chartist = new LineChart(chartRef.value, props.chartData, {
    lineSmooth: Interpolation.none(),
    fullWidth: true,
    showPoint: true,
    showLine: true,
    showArea: true,
    chartPadding: {
      top: 10,
      right: 0.5,
      bottom: 0.5,
      left: 0.5,
    },
    low: 0,
    axisX: {
      showGrid: false,
      showLabel: true,
      offset: 0,
    },
    axisY: {
      showGrid: false,
      showLabel: true,
      offset: 0,
    },
    plugins: [
      ChartistTooltip({
        pointClass: 'ct-point',
        anchorToPoint: false,
        appendToBody: false,
        transformTooltipTextFnc: value => {
          return props.amountValue ? formatMoney(value) : value
        },
      }),
    ],
  })
})

onBeforeUnmount(destroy)
</script>
