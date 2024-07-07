<template>
  <Teleport to="body" :disabled="documentNotFound">
    <FormLayout
      v-model:active-section="section"
      :total-products="
        componentReady ? form.billable.products.length : undefined
      "
      :total-signers="
        componentReady && form.requires_signature
          ? form.signers.length
          : undefined
      "
      :remaining-signers="
        componentReady
          ? form.signers.filter(signer => !Boolean(signer.signed_at)).length
          : undefined
      "
      @exit-requested="exit"
    >
      <template #actions>
        <div class="flex space-x-0.5">
          <IButtonCopy
            v-if="componentReady && document.authorizations.view"
            v-i-tooltip.bottom="$t('documents::document.copy_url')"
            class="shrink-0"
            :text="document.public_url"
            :success-message="$t('documents::document.url_copied')"
          />

          <IDropdown
            v-if="componentReady && document.authorizations.view"
            placement="bottom-end"
          >
            <IDropdownButton icon="DocumentDownload" basic no-caret />

            <IDropdownMenu>
              <IDropdownItem
                target="_blank"
                rel="noopener noreferrer"
                :href="document.public_url + '/pdf'"
                :text="$t('documents::document.view_pdf')"
              />

              <IDropdownItem
                :href="document.public_url + '/pdf?output=download'"
                :text="$t('documents::document.download_pdf')"
              />
            </IDropdownMenu>
          </IDropdown>

          <span
            v-i-tooltip.bottom="
              document.authorizations && !document.authorizations.update
                ? $t('core::app.action_not_authorized')
                : ''
            "
            class="inline-block"
          >
            <IExtendedDropdown
              v-if="componentReady"
              placement="bottom-end"
              :disabled="form.busy || !document.authorizations.update"
              :loading="form.busy"
              :text="$t('core::app.save')"
              @click="save"
            >
              <IDropdownMenu>
                <IDropdownItem
                  :text="$t('core::app.save_and_exit')"
                  @click="saveAndExit"
                />

                <IDropdownItem
                  v-if="
                    document.status !== 'accepted' && document.status !== 'lost'
                  "
                  :text="$t('documents::document.actions.mark_as_lost')"
                  @click="markAsLost"
                />

                <IDropdownItem
                  v-if="document.status !== 'accepted'"
                  :text="$t('documents::document.actions.mark_as_accepted')"
                  @click="markAsAccepted"
                />

                <IDropdownItem
                  v-if="document.status === 'lost'"
                  :text="$t('documents::document.actions.reactivate')"
                  confirmable
                  @confirmed="reactivate"
                />

                <IDropdownItem
                  v-if="
                    document.status === 'accepted' &&
                    document.marked_accepted_by
                  "
                  :text="$t('documents::document.actions.undo_acceptance')"
                  confirmable
                  @confirmed="reactivate"
                />

                <IDropdownItem :text="$t('core::app.clone')" @click="clone" />

                <IDropdownSeparator />

                <IDropdownItem
                  v-if="document.authorizations.delete"
                  :text="$t('core::app.delete')"
                  :confirm-text="$t('core::app.confirm')"
                  confirmable
                  @confirmed="destroy"
                />
              </IDropdownMenu>
            </IExtendedDropdown>
          </span>
        </div>
      </template>

      <IOverlay :show="!componentReady">
        <div v-if="componentReady" class="container mx-auto">
          <div
            v-if="componentReady && !document.authorizations.view"
            class="mx-auto mb-6 max-w-6xl"
          >
            <IAlert variant="warning">
              <IAlertBody>
                {{ $t('core::role.view_non_authorized_after_record_create') }}
              </IAlertBody>
            </IAlert>
          </div>

          <SectionDetails
            :form="form"
            :document="document"
            :visible="section == 'details'"
          >
            <template #actions>
              <div class="inline-block">
                <AssociationsPopover
                  v-model:associations-count="document.associations_count"
                  width-class="w-80"
                  :resource-name="resourceName"
                  :resource-id="document.id"
                  :initial-associateables="
                    viaResource ? relatedResource : undefined
                  "
                  :primary-resource-name="viaResource"
                  @synced="$emit('changed', $event)"
                >
                  <template
                    #after-record="{
                      record: associatedResource,
                      isSelected,
                      isSearching,
                      title,
                    }"
                  >
                    <span
                      v-if="
                        associatedResource.is_primary_associated &&
                        isSelected &&
                        !isSearching
                      "
                      v-i-tooltip.top="
                        $t(
                          'documents::document.will_use_placeholders_from_record',
                          { resourceName: title }
                        )
                      "
                      class="ml-1.5 self-start"
                    >
                      <Icon
                        icon="CodeBracket"
                        class="size-5 text-neutral-500 dark:text-neutral-400"
                      />
                    </span>
                  </template>
                </AssociationsPopover>
              </div>
            </template>
          </SectionDetails>

          <SectionProducts
            :form="form"
            :visible="section == 'products'"
            :document="document"
          />

          <SectionContent
            ref="sectionContentRef"
            :form="form"
            :document="document"
            :visible="section == 'content'"
            :is-ready="componentReady"
          />

          <SectionSignature
            :form="form"
            :document="document"
            :visible="section == 'signature'"
          />

          <SectionSend
            :sending="sending"
            :form="form"
            :document="document"
            :visible="section == 'send'"
            @send-requested="send"
            @save-requested="save"
          />
        </div>
      </IOverlay>
    </FormLayout>
  </Teleport>
