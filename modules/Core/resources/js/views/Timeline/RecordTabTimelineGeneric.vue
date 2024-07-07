<template>
  <BaseRecordTabTimelineItem
    :resource-name="resourceName"
    :resource-id="resourceId"
    :created-at="log.created_at"
    :is-pinned="log.is_pinned"
    :timelineable-id="log.id"
    :timeline-relationship="log.timeline_relation"
    :timeline-subject-key="resource.timeline_subject_key"
    :timelineable-key="log.timeline_key"
    :icon="log.properties.icon || 'User'"
    :heading="$t(log.properties.lang.key, langAttributes)"
  />
</template>

<script setup>
import { computed } from 'vue'
import get from 'lodash/get'

import { useApp } from '@/Core/composables/useApp'

import BaseRecordTabTimelineItem from './BaseRecordTabTimelineItem.vue'

const props = defineProps({
  log: { type: Object, required: true },
  resourceName: { type: String, required: true },
  resourceId: { type: [String, Number], required: true },
  resource: { type: Object, required: true },
})

const { locale } = useApp()

const langAttributes = computed(() => {
  // Create new object of the attributes
  // because we are mutating the store below
  let attributes = props.log.properties.lang.attrs

  if (!attributes) {
    return null
  }

  // Automatically add causer_name in case user attr is
  // provided with null value or the lang key has :user attribute but
  // user attribute is not provided
  if (
    (get(
      window.lang[locale.value],
      props.log.properties.lang.key.replace('::', '.')
    ).indexOf('{user}') > -1 &&
      Object.keys(attributes).indexOf('user') === -1) ||
    (Object.keys(attributes).indexOf('user') > -1 &&
      attributes['user'] === null)
  ) {
    // To avoid mutations errors, assign new object
    attributes = Object.assign({}, attributes, {
      user: props.log.causer_name,
    })
  }

  return attributes
})
</script>
