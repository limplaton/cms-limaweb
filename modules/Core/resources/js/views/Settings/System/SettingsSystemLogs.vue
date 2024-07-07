<template>
  <ICardHeader>
    <ICardHeading text="Logs" />

    <ICardActions>
      <IFormSelect
        v-model="logType"
        :option="logTypes"
        @input="$router.replace({ query: { type: $event, date: date } })"
      >
        <option v-for="_logType in logTypes" :key="_logType" :value="_logType">
          {{ _logType }}
        </option>
      </IFormSelect>

      <IFormSelect
        v-model="date"
        @input="$router.replace({ query: { date: $event, type: logType } })"
      >
        <option v-for="_date in log.log_dates" :key="_date" :value="_date">
          {{ _date }}
        </option>
      </IFormSelect>
    </ICardActions>
  </ICardHeader>

  <ICard header="Logs">
    <div class="px-6">
      <ITable class="[--gutter:theme(spacing.6)]" bleed condensed>
        <ITableHead>
          <ITableRow>
            <ITableHeader>Log</ITableHeader>
          </ITableRow>
        </ITableHead>

        <ITableBody>
          <ITableRow v-for="(line, index) in filteredLogs" :key="index">
            <ITableCell width="60%" class="whitespace-pre-line break-all">
              <div class="mb-3 flex justify-between py-1">
                <div class="inline-flex items-center gap-x-2">
                  <IBadge
                    :text="line.type"
                    :variant="logTypesClassMaps[line.type]"
                  />

                  <IButtonCopy :text="line.message" small />
                </div>

                <p v-text="line.timestamp" />
              </div>

              <code>
                <TextCollapse :text="line.message" :length="500" />
              </code>
            </ITableCell>
          </ITableRow>

          <ITableCell v-show="!hasLogs" colspan="4" class="text-center">
            {{ log.message || 'No logs to show.' }}
          </ITableCell>
        </ITableBody>
      </ITable>
    </div>
  </ICard>
</template>

<script setup>
import { computed, ref, shallowRef, watch } from 'vue'
import { useRoute } from 'vue-router'

import { useDates } from '@/Core/composables/useDates'

const route = useRoute()

const log = shallowRef({})

const { UTCDateTimeInstance } = useDates()

const date = ref(route.query.date || UTCDateTimeInstance.toISODate())
const logType = ref(route.query.type || 'ALL')

const logTypes = [
  'ALL',
  'INFO',
  'EMERGENCY',
  'CRITICAL',
  'ALERT',
  'ERROR',
  'WARNING',
  'NOTICE',
  'DEBUG',
]

const logTypesClassMaps = {
  INFO: 'info',
  DEBUG: 'neutral',
  EMERGENCY: 'danger',
  CRITICAL: 'danger',
  NOTICE: 'neutral',
  WARNING: 'warning',
  ERROR: 'danger',
  ALERT: 'warning',
}

watch(date, retrieve)

const filteredLogs = computed(() => {
  if (!log.value.logs) {
    return []
  }

  if (!logType.value || logType.value === 'ALL') {
    return sortLogsByDate(log.value.logs)
  }

  return sortLogsByDate(log.value.logs.filter(l => l.type === logType.value))
})

const hasLogs = computed(
  () => filteredLogs.value && filteredLogs.value.length > 0
)

function sortLogsByDate(logs) {
  return logs.sort(function compare(a, b) {
    var dateA = new Date(a.timestamp)
    var dateB = new Date(b.timestamp)

    return dateB - dateA
  })
}

function retrieve() {
  Innoclapps.request('/system/logs', {
    params: {
      date: date.value,
    },
  }).then(({ data }) => {
    log.value = data

    if (data.log_dates.indexOf(date.value) === -1) {
      log.value.log_dates.push(date.value)
    }
  })
}

retrieve()
</script>
