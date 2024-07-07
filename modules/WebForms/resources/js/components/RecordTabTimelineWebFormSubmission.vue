<template>
  <BaseRecordTabTimelineItem
    heading-class="font-medium"
    icon="Bars3BottomLeft"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :created-at="log.created_at"
    :is-pinned="log.is_pinned"
    :timelineable-id="log.id"
    :timeline-relationship="log.timeline_relation"
    :timeline-subject-key="resource.timeline_subject_key"
    :timelineable-key="log.timeline_key"
    :heading="$t('webforms::form.submission')"
  >
    <ICard v-once class="mt-2">
      <ICardBody>
        <ITextDark class="mb-2 font-semibold" :text="log.description" />

        <template v-for="(property, index) in log.properties" :key="index">
          <ITextBlockDark class="flex justify-start font-semibold">
            <span>{{ resources[property.resourceName].singularLabel }} /</span>
            <!-- eslint-disable-next-line vue/no-v-html -->
            <span class="ml-1 font-medium" v-html="property.label" />
          </ITextBlockDark>

          <IText class="mb-4 last:mb-0">
            {{
              property.value === null
                ? '/'
                : localizeIfDate(property.value) || property.value
            }}
          </IText>
        </template>
      </ICardBody>
    </ICard>
  </BaseRecordTabTimelineItem>
</template>

<script setup>
import { useDates } from '@/Core/composables/useDates'
import BaseRecordTabTimelineItem from '@/Core/views/Timeline/BaseRecordTabTimelineItem.vue'

defineProps({
  log: { type: Object, required: true },
  resourceName: { type: String, required: true },
  resourceId: { type: [String, Number], required: true },
  resource: { type: Object, required: true },
})

const { localizeIfDate } = useDates()

const resources = Innoclapps.resources()
</script>
