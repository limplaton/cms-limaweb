<template>
  <ITabPanel :lazy="!dataLoadedFirstTime" @activated.once="loadData">
    <ICard v-show="!showCreateForm" class="rounded-t-none pt-5 sm:pt-0">
      <ICardBody>
        <div class="sm:flex sm:items-start sm:justify-between">
          <div>
            <ITextDark
              class="font-semibold"
              :text="$t('notes::note.manage_notes')"
            />

            <IText class="max-w-xl" :text="$t('notes::note.info')" />
          </div>

          <div class="mt-5 sm:ml-6 sm:mt-0">
            <IButton
              variant="primary"
              icon="PlusSolid"
              :text="$t('notes::note.add')"
              @click="showCreateForm = true"
            />
          </div>
        </div>

        <SearchInput
          v-show="hasNotes || search"
          v-model="search"
          class="mt-4"
          @update:model-value="performSearch"
        />
      </ICardBody>
    </ICard>

    <NotesCreate
      v-if="showCreateForm"
      class="rounded-t-none"
      :via-resource="resourceName"
      :via-resource-id="resourceId"
      :related-resource-display-name="resource.display_name"
      @cancel="showCreateForm = false"
    />

    <div class="mt-3 space-y-4 sm:mt-7">
      <div
        v-for="note in notes"
        :key="note.id"
        v-memo="[note.updated_at, note.comments_count]"
      >
        <NotesView
          :note-id="note.id"
          :comments-count="note.comments_count"
          :created-at="note.created_at"
          :body="note.body"
          :user-id="note.user_id"
          :authorizations="note.authorizations"
          :comments="note.comments || []"
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

import NotesCreate from './NotesCreate.vue'
import NotesView from './NotesView.vue'

const props = defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
  scrollElement: { type: String },
})

const synchronizeResource = inject('synchronizeResource')

const route = useRoute()

const timelineRelation = 'notes'
const infinityRef = ref(null)
const showCreateForm = ref(false)

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
  infinityRef,
  synchronizeResource,
  timelineRelation,
})

const notes = computed(() =>
  orderBy(searchResults.value || props.resource.notes, 'created_at', 'desc')
)

const hasNotes = computed(() => notes.value.length > 0)

if (route.query.resourceId && route.query.section === timelineRelation) {
  // Wait till the data is loaded for the first time and the
  // elements are added to the document so we can have a proper scroll
  watchOnce(dataLoadedFirstTime, () => {
    focusToAssociateableElement(route.query.resourceId, 'note').then(() => {
      if (route.query.comment_id) {
        commentsAreVisible.value = true
      }
    })
  })
}
</script>
