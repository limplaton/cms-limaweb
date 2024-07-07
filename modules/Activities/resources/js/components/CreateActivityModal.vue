<template>
  <ISlideover
    id="createActivityModal"
    :visible="visible"
    :title="title || $t('activities::activity.create')"
    :ok-text="$t('core::app.create')"
    :ok-disabled="form.busy"
    static-backdrop
    form
    @hidden="handleModalHiddenEvent"
    @update:visible="$emit('update:visible', $event)"
    @submit="create"
  >
    <FieldsPlaceholder v-if="!hasFields" />

    <FormFields
      :fields="fields"
      :form="form"
      :resource-name="resourceName"
      is-floating
      focus-first
      @update-field-value="form.fill($event.attribute, $event.value)"
      @set-initial-value="form.set($event.attribute, $event.value)"
    >
      <template #after-deals-field>
        <ILink class="-mt-1 block text-right" @click="dealBeingCreated = true">
          &plus; {{ $t('deals::deal.create') }}
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

      <template #after-contacts-field>
        <ILink
          class="-mt-1 block text-right"
          @click="contactBeingCreated = true"
        >
          &plus; {{ $t('contacts::contact.create') }}
        </ILink>
      </template>
    </FormFields>

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

    <CreateDealModal
      v-model:visible="dealBeingCreated"
      :overlay="false"
      @created="
        handleAssociateableAdded('deals', $event.deal),
          (dealBeingCreated = false)
      "
    />

    <CreateContactModal
      v-model:visible="contactBeingCreated"
      :overlay="false"
      @created="
        handleAssociateableAdded('contacts', $event.contact),
          (contactBeingCreated = false)
      "
      @restored="
        handleAssociateableAdded('contacts', $event),
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
      @restored="
        handleAssociateableAdded('companies', $event),
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
import findIndex from 'lodash/findIndex'
import map from 'lodash/map'

import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

const props = defineProps({
  visible: { type: Boolean, default: true },
  goToList: { type: Boolean, default: true },
  redirectToView: Boolean,
  withExtendedSubmitButtons: Boolean,
  title: String,
  note: {},
  description: {},
  activityTypeId: {},
  contacts: {},
  companies: {},
  deals: {},
  dueDate: {},
  endDate: {},
  reminderMinutesBefore: {},
})

const emit = defineEmits(['created', 'update:visible'])

const resourceName = Innoclapps.resourceName('activities')

const { t } = useI18n()
const router = useRouter()

const { fields, hasFields, findField, getCreateFields } = useResourceFields()
const { form } = useForm()
const { createResource } = useResourceable(resourceName)

const dealBeingCreated = ref(false)
const contactBeingCreated = ref(false)
const companyBeingCreated = ref(false)

whenever(() => props.visible, prepareComponent, { immediate: true })

function handleAssociateableAdded(attribute, record) {
  findField(attribute).options.push(record)
  form[attribute].push(record.id)
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
  try {
    let activity = await createResource(form)

    let payload = {
      activity: activity,
      isRegularAction: actionType === null,
      action: actionType,
    }

    emit('created', payload)

    Innoclapps.success(t('activities::activity.created'))

    return payload
  } catch (e) {
    if (e.isValidationError()) {
      Innoclapps.error(t('core::app.form_validation_failed'), 3000)
    }

    return Promise.reject(e)
  }
}

function handleModalHiddenEvent() {
  fields.value = []
}

function onAfterCreate(data) {
  data.indexRoute = { name: 'activity-index' }

  if (data.action === 'go-to-list') {
    return router.push(data.indexRoute)
  }

  if (data.action === 'create-another') return

  // Not used yet as the activity has no view, it's an alias of EDIT
  if (props.redirectToView) {
    let activity = data.activity
    router.activity = activity

    router.push({
      name: 'view-activity',
      params: {
        id: activity.id,
      },
    })
  }
}

async function prepareComponent() {
  const createFields = await getCreateFields(resourceName)
  const hasField = attr => createFields.some(f => f.attribute === attr)
  const findFieldIndex = attr => findIndex(createFields, ['attribute', attr])

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

  fields.value = map(createFields, field => {
    if (
      [
        'contacts',
        'companies',
        'deals',
        'title',
        'note',
        'description',
      ].indexOf(field.attribute) > -1
    ) {
      field.value = props[field.attribute]
    } else if (field.attribute === 'activity_type_id' && props.activityTypeId) {
      field.value = props.activityTypeId
    } else if (field.attribute === 'due_date' && props.dueDate) {
      field.value = props.dueDate // object
    } else if (field.attribute === 'end_date' && props.endDate) {
      field.value = props.endDate // object
    } else if (
      field.attribute === 'reminder_minutes_before' &&
      props.reminderMinutesBefore
    ) {
      field.value = props.reminderMinutesBefore
    }

    return field
  })
}
</script>
