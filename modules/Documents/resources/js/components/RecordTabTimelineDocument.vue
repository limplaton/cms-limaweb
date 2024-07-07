<template>
  <BaseRecordTabTimelineItem
    heading-class="font-medium"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :created-at="log.created_at"
    :is-pinned="log.is_pinned || false"
    :timelineable-id="log.id"
    :timeline-relationship="log.timeline_relation"
    :timeline-subject-key="resource.timeline_subject_key"
    :timelineable-key="log.timeline_key"
    :icon="log.type.icon"
    :heading="$t('documents::document.timeline.heading')"
  >
    <RelatedDocument
      class="mt-3"
      :document-id="log.id"
      :type-id="log.document_type_id"
      :status="log.status"
      :display-name="log.display_name"
      :path="log.path"
      :public-url="log.public_url"
      :accepted-at="log.accepted_at"
      :last-date-sent="log.last_date_sent"
      :amount="log.amount"
      :authorizations="log.authorizations"
      :associations-count="log.associations_count"
      :via-resource="resourceName"
      :via-resource-id="resourceId"
      :related-resource="resource"
    />
  </BaseRecordTabTimelineItem>
</template>

<script setup>
import BaseRecordTabTimelineItem from '@/Core/views/Timeline/BaseRecordTabTimelineItem.vue'

import RelatedDocument from './RelatedDocument.vue'

defineProps({
  log: { type: Object, required: true },
  resourceName: { type: String, required: true },
  resourceId: { type: [String, Number], required: true },
  resource: { type: Object, required: true },
})
</script>
