<template>
  <div ref="chartRef" class="ct-chart" />
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { BarChart } from 'chartist'
import ChartistTooltip from 'chartist-plugin-tooltips-updated'

import { useAccounting } from '@/Core/composables/useAccounting'

import 'chartist/dist/index.css'
import 'chartist-plugin-tooltips-updated/dist/chartist-plugin-tooltip.css'

const props = defineProps({
  chartData: null,
  amountValue: null,
  horizontal: null,
  axisYOffset: { type: Number, default: 30 },
  axisXOffset: { type: Number, default: 30 },
  onlyInteger: { type: Boolean, default: true },
})

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
  chartist = new BarChart(
    chartRef.value,
    props.chartData,
    {
      horizontalBars: props.horizontal,
      fullWidth: true,
      axisY: {
        offset: props.axisYOffset,
        onlyInteger: props.onlyInteger,
      },
      axisX: {
        onlyInteger: props.onlyInteger,
        offset: props.axisXOffset,
      },
      plugins: [
        ChartistTooltip({
          pointClass: 'ct-bar',
          anchorToPoint: false,
          appendToBody: false,
          transformTooltipTextFnc: value => {
            return props.amountValue ? formatMoney(value) : value
          },
        }),
      ],
    },
    [
      [
        'screen and (max-width: 1025px)',
        {
          axisX: {
            labelInterpolationFnc: function (value) {
              if (typeof value === 'number') {
                return value
              }

              return value[0]
            },
          },
          axisY: {
            offset: 20,
            labelInterpolationFnc: function (value) {
              if (typeof value === 'number') {
                return value
              }

              return value[0]
            },
          },
        },
      ],
    ]
  )

  chartist.on('draw', context => {
    if (context.type === 'bar') {
      let color = context.series[context.index].color

      if (color) {
        context.element.attr({
          style: `stroke: ${color} !important`,
        })
      }
    }
  })
})

onBeforeUnmount(destroy)
</script>
