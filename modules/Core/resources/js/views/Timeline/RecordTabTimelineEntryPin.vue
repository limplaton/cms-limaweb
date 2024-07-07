<template>
  <ILink
    v-if="!isPinned"
    class="text-sm/6 sm:text-xs/6"
    :text="$t('core::timeline.pin')"
    basic
    @click="pin"
  />

  <ILink
    v-else
    class="text-sm/6 sm:text-xs/6"
    :text="$t('core::timeline.unpin')"
    basic
    @click="unpin"
  />
</template>

<script setup>
import { inject } from 'vue'

const props = defineProps({
  resourceName: { type: String, required: true },
  resourceId: { type: [String, Number], required: true },
  isPinned: { type: Boolean, required: true },

  timelineSubjectKey: { type: String, required: true },
  timelineRelationship: { type: String, required: true },

  timelineableKey: { type: String, required: true },
  timelineableId: { type: [Number, String], required: true },
})

const synchronizeResource = inject('synchronizeResource')

function pin() {
  Innoclapps.request()
    .post('timeline/pin', {
      subject_id: parseInt(props.resourceId),
      subject_type: props.timelineSubjectKey,
      timelineable_id: parseInt(props.timelineableId),
      timelineable_type: props.timelineableKey,
    })
    .then(() => {
      synchronizeResource({
        [props.timelineRelationship]: {
          id: props.timelineableId,
          is_pinned: true,
          pinned_date: new Date().toISOString(), // toISOString allowing consistency with the back-end dates
        },
      })
    })
}

function unpin() {
  Innoclapps.request()
    .post('timeline/unpin', {
      subject_id: parseInt(props.resourceId),
      subject_type: props.timelineSubjectKey,
      timelineable_id: parseInt(props.timelineableId),
      timelineable_type: props.timelineableKey,
    })
    .then(() => {
      synchronizeResource({
        [props.timelineRelationship]: {
          id: props.timelineableId,
          is_pinned: false,
          pinned_date: null,
        },
      })
    })
}
</script>
