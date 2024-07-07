<template>
  <ISlideover
    id="createDealModal"
    :visible="visible"
    :title="title || $t('deals::deal.create')"
    :ok-text="$t('core::app.create')"
    :ok-disabled="form.busy"
    :size="withProducts ? 'xxl' : 'md'"
    static-backdrop
    form
    @hidden="handleModalHiddenEvent"
    @submit="createUsing ? createUsing(create) : create()"
    @update:visible="$emit('update:visible', $event)"
  >
    <FieldsPlaceholder v-if="!hasFields" />

    <slot name="top" :is-ready="hasFields" />

    <div v-show="fieldsVisible">
      <div v-if="withProducts" class="mb-4 border-b border-neutral-200 pb-8">
        <ITextDisplay class="mb-5 inline-flex items-center">
          {{ $t('billable::product.products') }}

          <ILink
            v-show="withProducts"
            class="ml-2 mt-0.5"
            :text="$t('deals::deal.dont_add_products')"
            @click="hideProductsSection"
          />
        </ITextDisplay>

        <BillableFormTaxTypes v-model="form.billable.tax_type" class="mb-4" />

        <BillableFormTableProducts
          v-model:products="form.billable.products"
          :tax-type="form.billable.tax_type"
        >
          <template #after-product-select="{ index }">
            <IFormError
              :error="form.getError('billable.products.' + index + '.name')"
            />
          </template>
        </BillableFormTableProducts>
      </div>

      <FormFields
        :fields="fields"
        :form="form"
        :resource-name="resourceName"
        is-floating
        focus-first
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      >
        <template #after-contacts-field>
          <ILink
            class="-mt-1 block text-right"
            @click="contactBeingCreated = true"
          >
            &plus; {{ $t('contacts::contact.create') }}
          </ILink>
        </template>

        <template #after-companies-field>
          <ILink
            class="-mt-1 block text-right"
            @click="companyBeingCreated = true"
          >
            &plus; {{ $t('contacts::company.create') }}
          </ILink>
        </template>

        <template #after-amount-field>
          <span class="-mt-2 block text-right">
            <ILink v-show="!withProducts" @click="showProductsSection">
              &plus; {{ $t('deals::deal.add_products') }}
            </ILink>

            <ILink
              v-show="withProducts"
              :text="$t('deals::deal.dont_add_products')"
              @click="hideProductsSection"
            />
          </span>
        </template>
      </FormFields>
    </div>

    <template v-if="withExtendedSubmitButtons" #modal-ok>
      <IExtendedDropdown
        type="submit"
        placement="top-end"
        :disabled="form.busy"
        :loading="form.busy"
        :text="$t('core::app.create')"
      >
        <IDropdownMenu class="min-w-48">
          <IDropdownItem
            :text="$t('core::app.create_and_add_another')"
            @click="createAndAddAnother"
          />

          <IDropdownItem
            v-if="goToList"
            :text="$t('core::app.create_and_go_to_list')"
            @click="createAndGoToList"
          />
        </IDropdownMenu>
      </IExtendedDropdown>
    </template>

    <CreateContactModal
      v-model:visible="contactBeingCreated"
      :overlay="false"
      @created="
        handleAssociateableAdded('contacts', $event.contact),
          (contactBeingCreated = false)
      "
    />

    <CreateCompanyModal
      v-model:visible="companyBeingCreated"
      :overlay="false"
      @created="
        handleAssociateableAdded('companies', $event.company),
          (companyBeingCreated = false)
      "
    />
  </ISlideover>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { whenever } from '@vueuse/core'
import castArray from 'lodash/castArray'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

import BillableFormTableProducts from '@/Billable/components/BillableFormTableProducts.vue'
import BillableFormTaxTypes from '@/Billable/components/BillableFormTaxTypes.vue'

const props = defineProps({
  visible: { type: Boolean, default: true },
  goToList: { type: Boolean, default: true },
  redirectToView: Boolean,
  createUsing: Function,
  withExtendedSubmitButtons: Boolean,
  fieldsVisible: { type: Boolean, default: true },
  title: String,

  disabledFields: [Array, String],
  hiddenFields: [Array, String],

  associations: Object,
  // Must be passed if stageId is provided
  pipeline: Object,
  stageId: Number,
  name: String,
  contacts: Array,
  companies: Array,
})

const emit = defineEmits(['created', 'update:visible', 'ready'])

const resourceName = Innoclapps.resourceName('deals')

const { t } = useI18n()
const router = useRouter()

const contactBeingCreated = ref(false)
const companyBeingCreated = ref(false)

const withProducts = ref(false)

const { setting } = useApp()

const { fields, hasFields, updateField, findField, getCreateFields } =
  useResourceFields()

