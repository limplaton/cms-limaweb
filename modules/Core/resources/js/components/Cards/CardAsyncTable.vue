<template>
  <BaseCard
    :card="card"
    :request-query-string="tableRequestLastQueryString"
    :reload-on-query-string-change="false"
    @retrieved="handleCardRetrievedEvent"
  >
    <template #actions>
      <slot name="actions" />
    </template>

    <AsyncTable
      ref="tableRef"
      class="[--gutter:theme(spacing.6)]"
      wrapper-class="px-6 overflow-hidden border-y border-neutral-900/5 bg-white dark:border-white/10 dark:bg-neutral-900"
      max-height="450px"
      :searchable="card.searchable"
      :table-id="card.uriKey"
      :float-first="card.floatingResource"
      :fields="fields"
      :initial-data="card.value"
      :request-query-string="cardRequestLastQueryString"
      :request-uri="`cards/${card.uriKey}`"
      stackable
      bleed
      sticky
      @data-loaded="tableRequestLastQueryString = $event.requestQueryString"
    >
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData" />
      </template>
    </AsyncTable>
  </BaseCard>
</template>

<script setup>
import { computed, ref } from 'vue'
import get from 'lodash/get'

import AsyncTable from '@/Core/components/Table/AsyncTable.vue'
import { useDates } from '@/Core/composables/useDates'

import BaseCard from './BaseCard.vue'

const props = defineProps({
  card: Object,
})

const { localizeIfDate } = useDates()

const tableRequestLastQueryString = ref({})
const cardRequestLastQueryString = ref({})
const tableRef = ref(null)

/**
 * Get the fields for the table
 *
 * @return {Array}
 */
const fields = computed(() => {
  return props.card.fields.map(field => {
    field.formatter = (value, key, item) => {
      return localizeIfDate(value, get(item, key))
    }

    return field
  })
})

/**
 * Card retrieve event
 *
 * @param  {Object} payload
 *
 * @return {Void}
 */
function handleCardRetrievedEvent(payload) {
  cardRequestLastQueryString.value = payload.requestQueryString
  // We must replace the actual table data as the card may have e.q. range
  // parameter which may cause the table data to change but because
  // the request is not performed via the table class, the data will remain the same as before the
  // request and this will make sure that the data is updated
  tableRef.value.replaceCollection(payload.card.value)
}

/**
 * Reload the tbale
 *
 * @return {Void}
 */
function reload() {
  tableRef.value.reload()
}

defineExpose({ reload })
</script>
