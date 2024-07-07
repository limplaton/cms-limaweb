<template>
  <ITabPanel :lazy="!dataLoadedFirstTime" @activated.once="loadData">
    <ICard v-show="!showCreateForm" class="rounded-t-none pt-5 sm:pt-0">
      <ICardBody>
        <div class="sm:flex sm:items-start sm:justify-between">
          <div>
            <ITextDark
              class="font-semibold"
              :text="$t('calls::call.manage_calls')"
            />

            <IText class="max-w-xl" :text="$t('calls::call.info')" />
          </div>

          <div
            class="mt-5 space-x-0 space-y-1 sm:ml-6 sm:mt-0 sm:flex sm:shrink-0 sm:items-center sm:space-x-2 sm:space-y-0"
          >
            <IButton
              variant="primary"
              icon="PlusSolid"
              class="w-full sm:w-auto"
              :text="$t('calls::call.add')"
              @click="showCreateForm = true"
            />

            <CallMakeButton
              v-if="$gate.userCan('use voip')"
              class="block w-full sm:w-auto"
              :resource-name="resourceName"
              :resource="resource"
              @requested="newCall"
            />
          </div>
        </div>

        <SearchInput
          v-show="hasCalls || search"
          v-model="search"
          class="mt-4"
          @update:model-value="performSearch"
        />
      </ICardBody>
    </ICard>

    <CallsCreate
      v-if="showCreateForm"
      class="rounded-t-none"
      :via-resource="resourceName"
      :via-resource-id="resourceId"
      :related-resource-display-name="resource.display_name"
      @cancel="showCreateForm = false"
    />

    <div class="mt-3 space-y-4 sm:mt-7">
      <div
        v-for="call in calls"
        :key="call.id"
        v-memo="[call.updated_at, call.comments_count, call.call_outcome_id]"
      >
        <CallsView
          :call-id="call.id"
          :comments-count="call.comments_count"
          :call-date="call.date"
          :body="call.body"
          :user-id="call.user_id"
          :outcome-id="call.call_outcome_id"
          :authorizations="call.authorizations"
          :comments="call.comments || []"
          :via-resource="resourceName"
          :via-resource-id="resourceId"
        />
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
import { useRoute } from 'vue-router'
import { watchOnce } from '@vueuse/core'
import orderBy from 'lodash/orderBy'

import InfinityLoader from '@/Core/components/InfinityLoader.vue'
import { useRecordTab } from '@/Core/composables/useRecordTab'

import { useComments } from '@/Comments/composables/useComments'

import { useVoip } from '../composables/useVoip'

import CallMakeButton from './CallMakeButton.vue'
import CallsCreate from './CallsCreate.vue'
import CallsView from './CallsView.vue'

const props = defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
  scrollElement: { type: String },
})

const synchronizeResource = inject('synchronizeResource')

const route = useRoute()

const timelineRelation = 'calls'
const infinityRef = ref(null)
const showCreateForm = ref(false)

const { voip } = useVoip()

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
  synchronizeResource,
  infinityRef,
  timelineRelation,
})

const calls = computed(() =>
  orderBy(searchResults.value || props.resource.calls, 'date', 'desc')
)

const hasCalls = computed(() => calls.value.length > 0)

async function newCall(phoneNumber) {
  showCreateForm.value = true
  await voip.makeCall(phoneNumber)
}

if (route.query.resourceId && route.query.section === timelineRelation) {
  // Wait till the data is loaded for the first time and the
  // elements are added to the document so we can have a proper scroll
  watchOnce(dataLoadedFirstTime, () => {
    focusToAssociateableElement(route.query.resourceId, 'call').then(() => {
      if (route.query.comment_id) {
        commentsAreVisible.value = true
      }
    })
  })
}
</script>
