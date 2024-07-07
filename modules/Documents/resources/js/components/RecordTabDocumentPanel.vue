<template>
  <ITabPanel :lazy="!dataLoadedFirstTime" @activated.once="loadData">
    <ICard class="rounded-t-none pt-5 sm:pt-0">
      <ICardBody>
        <div class="sm:flex sm:items-start sm:justify-between">
          <div>
            <ITextDark
              class="font-semibold"
              :text="$t('documents::document.manage_documents')"
            />

            <IText class="max-w-xl" :text="$t('documents::document.info')" />
          </div>

          <div class="mt-5 sm:ml-6 sm:mt-0">
            <IButton
              variant="primary"
              icon="PlusSolid"
              :text="$t('documents::document.create')"
              @click="documentBeingCreated = true"
            />
          </div>
        </div>

        <SearchInput
          v-show="hasDocuments || search"
          v-model="search"
          class="mt-4"
          @update:model-value="performSearch"
        />
      </ICardBody>
    </ICard>

    <DocumentsCreate
      v-if="documentBeingCreated"
      :via-resource="resourceName"
      :via-resource-id="resourceId"
      :related-resource="resource"
      :exit-using="() => (documentBeingCreated = null)"
      :edit-redirect-handler="handleRedirectOnEditWhenCreating"
    />

    <DocumentsEdit
      v-if="documentBeingEdited"
      :id="documentBeingEdited"
      :via-resource="resourceName"
      :via-resource-id="resourceId"
      :related-resource="resource"
      :exit-using="() => (documentBeingEdited = false)"
      @reactivated="refreshRecordView"
      @sent="refreshRecordView"
      @lost="refreshRecordView"
      @accept="refreshRecordView"
      @changed="handleDocumentChanged"
      @deleted="handleDocumentDeleted"
    />

    <div class="mt-3 space-y-4 sm:mt-7">
      <RelatedDocument
        v-for="document in documents"
        :key="document.id"
        :document-id="document.id"
        :type-id="document.document_type_id"
        :status="document.status"
        :display-name="document.display_name"
        :path="document.path"
        :public-url="document.public_url"
        :accepted-at="document.accepted_at"
        :last-date-sent="document.last_date_sent"
        :amount="document.amount"
        :authorizations="document.authorizations"
        :associations-count="document.associations_count"
        :via-resource="resourceName"
        :via-resource-id="resourceId"
        :related-resource="resource"
      />
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
import orderBy from 'lodash/orderBy'

import InfinityLoader from '@/Core/components/InfinityLoader.vue'
import { emitGlobal } from '@/Core/composables/useGlobalEventListener'
import { useRecordTab } from '@/Core/composables/useRecordTab'

import DocumentsCreate from '../views/DocumentsCreate.vue'
import DocumentsEdit from '../views/DocumentsEdit.vue'

import RelatedDocument from './RelatedDocument.vue'

const props = defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
  scrollElement: { type: String },
})

const synchronizeResource = inject('synchronizeResource')

const timelineRelation = 'documents'

const infinityRef = ref(null)
const documentBeingCreated = ref(false)
const documentBeingEdited = ref(null)

const {
  dataLoadedFirstTime,
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

const iterable = computed(() =>
  isSearching.value ? searchResults.value || [] : props.resource.documents || []
)

const lost = computed(() =>
  orderBy(
    iterable.value.filter(document => document.status === 'lost'),
    document => new Date(document.created_at),
    'asc'
  )
)

const accepted = computed(() =>
  orderBy(
    iterable.value.filter(document => document.status === 'accepted'),
    document => new Date(document.accepted_at),
    'asc'
  )
)

const draft = computed(() =>
  orderBy(
    iterable.value.filter(document => document.status === 'draft'),
    document => new Date(document.created_at),
    'asc'
  )
)

const sent = computed(() =>
  orderBy(
    iterable.value.filter(document => document.status === 'sent'),
    document => new Date(document.last_date_sent),
    'desc'
  )
)

const documents = computed(() => [
  ...draft.value,
  ...sent.value,
  ...lost.value,
  ...accepted.value,
])

const hasDocuments = computed(() => documents.value.length > 0)

function handleDocumentChanged(updatedDocument) {
  synchronizeResource({ documents: updatedDocument })
}

function handleDocumentDeleted(deletedDocument) {
  synchronizeResource({ documents: { id: deletedDocument.id, _delete: true } })
  refreshRecordView()
}

function handleRedirectOnEditWhenCreating(document) {
  documentBeingEdited.value = document.id
  documentBeingCreated.value = false
}

function refreshRecordView() {
  emitGlobal('refresh-details-view')
}
</script>
