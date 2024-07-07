<template>
  <BaseRecordTabTimelineItem
    heading-class="font-medium"
    icon="Mail"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :created-at="log.created_at"
    :is-pinned="log.is_pinned"
    :timelineable-id="log.id"
    :timeline-relationship="log.timeline_relation"
    :timeline-subject-key="resource.timeline_subject_key"
    :timelineable-key="log.timeline_key"
    :heading="$t('mailclient::mail.message')"
  >
    <template #date>
      {{ localizedDateTime(log.date) }}
    </template>

    <div class="mt-3">
      <RelatedEmail
        :email="log"
        :via-resource="resourceName"
        :via-resource-id="resourceId"
        :related-resource="resource"
      />
    </div>
  </BaseRecordTabTimelineItem>
</template>

<script setup>
import { useDates } from '@/Core/composables/useDates'
import BaseRecordTabTimelineItem from '@/Core/views/Timeline/BaseRecordTabTimelineItem.vue'

import RelatedEmail from '../views/Emails/RelatedEmail.vue'

defineProps({
  log: { type: Object, required: true },
  resourceName: { type: String, required: true },
  resourceId: { type: [String, Number], required: true },
  resource: { type: Object, required: true },
})

const { localizedDateTime } = useDates()
</script>
