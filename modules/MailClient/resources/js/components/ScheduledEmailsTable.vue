<template>
  <AsyncTable
    ref="tableRef"
    table-id="scheduled-emails"
    request-uri="emails/scheduled"
    :fields="fields"
    :searchable="false"
  >
    <template #scheduled_at="{ row }">
      {{ localizedDateTime(row.scheduled_at) }}
      <ITextSmall v-if="row.sent_at" class="block">
        {{ $t('mailclient::schedule.sent_at') }}:
        {{ localizedDateTime(row.sent_at) }}
      </ITextSmall>
    </template>

    <template #status="{ row }">
      <IBadge
        :variant="statusBadgeVariantMap[row.status]"
        :text="$t('mailclient.schedule.statuses.' + row.status)"
      />
    </template>

    <template #scheduled_by="{ row }">
      {{ row.user.name }}
      <ITextSmall class="block">
        {{ localizedDateTime(row.created_at) }}
      </ITextSmall>
    </template>

    <template #to="{ row }">
      <span
        v-for="to in row.to"
        :key="to.address"
        class="block"
        :title="to.address"
      >
        {{ to.name || to.address }}
      </span>
    </template>

    <template #actions="{ row }">
      <div
        v-show="
          !emailBeingSent || (emailBeingSent && emailBeingSent !== row.id)
        "
      >
        <ITableRowActions
          v-if="row.status !== 'sending'"
          :disabled="Boolean(emailBeingSent)"
        >
          <ITableRowAction
            v-if="row.authorizations.delete"
            :text="
              $t(
                row.status === 'pending'
                  ? 'mailclient::schedule.cancel_and_delete'
                  : 'core::app.delete'
              )
            "
            :confirm-text="$t('core::app.confirm')"
            :confirmable="isFloating"
            @confirmed="destroy(row.id, false)"
            @click="!isFloating ? destroy(row.id) : undefined"
          />

          <ITableRowAction
            v-if="row.status !== 'sent'"
            :text="$t('mailclient::schedule.send_now')"
            @click="sendNow(row.id)"
          />
        </ITableRowActions>
      </div>

      <ISpinner
        v-if="emailBeingSent && emailBeingSent == row.id"
        class="size-4 text-success-500"
      />
    </template>

    <template #before-row="{ row }">
      <ITableRow v-if="row.fail_reason || row.retry_after">
        <ITableCell colspan="6">
          <IAlert
            class="-mx-6 -my-3 rounded-none"
            :variant="row.retry_after ? 'warning' : 'danger'"
          >
            <IAlertBody>
              <p v-if="row.retry_after">
                {{
                  $t('mailclient::schedule.will_retry_at', {
                    date: localizedDateTime(row.retry_after),
                  })
                }}
              </p>

              <p>
                {{ row.fail_reason }}
              </p>
            </IAlertBody>
          </IAlert>
        </ITableCell>
      </ITableRow>
    </template>
  </AsyncTable>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import AsyncTable from '@/Core/components/Table/AsyncTable.vue'
import { useDates } from '@/Core/composables/useDates'

defineProps({ isFloating: Boolean })
const emit = defineEmits(['sent', 'deleted'])

const statusBadgeVariantMap = {
  sent: 'success',
  sending: 'neutral',
  pending: 'info',
  failed: 'danger',
}

const { localizedDateTime } = useDates()
const { t } = useI18n()

const tableRef = ref(null)
const emailBeingSent = ref(null)

const fields = ref([
  {
    key: 'subject',
    label: t('mailclient::inbox.subject'),
    tdClass: 'font-medium',
  },
  { key: 'to', label: t('mailclient::inbox.to') },
  { key: 'scheduled_by', label: t('mailclient::schedule.scheduled_by') },
  {
    key: 'scheduled_at',
    label: t('mailclient::schedule.scheduled_at'),
    sortable: true,
  },
  { key: 'status', label: t('mailclient::schedule.status') },
  { key: 'actions', label: '' },
])

async function sendNow(id) {
  emailBeingSent.value = id
  await Innoclapps.request().post(`/emails/scheduled/${id}/send`)
  emailBeingSent.value = null
  emit('sent', id)
  tableRef.value.reload()
}

async function destroy(id, confirm = true) {
  if (confirm) {
    await Innoclapps.confirm()
  }
  await Innoclapps.request().delete(`/emails/scheduled/${id}`)
  emit('deleted', id)
  tableRef.value.reload()
}
</script>