</template>

<script setup>
import { computed, inject, nextTick, provide, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

import AssociationsPopover from '@/Core/components/AssociationsPopover.vue'
import { useForm } from '@/Core/composables/useForm'
import { usePageTitle } from '@/Core/composables/usePageTitle'
import { useResourceable } from '@/Core/composables/useResourceable'

import { useBrands } from '@/Brands/composables/useBrands'

import SectionContent from '../components/DocumentFormContent.vue'
import SectionDetails from '../components/DocumentFormDetails.vue'
import FormLayout from '../components/DocumentFormLayout.vue'
import SectionProducts from '../components/DocumentFormProducts.vue'
import SectionSend from '../components/DocumentFormSend.vue'
import SectionSignature from '../components/DocumentFormSignature.vue'

const props = defineProps({
  id: Number,
  viaResource: String,
  viaResourceId: [String, Number], // required when "viaResource" is provided
  relatedResource: Object, // required when "viaResource" is provided
  section: String,
  exitUsing: Function,
})

const emit = defineEmits([
  'changed',
  'updated',
  'deleted',
  'cloned',
  'lost',
  'reactivated',
  'accept',
  'sent',
  'associations-updated',
])

const resourceName = Innoclapps.resourceName('documents')

const synchronizeResource = inject('synchronizeResource', null)
const incrementResourceCount = inject('incrementResourceCount', null)

const { t } = useI18n()

const { retrieveResource, deleteResource, cloneResource } =
  useResourceable(resourceName)

const router = useRouter()
const route = useRoute()

const pageTitle = usePageTitle()

const sectionContentRef = ref(null)

const sending = ref(false)
const documentFetched = ref(false)
const { orderedBrands: brands, brandsAreBeingFetched } = useBrands()
const section = ref(props.section || route.query.section || 'details')
const document = ref({})
const documentNotFound = ref(false)

const { form } = useForm()

const componentReady = computed(
  () => documentFetched.value && !brandsAreBeingFetched.value
)

provide('brands', brands)
provide('document', document)

function retrieveInitialDocument(id) {
  return router.document
    ? new Promise(resolve => resolve(router.document))
    : retrieveResource(id)
}

function prepareComponent(id) {
  retrieveInitialDocument(id)
    .then(document => {
      form.set({ send: false })

      prepareDocument(document)

      documentFetched.value = true
      router.document && delete router.document
    })
    .catch(error => {
      if (error.response.status === 404) {
        documentNotFound.value = true
      }
    })
}

/**
 * Prepare the document for edit
 */
function prepareDocument(documentObject) {
  document.value = documentObject

  if (!props.viaResource) {
    pageTitle.value = documentObject.title
  }

  form.set({
    title: documentObject.title,
    view_type: documentObject.view_type,
    locale: documentObject.locale,
    user_id: documentObject.user_id,
    brand_id: documentObject.brand_id,
    document_type_id: documentObject.document_type_id,
    content: documentObject.content,
    requires_signature: documentObject.requires_signature,

    signers: documentObject.signers,

    recipients: documentObject.recipients,

    pdf: documentObject.pdf,

    billable: {
      tax_type: documentObject.billable.tax_type,
      products: documentObject.billable.products,
      removed_products: [],
    },

    send_at: documentObject.send_at,

    send_mail_account_id: documentObject.send_mail_account_id,
    send_mail_body: documentObject.send_mail_body,
    send_mail_subject: documentObject.send_mail_subject,
  })
}

/**
 * Mark the document as lost
 */
async function markAsLost() {
  await Innoclapps.confirm({
    message: t('documents::document.actions.mark_as_lost_message'),
    title: false,
    icon: 'QuestionMarkCircle',
    iconWrapperColorClass: 'bg-info-100',
    iconColorClass: 'text-info-400',
    html: true,
    confirmText: t('core::app.confirm'),
    confirmVariant: 'info',
  })

  Innoclapps.request()
    .post(`/documents/${document.value.id}/lost`)
    .then(({ data }) => {
      emit('lost', data)
      emit('changed', data)

      Innoclapps.success(t('documents::document.marked_as_lost'))

      exit()
    })
}

/**
 * Mark the document as accepted
 */
function markAsAccepted() {
  Innoclapps.request()
    .post(`/documents/${document.value.id}/accept`)
    .then(({ data }) => {
      emit('accept', data)
      emit('changed', data)

      Innoclapps.success(t('documents::document.marked_as_accepted'))

      prepareDocument(data)
    })
}

/**
 * Reactivate the document
 */
async function reactivate() {
  Innoclapps.request()
    .post(`/documents/${document.value.id}/draft`)
    .then(({ data }) => {
      Innoclapps.success(t('documents::document.reactivated'))
      emit('reactivated', data)
      emit('changed', data)
      prepareDocument(data)
    })
}

/**
 * Send the document
 */
function send() {
  sending.value = true

  save()
    .then(() => {
      form.send = true

      save()
        .then(() => Innoclapps.success(t('documents::document.sent')))
        .finally(doc => {
          form.send = false
          sending.value = false
          emit('sent', doc)
        })
    })
    .catch(error => {
      console.error(error)
      sending.value = false
    })
}

/**
 * Save the document
 */
async function save() {
  form.busy = true

  if (sectionContentRef.value.builderRef) {
    await sectionContentRef.value.builderRef.saveBase64Images()
  }

  // Wait till update:modelValue event is properly propagated
  await nextTick()

  let updatedDocument = await form
    .put(`/documents/${document.value.id}`)
    .catch(e => {
      if (e.isValidationError()) {
        Innoclapps.error(
          t('core::app.form_validation_failed_with_sections'),
          3000
        )
      }

      return Promise.reject(e)
    })

  prepareDocument(updatedDocument)

  emit('updated', updatedDocument)
  emit('changed', updatedDocument)

  return updatedDocument
}

/**
 * Save the document and exit
 */
function saveAndExit() {
  save().then(exit)
}

/**
 * Clone the document being edited
 */
async function clone() {
  const clonedDocument = await cloneResource(document.value.id)

  emit('cloned', clonedDocument)

  if (props.viaResource) {
    router.push({ name: route.name, params: { documentId: clonedDocument.id } })

    if (props.viaResource && clonedDocument.authorizations.view) {
      synchronizeResource({ documents: [clonedDocument] })

      incrementResourceCount([
        'documents_count',
        'documents_for_user_count',
        'draft_documents_for_user_count',
      ])
    }
  } else {
    router.push({ name: route.name, params: { id: clonedDocument.id } })
  }

  prepareDocument(clonedDocument)
}

/**
 * Remove document from storage
 */
async function destroy() {
  await deleteResource(document.value.id)

  emit('deleted', document.value)

  Innoclapps.success(t('documents::document.deleted'))

  exit()
}

/**
 * Exit the document edit
 */
function exit() {
  if (props.exitUsing) {
    props.exitUsing()

    return
  }

  router.back()
}

prepareComponent(props.id || route.params.id)
</script>
