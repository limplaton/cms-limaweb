<template>
  <IModal size="xxl" :title="$t('mailclient::schedule.scheduled_emails')">
    <div class="[&>[data-slot=header]]:-mx-4 sm:[&>[data-slot=header]]:-mx-6">
      <ScheduledEmailsTable
        class="[--gutter:theme(spacing.6)] sm:[--gutter:theme(spacing.8)]"
        :request-query-string="requestQueryString"
        is-floating
        bleed
        @sent="$emit('sent', $event)"
        @deleted="$emit('deleted', $event)"
      />
    </div>

    <template #modal-footer="{ cancel }">
      <div class="text-right">
        <IButton
          variant="secondary"
          :text="$t('core::app.hide')"
          @click="cancel"
        />
      </div>
    </template>
  </IModal>
</template>

<script setup>
import ScheduledEmailsTable from './ScheduledEmailsTable.vue'

defineProps({ requestQueryString: Object })

defineEmits(['sent', 'deleted'])
</script>
