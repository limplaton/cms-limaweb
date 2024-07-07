<template>
  <ITabPanel :lazy="!dataLoadedFirstTime" @activated.once="loadData">
    <ICard v-show="!showCreateForm" class="rounded-t-none pt-5 sm:pt-0">
      <ICardBody>
        <div class="sm:flex sm:items-start sm:justify-between">
          <div>
            <ITextDark
              class="font-semibold"
              :text="$t('activities::activity.manage_activities')"
            />

            <IText class="max-w-xl" :text="$t('activities::activity.info')" />
          </div>

          <div class="mt-5 sm:ml-6 sm:mt-0">
            <IButton
              variant="primary"
              icon="PlusSolid"
              :text="$t('activities::activity.add')"
              @click="showCreateForm = true"
            />
          </div>
        </div>

        <SearchInput
          v-show="hasActivities || search"
          v-model="search"
          class="mt-4"
          @update:model-value="performSearch"
        />
      </ICardBody>
    </ICard>

    <CreateActivity
      v-if="showCreateForm"
      class="rounded-t-none"
      :via-resource="resourceName"
      :via-resource-id="resourceId"
      :related-resource="resource"
      @cancel="showCreateForm = false"
    />

    <div class="mt-3 sm:mt-7 sm:block">
      <div
        v-show="hasActivities"
        class="border-b border-neutral-200 dark:border-neutral-500/30"
      >
        <div class="flex items-center justify-center">
          <nav
            class="overlow-y-hidden -mb-px flex grow snap-x snap-mandatory overflow-x-auto sm:grow-0 sm:space-x-4 lg:space-x-6"
          >
            <ILink
              v-for="filter in filters"
              :key="filter.id"
              :class="[
                activeFilter === filter.id
                  ? 'border-neutral-700 text-neutral-700 dark:border-neutral-400 dark:text-neutral-200'
                  : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700 dark:text-neutral-100 dark:hover:border-neutral-500 dark:hover:text-neutral-300',
                'group inline-flex min-w-full shrink-0 snap-start snap-always items-center justify-center whitespace-nowrap border-b-2 px-1 py-4 text-base/5 font-medium sm:min-w-0 sm:text-sm/5',
              ]"
              plain
              @click="activateFilter(filter)"
            >
              {{ filter.title }} <span class="ml-2">({{ filter.total }})</span>
            </ILink>
          </nav>
        </div>
      </div>
    </div>

    <div class="py-2 sm:py-4">
      <ITextDark
        v-if="isFilterDataEmpty"
        class="mt-6 flex items-center justify-center font-medium"
      >
        <Icon
          :icon="activeFilterInstance.empty.icon"
          :class="['mr-2 size-5', activeFilterInstance.empty.iconClass]"
        />
        {{ activeFilterInstance.empty.text }}
      </ITextDark>

      <div class="mt-3 space-y-4">
        <div v-for="activity in activeFilterInstance.data" :key="activity.id">
          <RelatedActivity
            :activity-id="activity.id"
            :title="activity.title"
            :comments-count="activity.comments_count"
            :is-completed="activity.is_completed"
            :is-reminded="activity.is_reminded"
            :is-due="activity.is_due"
            :type-id="activity.activity_type_id"
            :user-id="activity.user_id"
            :note="activity.note"
            :description="activity.description"
            :reminder-minutes-before="activity.reminder_minutes_before"
            :due-date="activity.due_date"
            :end-date="activity.end_date"
            :attachments-count="activity.media.length"
            :media="activity.media"
            :associations-count="activity.associations_count"
            :authorizations="activity.authorizations"
            :comments="activity.comments || []"
            :via-resource="resourceName"
            :via-resource-id="resourceId"
            :related-resource="resource"
          />
        </div>
      </div>
    </div>

    <IText
      v-show="isSearching && !hasSearchResults"
      class="mt-6 text-center"
      :text="$t('core::app.no_search_results')"
    />

    <InfinityLoader
      ref="infinityRef"
      :scroll-element="scrollElement"
      @handle="infiniteHandler($event)"
    />
  </ITabPanel>
</template>

<script setup>
import { computed, inject, ref, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { watchOnce } from '@vueuse/core'
import orderBy from 'lodash/orderBy'

import InfinityLoader from '@/Core/components/InfinityLoader.vue'
import { useDates } from '@/Core/composables/useDates'
import { useRecordTab } from '@/Core/composables/useRecordTab'

import { useComments } from '@/Comments/composables/useComments'

import RelatedActivity from './RelatedActivity.vue'
import CreateActivity from './RelatedActivityCreate.vue'

const props = defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
  scrollElement: { type: String },
})

const synchronizeResource = inject('synchronizeResource')

const activeFilter = ref('all')
const infinityRef = ref(null)
const showCreateForm = ref(false)

const { DateTime, LocalDateTimeInstance, hasTime } = useDates()

const { t } = useI18n()
const route = useRoute()

const timelineRelation = 'activities'

const { commentsAreVisible } = useComments(
  route.query.resourceId,
  timelineRelation
)

