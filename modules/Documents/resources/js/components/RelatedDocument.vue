<template>
  <DocumentsEdit
    v-if="documentBeingEdited"
    :id="documentId"
    :section="editSection"
    :via-resource="viaResource"
    :exit-using="() => (documentBeingEdited = false)"
    @reactivated="refreshRecordView"
    @sent="refreshRecordView"
    @lost="refreshRecordView"
    @accept="refreshRecordView"
    @changed="handleDocumentChanged"
    @deleted="handleDocumentDeleted"
  />

  <ICard v-bind="$attrs" :class="'document-' + documentId">
    <ICardBody>
      <div class="my-1 flex items-center">
        <ILink
          class="font-medium"
          :href="path"
          :text="displayName"
          basic
          @click="editDocument"
        />

        <IBadge
          class="ml-3"
          :text="type.name"
          :color="type.swatch_color"
          :icon="type.icon"
        />
      </div>

      <AssociationsPopover
        :associations-count="associationsCount"
        :initial-associateables="relatedResource"
        :resource-id="documentId"
        :resource-name="resourceName"
        :primary-record="relatedResource"
        :primary-resource-name="viaResource"
        @synced="synchronizeResource({ documents: $event })"
      />

      <div class="mt-5">
        <div
          class="rounded-md bg-neutral-50 px-6 py-5 dark:bg-neutral-800 sm:flex sm:items-start sm:justify-between"
        >
          <div class="sm:flex sm:items-start">
            <IBadge
              class="w-auto self-start sm:shrink-0"
              :text="statuses[status].display_name"
              :color="statuses[status].color"
            />

            <div class="mt-3 sm:ml-4 sm:mt-0">
              <ITextBlockDark class="font-medium" :text="formatMoney(amount)" />

              <IText
                class="mt-1"
                :text="
                  $t('documents::document.sent_at', {
                    date: lastDateSent
                      ? localizedDateTime(lastDateSent)
                      : 'N/A',
                  })
                "
              />

              <ITextBlock
                v-if="acceptedAt"
                class="mt-1 space-x-1 sm:flex sm:items-center"
              >
                <span>{{ $t('documents::document.accepted_at') }}:</span>

                <span v-text="localizedDateTime(acceptedAt)" />
              </ITextBlock>
            </div>
          </div>

          <div class="mt-4 sm:ml-6 sm:mt-0 sm:shrink-0">
            <div class="flex items-center space-x-2">
              <IButton
                v-show="authorizations.view"
                variant="secondary"
                :text="$t('core::app.edit')"
                @click="editDocument"
              />

              <IButton
                v-if="status === 'draft'"
                v-show="authorizations.view"
                variant="secondary"
                icon="Mail"
                :text="$t('documents::document.send.send')"
                @click="editDocument('send')"
              />

              <IDropdownMinimal>
                <IDropdownItem
                  :href="publicUrl"
                  :text="$t('documents::document.view')"
                />

                <IDropdownSeparator />

                <IDropdownItem
                  target="_blank"
                  rel="noopener noreferrer"
                  :href="publicUrl + '/pdf'"
                  :text="$t('documents::document.view_pdf')"
                />

                <IDropdownItem
                  :href="publicUrl + '/pdf?output=download'"
                  :text="$t('documents::document.download_pdf')"
                />

                <IDropdownSeparator />

                <IDropdownItem
                  v-if="authorizations.update"
                  :text="$t('core::app.edit')"
                  @click="editDocument"
                />

                <IDropdownItem
                  v-if="authorizations.delete"
                  :text="$t('core::app.delete')"
                  @click="$confirm(() => destroy(documentId))"
                />
              </IDropdownMinimal>
            </div>
          </div>
        </div>
      </div>
    </ICardBody>
  </ICard>
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import AssociationsPopover from '@/Core/components/AssociationsPopover.vue'
import { useAccounting } from '@/Core/composables/useAccounting'
import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { emitGlobal } from '@/Core/composables/useGlobalEventListener'
import { useResourceable } from '@/Core/composables/useResourceable'

import { useDocumentTypes } from '../composables/useDocumentTypes'
import DocumentsEdit from '../views/DocumentsEdit.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  documentId: { type: Number, required: true },
  typeId: { type: Number, required: true },
  status: { type: String, required: true },
  displayName: { type: String, required: true },
  path: { type: String, required: true },
  publicUrl: { type: String, required: true },
  acceptedAt: String,
  lastDateSent: String,
  amount: { required: true },
  authorizations: { type: Object, required: true },
  associationsCount: { type: Number, required: true },
  viaResource: { type: String, required: true },
  viaResourceId: { required: true, type: [String, Number] },
  relatedResource: { required: true, type: Object },
})

const resourceName = Innoclapps.resourceName('documents')

const synchronizeResource = inject('synchronizeResource')
const decrementResourceCount = inject('decrementResourceCount')

const { t } = useI18n()
const { deleteResource } = useResourceable(resourceName)
const { formatMoney } = useAccounting()
const { localizedDateTime } = useDates()
const { scriptConfig } = useApp()
const { findTypeById } = useDocumentTypes()

const documentBeingEdited = ref(false)
const editSection = ref(null)

const type = computed(() => findTypeById(props.typeId))

const statuses = scriptConfig('documents.statuses')

function handleDocumentChanged(changedDocument) {
  synchronizeResource({
    documents: changedDocument,
  })
}

function handleDocumentDeleted(deletedDocument) {
  synchronizeResource({ documents: { id: deletedDocument.id, _delete: true } })
  refreshRecordView()
}

function editDocument(section = null) {
  editSection.value = typeof section == 'string' ? section : 'details'
  documentBeingEdited.value = true
}

function refreshRecordView() {
  emitGlobal('refresh-details-view')
}

async function destroy(id) {
  await deleteResource(id)

  if (props.status === 'draft') {
    decrementResourceCount('draft_documents_for_user_count')
  }

  synchronizeResource({ documents: { id, _delete: true } })
  decrementResourceCount('documents_count')
  decrementResourceCount('documents_for_user_count')

  Innoclapps.success(t('documents::document.deleted'))
}
</script>
