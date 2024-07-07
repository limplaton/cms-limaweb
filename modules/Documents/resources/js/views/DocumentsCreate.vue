<template>
  <Teleport to="body">
    <FormLayout
      v-model:active-section="selectedSection"
      :total-products="form.billable.products.length"
      :total-signers="form.requires_signature ? form.signers.length : undefined"
      @exit-requested="exit"
    >
      <template #actions>
        <IExtendedDropdown
          placement="bottom-end"
          :disabled="form.busy"
          :loading="form.busy"
          :text="$t('core::app.save')"
          @click="save"
        >
          <IDropdownMenu>
            <IDropdownItem
              :text="$t('core::app.save_and_exit')"
              @click="saveAndExit"
            />
          </IDropdownMenu>
        </IExtendedDropdown>
      </template>

      <div v-if="componentReady">
        <SectionDetails :form="form" :visible="selectedSection == 'details'">
          <template #actions>
            <div class="inline-block">
              <AssociationsPopover
                v-model="form.associations"
                width-class="w-80"
                :primary-resource-name="viaResource"
                :primary-record="viaResource ? relatedResource : undefined"
                :primary-record-disabled="true"
                :initial-associateables="
                  viaResource ? relatedResource : undefined
                "
                :associateables="associateables"
              >
                <template
                  #after-record="{
                    record,
                    selectedRecords,
                    isSearching,
                    title,
                  }"
                >
                  <span
                    v-if="selectedRecords[0] === record.id && !isSearching"
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

          <template #top>
            <IFormGroup
              v-if="showDealSelector"
              class="mb-6 mt-3 rounded-lg border border-neutral-200 bg-info-50/40 p-5 dark:border-info-400/10 dark:bg-info-900/10"
              :description="$t('documents::document.deal_description')"
            >
              <ICustomSelect
                ref="createDealSelectRef"
                label="name"
                input-id="deal_id"
                :placeholder="$t('deals::deal.choose_or_create')"
                :options="deals"
                :filterable="false"
                :model-value="selectedDeal"
                debounce
                @update:model-value="handleSelectedDealChanged"
                @search="searchDeals"
                @option-selected="handleDealSelected"
              >
                <template #no-options="{ searching, text }">
                  <span v-show="searching" v-text="text" />

                  <span v-show="!searching" v-t="'core::app.type_to_search'" />
                </template>

                <template #footer>
                  <ILink
                    class="block border-t border-neutral-200 px-4 py-2 hover:bg-neutral-50 dark:border-neutral-500/30 dark:hover:bg-neutral-700"
                    @click="
                      ;(dealIsBeingCreated = true),
                        $refs.createDealSelectRef.hide()
                    "
                  >
                    &plus; {{ $t('deals::deal.create') }}
                  </ILink>
                </template>
              </ICustomSelect>
            </IFormGroup>
          </template>
        </SectionDetails>

        <SectionProducts
          :form="form"
          :visible="selectedSection == 'products'"
        />

        <SectionContent
          ref="sectionContentRef"
          :form="form"
          :visible="selectedSection == 'content'"
        />

        <SectionSignature
          :form="form"
          :visible="selectedSection == 'signature'"
        />

        <SectionSend
          :sending="sending"
          :form="form"
          :visible="selectedSection == 'send'"
          @send-requested="send"
          @save-requested="save"
        />
      </div>
    </FormLayout>

    <CreateDealModal
      v-model:visible="dealIsBeingCreated"
      @created="dealCreatedHandler"
    />
  </Teleport>
</template>