const {
  dataLoadedFirstTime,
  focusToAssociateableElement,
  searchResults,
  isSearching,
  hasSearchResults,
  performSearch,
  search,
  loadData,
  infiniteHandler,
} = useRecordTab({
  resourceName: props.resourceName,
  resource: toRef(props, 'resource'),
  scrollElement: props.scrollElement,
  // Because of the filters badges totals, if the user has more then 15 activities, they won't be accurate
  perPage: 100,
  infinityRef,
  synchronizeResource,
  timelineRelation,
})

const activeFilterInstance = computed(() =>
  filters.value.find(filter => filter.id === activeFilter.value)
)

const todaysActivities = computed(() =>
  incompleteActivities.value.filter(d =>
    createDueDateTimeInstance(d.due_date).hasSame(LocalDateTimeInstance, 'day')
  )
)

const tomorrowActivities = computed(() =>
  incompleteActivities.value.filter(d =>
    createDueDateTimeInstance(d.due_date).hasSame(
      LocalDateTimeInstance.plus({ days: 1 }),
      'day'
    )
  )
)

const thisWeekActivities = computed(() =>
  incompleteActivities.value.filter(d =>
    createDueDateTimeInstance(d.due_date).hasSame(
      LocalDateTimeInstance,
      'week',
      { useLocaleWeeks: true }
    )
  )
)

const nextWeekActivities = computed(() =>
  incompleteActivities.value.filter(d =>
    createDueDateTimeInstance(d.due_date).hasSame(
      LocalDateTimeInstance.plus({ weeks: 1 }),
      'week',
      { useLocaleWeeks: true }
    )
  )
)

/**
 * Get the activities for the resource ordered by not completed on top and by due date
 */
const activities = computed(() =>
  orderBy(
    searchResults.value || props.resource.activities,
    [
      'is_completed',
      activity => createDueDateTimeInstance(activity.due_date).toJSDate(),
    ],
    ['asc', 'asc']
  )
)

/**
 * Get the currently incomplete activities from the loaded activities
 */
const incompleteActivities = computed(() =>
  activities.value.filter(activity => !activity.is_completed)
)

/**
 * Get the currently completed activities from the loaded activities
 */
const completedActivities = computed(() =>
  activities.value.filter(activity => activity.is_completed)
)

const hasActivities = computed(() => activities.value.length > 0)

const isFilterDataEmpty = computed(
  () =>
    activeFilterInstance.value.total === 0 &&
    dataLoadedFirstTime.value &&
    !(isSearching.value && !hasSearchResults.value)
)

/**
 * Activate the given filter
 */
function activateFilter(filter) {
  activeFilter.value = filter.id
  loadData()
}

/**
 * Create DateTime instance from the given due date.
 */
function createDueDateTimeInstance(date) {
  if (!hasTime(date)) {
    return DateTime.fromFormat(date, 'yyyy-MM-dd')
  }

  return DateTime.fromISO(date)
}

const filters = computed(() => [
  {
    id: 'all',
    title: t('activities::activity.filters.all'),
    data: activities.value,
    total: activities.value.length,
    empty: {
      text: t('core::app.all_caught_up'),
      icon: 'Check',
      iconClass: 'text-success-500',
    },
  },
  {
    id: 'today',
    title: t('activities::activity.filters.today'),
    data: todaysActivities.value,
    total: todaysActivities.value.length,
    empty: {
      text: t('core::app.all_caught_up'),
      icon: 'Check',
      iconClass: 'text-success-500',
    },
  },
  {
    id: 'tomorrow',
    title: t('activities::activity.filters.tomorrow'),
    data: tomorrowActivities.value,
    total: tomorrowActivities.value.length,
    empty: {
      text: t('core::app.all_caught_up'),
      icon: 'Check',
      iconClass: 'text-success-500',
    },
  },
  {
    id: 'this_week',
    title: t('activities::activity.filters.this_week'),
    data: thisWeekActivities.value,
    total: thisWeekActivities.value.length,
    empty: {
      text: t('core::app.all_caught_up'),
      icon: 'Check',
      iconClass: 'text-success-500',
    },
  },
  {
    id: 'next_week',
    title: t('activities::activity.filters.next_week'),
    data: nextWeekActivities.value,
    total: nextWeekActivities.value.length,
    empty: {
      text: t('core::app.all_caught_up'),
      icon: 'Check',
      iconClass: 'text-success-500',
    },
  },
  {
    id: 'done',
    title: t('activities::activity.filters.done'),
    data: completedActivities.value,
    total: completedActivities.value.length,
    empty: {
      text: t('activities::activity.filters.done_empty_state'),
      icon: 'CheckCircle',
      iconClass: 'text-neutral-500 dark:text-neutral-300',
    },
  },
])

if (route.query.resourceId && route.query.section === timelineRelation) {
  // Wait till the data is loaded for the first time and the
  // elements are added to the document so we can have a proper scroll
  watchOnce(dataLoadedFirstTime, () => {
    focusToAssociateableElement(route.query.resourceId, 'activity').then(() => {
      if (route.query.comment_id) {
        commentsAreVisible.value = true
      }
    })
  })
}
</script>
