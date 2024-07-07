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
    :icon="type.icon"
    :heading="$t('activities::activity.timeline.heading')"
  >
    <div class="mt-3">
      <RelatedActivity
        :activity-id="log.id"
        :title="log.title"
        :comments-count="log.comments_count"
        :is-completed="log.is_completed"
        :is-reminded="log.is_reminded"
        :is-due="log.is_due"
        :type-id="log.activity_type_id"
        :user-id="log.user_id"
        :note="log.note"
        :description="log.description"
        :reminder-minutes-before="log.reminder_minutes_before"
        :due-date="log.due_date"
        :end-date="log.end_date"
        :attachments-count="log.media.length"
        :media="log.media"
        :authorizations="log.authorizations"
        :comments="log.comments || []"
        :associations-count="log.associations_count"
        :via-resource="resourceName"
        :via-resource-id="resourceId"
        :related-resource="resource"
      />
    </div>
  </BaseRecordTabTimelineItem>
</template>

<script setup>
import { computed } from 'vue'

import BaseRecordTabTimelineItem from '@/Core/views/Timeline/BaseRecordTabTimelineItem.vue'

import { useActivityTypes } from '../composables/useActivityTypes'

import RelatedActivity from './RelatedActivity.vue'

const props = defineProps({
  log: { type: Object, required: true },
  resourceName: { type: String, required: true },
  resourceId: { type: [String, Number], required: true },
  resource: { type: Object, required: true },
})

const { findTypeById } = useActivityTypes()
const type = computed(() => findTypeById(props.log.activity_type_id))
</script>