<script setup>
import { computed, inject, nextTick, provide, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'
import omit from 'lodash/omit'

import AssociationsPopover from '@/Core/components/AssociationsPopover.vue'
import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'

import { useBrands } from '@/Brands/composables/useBrands'

import SectionContent from '../components/DocumentFormContent.vue'
import SectionDetails from '../components/DocumentFormDetails.vue'
import FormLayout from '../components/DocumentFormLayout.vue'
import SectionProducts from '../components/DocumentFormProducts.vue'
import SectionSend from '../components/DocumentFormSend.vue'
import SectionSignature from '../components/DocumentFormSignature.vue'
import { useDocumentTypes } from '../composables/useDocumentTypes'

const props = defineProps({
  viaResource: String,
  viaResourceId: [String, Number], // required when "viaResource" is provided
  relatedResource: Object, // required when "viaResource" is provided
  editRedirectHandler: Function,
  exitUsing: Function,
})

const emit = defineEmits(['created', 'sent'])

const synchronizeResource = inject('synchronizeResource', null)
const incrementResourceCount = inject('incrementResourceCount', null)

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { setting, currentUser } = useApp()

const { createResource, updateResource } = useResourceable(
  Innoclapps.resourceName('documents')
)

const sectionContentRef = ref(null)

const sending = ref(false)
const { orderedBrands: brands, brandsAreBeingFetched } = useBrands()
const selectedSection = ref(route.query.section || 'details')
const associateables = ref({})
const dealIsBeingCreated = ref(false)
const selectedDeal = ref(null)
const showDealSelector = ref(true)
const deals = ref([])

provide('brands', brands)

const componentReady = computed(() => !brandsAreBeingFetched.value)

const { documentTypes } = useDocumentTypes()

const { form } = useForm({
  title: null,
  brand_id: null,
  user_id: null,
  content: null,
  view_type: 'nav-top',
  locale: currentUser.value.locale,
  document_type_id: null,
  requires_signature: true,
  signers: [],
  recipients: [],

  pdf: {
    padding: '15px',
  },

  billable: {
    tax_type: setting('tax_type'),
    products: [],
  },

  send: false,
  send_mail_account_id: null,
  send_mail_body: null,
  send_mail_subject: null,

  associations: {},
})

/**
 * Exit handler
 */
function exit() {
  if (props.exitUsing) {
    props.exitUsing()

    return
  }
  router.back()
}

/**
 * Redirect the doc to edit route
 */
function performEditRedirect(document, query = {}) {
  router.document = document

  if (props.editRedirectHandler) {
    props.editRedirectHandler(document, query)

    return
  }

  // Use replace so the exit link works well and returns to the previous location
  router.replace({
    name: 'edit-document',
    params: { id: document.id },
    query: query,
  })
}

/**
 * Send the document
 */
function send() {
  sending.value = true

  makeSaveRequest()
    .then(document => {
      form.send = true

      if (!document.authorizations.view) {
        performEditRedirect(document, { section: 'send' })

        Innoclapps.error(
          'Document not sent, your account not authorized to perform this action.'
        )

        return
      }

      // Update the form billable and signers to reflect the newly created id's
      form.billable = document.billable
      form.signers = document.signers

      updateResource(form, document.id).then(document => {
        Innoclapps.success(t('documents::document.sent'))
        emit('sent', document)
        performEditRedirect(document, { section: 'send' })
      })
    })
    .catch(() => (sending.value = false))
}

/**
 * Save the document and exit
 */
function saveAndExit() {
  makeSaveRequest().then(exit)
}

/**
 * Save the document
 */
async function save() {
  let document = await makeSaveRequest()

  performEditRedirect(document, { section: selectedSection.value })

  return document
}

/**
 * Make save request
 */
async function makeSaveRequest() {
  form.busy = true

  if (sectionContentRef.value.builderRef) {
    await sectionContentRef.value.builderRef.saveBase64Images()
  }

  // Wait till update:modelValue event is properly propagated
  await nextTick()

  let document = await createResource(form).catch(e => {
    if (e.isValidationError()) {
      Innoclapps.error(
        t('core::app.form_validation_failed_with_sections'),
        3000
      )
    }

    return Promise.reject(e)
  })

  if (props.viaResource && document.authorizations.view) {
    synchronizeResource({ documents: [document] })

    incrementResourceCount([
      'documents_count',
      'documents_for_user_count',
      'draft_documents_for_user_count',
    ])
  }

  emit('created', document)

  return document
}

/**
 * Handle the deal created event
 */
function dealCreatedHandler({ deal }) {
  selectedDeal.value = deal
  deals.value.push(deal)
  handleDealSelected(deal)
  dealIsBeingCreated.value = false
}

/**
 * Remove associations
 */
function removeAssociation(id, resourceName) {
  let associateablesIndex = findIndex(associateables.value[resourceName], [
    'id',
    id,
  ])

  let modelIndex = form.associations[resourceName].findIndex(
    associatedId => associatedId === id
  )

  if (associateablesIndex !== -1) {
    associateables.value[resourceName].splice(associateablesIndex, 1)
  }

  if (modelIndex !== -1) {
    form.associations[resourceName].splice(modelIndex, 1)
  }
}

/** Add custom association to the form */
function addAssociation(record, resourceName) {
  if (!Object.hasOwn(form.associations, resourceName)) {
    form.associations[resourceName] = []
  }

  if (!Object.hasOwn(associateables.value, resourceName)) {
    associateables.value[resourceName] = []
  }

  if (!find(form.associations[resourceName], ['id', record.id])) {
    form.associations[resourceName].push(record.id)
    associateables.value[resourceName].push(record)
  }
}

/**
 * Handle the deal selected event
 */
function handleDealSelected(deal) {
  if (deal) {
    addAssociation({ id: deal.id, display_name: deal.display_name }, 'deals')

    setDataFromDeal(deal)
  }
}

function handleSelectedDealChanged(value) {
  if (!value && selectedDeal.value) {
    removeAssociation(selectedDeal.value.id, 'deals')
  }
  selectedDeal.value = value
}

/** Set the data from the given deal */
function setDataFromDeal(deal) {
  if (!form.title && form.document_type_id) {
    form.title = `${deal.display_name} ${
      find(documentTypes.value, ['id', parseInt(form.document_type_id)]).name
    }`
  }

  if (deal.billable) {
    form.billable.products = deal.billable.products.map(product =>
      omit(product, ['id'])
    )
    form.billable.tax_type = deal.billable.tax_type
  }

  if (form.requires_signature) {
    // eslint-disable-next-line padding-line-between-statements
    ;(deal.contacts || [])
      .filter(contact => Boolean(contact.email))
      .forEach(contact =>
        form.signers.unshift({
          name: contact.display_name,
          email: contact.email,
          send_email: true,
        })
      )
  }
}

/**
 * Set data when creating document via resource
 */
function setDataWhenViaResource() {
  addAssociation(
    {
      id: props.viaResourceId,
      display_name: props.relatedResource.display_name,
    },
    props.viaResource
  )

  if (props.viaResource === 'deals') {
    setDataFromDeal(props.relatedResource)
    showDealSelector.value = false
  }

  if (props.viaResource === 'contacts' && props.relatedResource.email) {
    form.signers.unshift({
      name: props.relatedResource.display_name,
      email: props.relatedResource.email,
      send_email: true,
    })
  }

  if (props.viaResource === 'companies') {
    // eslint-disable-next-line padding-line-between-statements
    ;(props.relatedResource.contacts || [])
      .filter(contact => Boolean(contact.email))
      .forEach(contact => {
        form.signers.unshift({
          name: contact.display_name,
          email: contact.email,
          send_email: true,
        })
      })
  }
}

/**
 * Perform async deals search
 *
 * @param  {String} q
 * @param  {Function} loading
 *
 * @return {Void}
 */
async function searchDeals(q, loading) {
  if (q == '') {
    deals.value = []

    return
  }

  loading(true)

  let { data } = await Innoclapps.request('/deals/search', {
    params: {
      q: q,
      with: 'billable.products',
    },
  })

  deals.value = data
  loading(false)
}

/**
 * Prepare the component
 */
function prepareComponent() {
  if (props.viaResource) {
    setDataWhenViaResource()
  }

  form.set('user_id', currentUser.value.id)
}

prepareComponent()
</script>
