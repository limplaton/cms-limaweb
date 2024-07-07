<template>
  <MainLayout>
    <ICard class="mx-auto max-w-6xl">
      <ICardHeader>
        <ICardHeading :text="$t('mailclient::schedule.scheduled_emails')" />
      </ICardHeader>

      <ScheduledEmailsTable
        wrapper-class="px-6"
        class="[--gutter:theme(spacing.6)]"
        bleed
      />
    </ICard>
  </MainLayout>
</template>

<script setup>
import ScheduledEmailsTable from '../components/ScheduledEmailsTable.vue'
</script>
