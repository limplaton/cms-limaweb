<template>
  <div :class="{ 'sync-stopped-by-system': isSyncStopped }">
    <ResourceTable
      ref="tableRef"
      :resource-name="resourceName"
      :table-id="tableId"
      :action-request-params="actionRequestParams"
      :data-request-query-string="dataRequestQueryString"
    >
      <template #subject="{ row }">
        <div class="inline-flex max-w-[380px] flex-nowrap items-center">
          <div class="block min-w-0 truncate">
            <InboxMessageSubject
              :subject="row.subject"
              :message-id="row.id"
              :account-id="accountId"
            />
          </div>

          <div
            v-if="row.tags"
            class="ml-2 inline-flex shrink-0 space-x-0.5 group-hover/td:hidden"
          >
            <IBadge
              v-for="tag in row.tags"
              :key="tag.id"
              :color="tag.swatch_color"
              :text="tag.name"
            />
          </div>
        </div>
      </template>

      <template #to="{ row }">
        <div v-for="(address, index) in row.to" :key="index">
          <MessageAddress :address="address" />
        </div>

        <span
          v-if="!row.to || row.to.length === 0"
          v-text="'(' + $t('mailclient::inbox.unknown_address') + ')'"
        />
      </template>

      <template #date="{ row }">
        <span :title="localizedDateTime(row.date)">
          {{ formatMessageDate(row.date) }}
        </span>
      </template>
    </ResourceTable>
  </div>
</template>

<script setup>
import { ref } from 'vue'

import { useDates } from '@/Core/composables/useDates'

import MessageAddress from '../Emails/MessageAddress.vue'

import InboxMessageSubject from './InboxMessageSubject.vue'

defineProps({
  tableId: { required: true, type: String },
  dataRequestQueryString: { type: Object, required: true },
  actionRequestParams: { type: Object, required: true },
  accountId: { type: Number, required: true },
  isSyncStopped: Boolean,
})

const resourceName = Innoclapps.resourceName('emails')

const { DateTime, localizedTime, localizedDate, localizedDateTime } = useDates()

const tableRef = ref(null)

function formatMessageDate(date) {
  const dateTimeInstance = DateTime.fromISO(date)

  return dateTimeInstance.hasSame(DateTime.now(), 'day')
    ? localizedTime(dateTimeInstance.toISO())
    : localizedDate(dateTimeInstance.toISO())
}

defineExpose({ tableRef })
</script>

<style>
.read td {
  @apply !font-normal;
}
.unread td {
  @apply !bg-neutral-50/80 !font-semibold dark:!bg-neutral-800;
}
</style>
