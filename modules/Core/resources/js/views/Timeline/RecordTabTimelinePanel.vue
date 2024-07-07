<template>
  <ITabPanel>
    <div class="mt-4 inline-flex items-center sm:ml-1 sm:mt-2">
      <ITextDark
        v-t="'core::filters.filter_by'"
        class="-mt-0.5 mr-1.5 font-medium"
      />

      <IDropdown>
        <IDropdownButton :text="activeFilter.name" basic />

        <IDropdownMenu>
          <IDropdownItem
            v-for="filter in filters"
            :key="filter.id"
            :text="filter.name"
            :active="activeFilter.id === filter.id"
            @click="(activeFilter = filter), loadData()"
          />
        </IDropdownMenu>
      </IDropdown>
    </div>

    <div class="pt-6">
      <div class="flow-root">
        <ul role="list" class="sm:-mb-6">
          <li
            v-for="(entry, index) in timeline"
            :key="'timeline-' + entry.timeline_component + '-' + entry.id"
          >
            <div class="relative sm:pb-6">
              <span
                v-if="index !== timeline.length - 1"
                class="absolute left-5 top-5 -ml-px hidden h-full w-0.5 bg-neutral-200 dark:bg-neutral-700 sm:block"
                aria-hidden="true"
              />

              <div class="relative flex items-start sm:space-x-3">
                <component
                  :is="
                    Object.hasOwn(
                      timelineComponents,
                      'timeline-' + entry.timeline_component
                    )
                      ? timelineComponents[
                          'timeline-' + entry.timeline_component
                        ]
                      : entry.timeline_component
                  "
                  :log="entry"
                  :resource-name="resourceName"
                  :resource-id="resourceId"
                  :resource="resource"
                />
              </div>
            </div>

            <div
              v-if="index !== timeline.length - 1"
              class="block sm:hidden"
              aria-hidden="true"
            >
              <div class="py-5">
                <div
                  class="border-t border-neutral-200 dark:border-neutral-500/30"
                />
              </div>
            </div>
          </li>
        </ul>

        <InfinityLoader
          ref="infinityRef"
          :scroll-element="scrollElement"
          @handle="infiniteHandler($event)"
        />
      </div>
    </div>
  </ITabPanel>
</template>

<script setup>
import { computed, inject, onMounted, ref, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { watchDebounced } from '@vueuse/core'
import groupBy from 'lodash/groupBy'
import orderBy from 'lodash/orderBy'

import InfinityLoader from '@/Core/components/InfinityLoader.vue'
import { useRecordTab } from '@/Core/composables/useRecordTab'

import TimelineAttached from './RecordTabTimelineAttached.vue'
import TimelineCreated from './RecordTabTimelineCreated.vue'
import TimelineDeleted from './RecordTabTimelineDeleted.vue'
import TimelineDetached from './RecordTabTimelineDetached.vue'
import TimelineGeneric from './RecordTabTimelineGeneric.vue'
import TimelineImported from './RecordTabTimelineImported.vue'
import TimelineRestored from './RecordTabTimelineRestored.vue'
import TimelineUpdated from './RecordTabTimelineUpdated.vue'

const props = defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
  scrollElement: { type: String },
})

const synchronizeResource = inject('synchronizeResource')

const timelineComponents = {
  'timeline-restored': TimelineRestored,
  'timeline-deleted': TimelineDeleted,
  'timeline-created': TimelineCreated,
  'timeline-updated': TimelineUpdated,
  'timeline-attached': TimelineAttached,
  'timeline-detached': TimelineDetached,
  'timeline-imported': TimelineImported,
  'timeline-generic': TimelineGeneric,
}

const timelineRelation = 'changelog'

const { t } = useI18n()
const infinityRef = ref(null)

const { loadData, infiniteHandler, search, refresh } = useRecordTab({
  resourceName: props.resourceName,
  resource: toRef(props, 'resource'),
  scrollElement: props.scrollElement,
  timelineRelation,
  synchronizeResource,
  infinityRef,
  handleInfinityResult,
  makeRequestForData,
})

/**
 * We will use a watcher for the resource "updated_at" attrribute
 * to retieve again the first page of the timeline for the current resource
 *
 * The check is performed e.q. if new activities are created from workflows, changelog is added etc...
 */
watchDebounced(
  () => props.resource._sync_timestamp,
  () => refresh(),
  { debounce: 500 }
)

const resources = ref([])

const filters = computed(() => {
  return [
    { id: null, name: t('core::app.all') },
    { id: 'changelog', name: t('core::app.changelog') },
    ...resources.value.map(resource => ({
      id: resource.name,
      name: resource.label,
    })),
  ]
})

const activeFilter = ref({
  id: null,
  name: t('core::app.all'),
})

const changelog = computed(() => {
  // The changelog is returned too from the record request
  // these are the general changelog related to the model
  // in this case, when the record is updated the new changelog
  // are able to be reflected and shown in the tab
  return !activeFilter.value.id || activeFilter.value.id === 'changelog'
    ? props.resource.changelog || []
    : []
})

const timeline = computed(() => {
  const timelineData = [...changelog.value]

  resources.value.forEach(resource => {
    timelineData.push(
      ...(!activeFilter.value.id || activeFilter.value.id === resource.name
        ? props.resource[resource.timeline_relation] || []
        : [])
    )
  })

  return orderBy(
    timelineData,
    [
      'is_pinned',
      'pinned_date',
      log => new Date(log[log.timeline_sort_column]),
    ],
    ['desc', 'desc', 'desc']
  )
})

function makeRequestForData(page, perPage) {
  return Innoclapps.request(`${props.resource.path}/timeline`, {
    params: { page: page, per_page: perPage, q: search.value },
  })
}

function handleInfinityResult(data) {
  resources.value = data.resources
  synchronizeResource(groupBy(data.data, 'timeline_relation'))
}

onMounted(loadData)
</script>
