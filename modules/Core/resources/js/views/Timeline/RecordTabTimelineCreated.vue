<template>
  <BaseRecordTabTimelineItem
    icon="User"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :created-at="log.created_at"
    :is-pinned="log.is_pinned"
    :timelineable-id="log.id"
    :timeline-relationship="log.timeline_relation"
    :timeline-subject-key="resource.timeline_subject_key"
    :timelineable-key="log.timeline_key"
  >
    <template #heading>
      <I18nT v-once scope="global" keypath="core::timeline.created">
        <template #causer>
          <span class="font-medium" v-text="log.causer_name"></span>
        </template>
      </I18nT>
    </template>
  </BaseRecordTabTimelineItem>
</template>

<script setup>
import BaseRecordTabTimelineItem from './BaseRecordTabTimelineItem.vue'

defineProps({
  log: { type: Object, required: true },
  resourceName: { type: String, required: true },
  resourceId: { type: [String, Number], required: true },
  resource: { type: Object, required: true },
})
</script>
