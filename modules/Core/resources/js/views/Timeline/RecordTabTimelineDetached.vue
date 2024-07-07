<template>
  <BaseRecordTabTimelineItem
    icon="XSolid"
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
      <I18nT
        v-once
        scope="global"
        keypath="core::timeline.detached"
        :user="log.causer_name"
      >
        <template #user>
          <span class="font-medium" v-text="log.causer_name"></span>
        </template>
      </I18nT>

      <ILink
        class="ml-1 font-medium"
        :to="log.properties.path"
        :text="log.properties.name"
      />
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