const { form } = useForm({
  billable: {
    tax_type: setting('tax_type'),
    products: [],
  },
})

const { createResource } = useResourceable(resourceName)

whenever(() => props.visible, prepareComponent, { immediate: true })

function onAfterCreate(data) {
  data.indexRoute = { name: 'deal-index' }

  if (data.action === 'go-to-list') {
    return router.push(data.indexRoute)
  }

  if (data.action === 'create-another') return

  if (props.redirectToView) {
    let deal = data.deal
    router.deal = deal

    router.push({
      name: 'view-deal',
      params: {
        id: deal.id,
      },
    })
  }
}

function handleAssociateableAdded(attribute, record) {
  findField(attribute).options.push(record)
  form[attribute].push(record.id)
}

function handleModalHiddenEvent() {
  withProducts.value = false
  resetBillable()

  fields.value = []
  form.reset()
}

/** Reset the form billable */
function resetBillable() {
  form.billable.products = []
  form.billable.tax_type = setting('tax_type')
}

function showProductsSection() {
  withProducts.value = true
  updateField('amount', { readonly: true })
}

function hideProductsSection() {
  withProducts.value = false
  updateField('amount', { readonly: false })
}

function create() {
  makeCreateRequest().then(onAfterCreate)
}

function createAndAddAnother() {
  makeCreateRequest('create-another').then(data => {
    form.reset()
    onAfterCreate(data)
  })
}

function createAndGoToList() {
  makeCreateRequest('go-to-list').then(onAfterCreate)
}

async function makeCreateRequest(actionType = null) {
  if (!withProducts.value) {
    resetBillable()
  }

  if (props.associations) {
    form.fill(props.associations)
  }

  let deal = await createResource(form).catch(e => {
    if (e.isValidationError()) {
      Innoclapps.error(t('core::app.form_validation_failed'), 3000)
    }

    return Promise.reject(e)
  })

  let payload = {
    deal: deal,
    isRegularAction: actionType === null,
    action: actionType,
  }

  emit('created', payload)

  Innoclapps.success(t('core::resource.created'))

  return payload
}

async function prepareComponent() {
  let createFields = await getCreateFields(resourceName)
  const hasField = attr => createFields.some(f => f.attribute === attr)
  const findFieldIndex = attr => findIndex(createFields, ['attribute', attr])

  for (let attribute of ['contacts', 'companies']) {
    if (!props[attribute]) continue
    let relatedIndex = findFieldIndex(attribute)

    if (relatedIndex !== -1) {
      createFields[relatedIndex].value = props[attribute]
    } else {
      form.set(
        attribute,
        props[attribute].map(record =>
          typeof record === 'object' ? record.id : record
        )
      )
    }
  }

  // Show related companies or contacts based on the companies/contacts selection.
  // If not it will be very hard to choose contact and companies manually when you have alot of contacts.
  if (hasField('contacts') && hasField('companies')) {
    let contactsFIdx = findFieldIndex('contacts')
    let companiesFIdx = findFieldIndex('companies')

    const { lazyLoad: originalContactsLazyLoad } = createFields[contactsFIdx]
    const { lazyLoad: originalCompaniesLazyLoad } = createFields[companiesFIdx]

    createFields[companiesFIdx].lazyLoad = computed(() => {
      if (!form.contacts.length) return originalCompaniesLazyLoad

      return form.contacts.map(contactId => ({
        url: `/contacts/${contactId}/companies`,
      }))
    })

    createFields[contactsFIdx].lazyLoad = computed(() => {
      if (!form.companies.length) return originalContactsLazyLoad

      return form.companies.map(companyId => ({
        url: `/companies/${companyId}/contacts`,
      }))
    })
  }

  if (props.name) {
    createFields[findFieldIndex('name')].value = props.name
  }

  if (props.pipeline) {
    createFields[findFieldIndex('pipeline_id')].value = props.pipeline

    // If pipeline is provided and no stage is provided, the default field value may have a different
    // stage, in this case, we need to set the first stage as active stage from the provided pipeline.
    if (!props.stageId) {
      createFields[findFieldIndex('stage_id')].value = props.pipeline.stages[0]
    }
  }

  if (props.stageId) {
    // Sets to read only as if the user change the e.q. stage
    // manually will have unexpected UI confusions
    createFields[findFieldIndex('stage_id')].value = props.stageId
      ? find(props.pipeline.stages, stage => stage.id === props.stageId)
      : null
  }

  fields.value = createFields

  if (props.disabledFields) {
    castArray(props.disabledFields).forEach(attribute =>
      updateField(attribute, { readonly: true })
    )
  }

  if (props.hiddenFields) {
    castArray(props.hiddenFields).forEach(attribute =>
      updateField(attribute, { displayNone: true })
    )
  }

  emit('ready', { fields, form })
}
</script>
