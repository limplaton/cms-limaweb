<template>
  <ISlideover
    id="createContactModal"
    :visible="visible"
    :title="title || $t('contacts::contact.create')"
    :ok-text="$t('core::app.create')"
    :ok-disabled="form.busy"
    static-backdrop
    form
    @hidden="handleModalHiddenEvent"
    @submit="createUsing ? createUsing(create) : create()"
    @update:visible="$emit('update:visible', $event)"
  >
    <FieldsPlaceholder v-if="!hasFields" />

    <slot name="top" :is-ready="hasFields" />

    <FormFields
      v-show="fieldsVisible"
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

      <template v-if="trashedContactByEmail !== null" #after-email-field>
        <IAlert
          v-slot="{ variant }"
          class="mb-3"
          dismissible
          @dismissed="
            ;(recentlyRestored.byEmail = false), (trashedContactByEmail = null)
          "
        >
          <IAlertBody>
            {{ $t('contacts::contact.exists_in_trash_by_email') }}
          </IAlertBody>

          <IAlertActions>
            <IButton
              v-if="!recentlyRestored.byEmail"
              :variant="variant"
              :text="$t('core::app.soft_deletes.restore')"
              ghost
              @click="restoreTrashed(trashedContactByEmail.id, 'byEmail')"
            />

            <IButton
              v-if="recentlyRestored.byEmail"
              :variant="variant"
              :to="{
                name: 'view-contact',
                params: { id: trashedContactByEmail.id },
              }"
              :text="$t('core::app.view_record')"
              ghost
            />
          </IAlertActions>
        </IAlert>
      </template>

      <template v-if="trashedContactsByPhone.length > 0" #after-phones-field>
        <IAlert
          v-for="(contact, index) in trashedContactsByPhone"
          v-slot="{ variant }"
          :key="contact.id"
          class="mb-3"
          dismissible
          @dismissed="
            ;(recentlyRestored.byPhone[contact.id] = false),
              (trashedContactsByPhone[index] = null)
          "
        >
          <IAlertBody>
            {{
              $t('contacts::contact.exists_in_trash_by_phone', {
                contact: contact.display_name,
                phone_numbers: contact.phones
                  .map(phone => phone.number)
                  .join(','),
              })
            }}
          </IAlertBody>

          <IAlertActions>
            <IButton
              v-if="!recentlyRestored.byPhone[contact.id]"
              :variant="variant"
              :text="$t('core::app.soft_deletes.restore')"
              ghost
              @click="restoreTrashed(contact.id, 'byPhone')"
            />

            <IButton
              v-if="recentlyRestored.byPhone[contact.id]"
              :variant="variant"
              :to="{
                name: 'view-contact',
                params: { id: contact.id },
              }"
              :text="$t('core::app.view_record')"
              ghost
            />
          </IAlertActions>
        </IAlert>
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
            v-show="goToList"
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
import { ref, shallowRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { whenever } from '@vueuse/core'
import { watchDebounced } from '@vueuse/shared'
import findIndex from 'lodash/findIndex'

import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

const props = defineProps({
  visible: { type: Boolean, default: true },
  goToList: { type: Boolean, default: true },
  redirectToView: Boolean,
  createUsing: Function,
  withExtendedSubmitButtons: Boolean,
  fieldsVisible: { type: Boolean, default: true },
  title: String,

  firstName: String,
  lastName: String,
  email: String,

  associations: Object,

  companies: Array,
  deals: Array,
})

const emit = defineEmits(['created', 'restored', 'update:visible', 'ready'])

const resourceName = Innoclapps.resourceName('contacts')

const { t } = useI18n()
const router = useRouter()

const dealBeingCreated = ref(false)
const companyBeingCreated = ref(false)

const trashedContactByEmail = shallowRef(null)
const trashedContactsByPhone = ref([])

const recentlyRestored = ref({
  byEmail: false,
  byPhone: {},
})

const { fields, hasFields, findField, getCreateFields } = useResourceFields()

const { createResource } = useResourceable(resourceName)

const { form } = useForm({
  avatar: null,
})

whenever(() => props.visible, prepareComponent, { immediate: true })

watchDebounced(
  () => form.email,
  newVal => {
    if (!newVal) {
      trashedContactByEmail.value = null

      return
    }

    Innoclapps.request('/trashed/contacts/search', {
      params: {
        q: newVal,
        search_fields: 'email:=',
      },
    }).then(({ data: contacts }) => {
      trashedContactByEmail.value = contacts.length > 0 ? contacts[0] : null
    })
  },
  { debounce: 500 }
)

watchDebounced(
  () => form.phones,
  newVal => {
    const numbers = newVal.map(phone => phone.number)

    if (numbers.length === 0) {
      trashedContactsByPhone.value = []

      return
    }

    Innoclapps.request('/trashed/contacts/search', {
      params: {
        q: numbers.join(','),
        search_fields: 'phones.number:in',
      },
    }).then(({ data: contacts }) => {
      trashedContactsByPhone.value = contacts
    })
  },
  { debounce: 500, deep: true }
)

function onAfterCreate(data) {
  data.indexRoute = { name: 'contact-index' }

  if (data.action === 'go-to-list') {
    return router.push(data.indexRoute)
  }

  if (data.action === 'create-another') return

  if (props.redirectToView) {
    let contact = data.contact
    router.contact = contact

    router.push({
      name: 'view-contact',
      params: {
        id: contact.id,
      },
    })
  }
}

function handleAssociateableAdded(attribute, record) {
  findField(attribute).options.push(record)
  form[attribute].push(record.id)
}

function handleModalHiddenEvent() {
  fields.value = []
  form.reset()
}

function restoreTrashed(id, type) {
  Innoclapps.request()
    .post(`/trashed/contacts/${id}`)
    .then(({ data }) => {
      if (typeof recentlyRestored.value[type] === 'object') {
        recentlyRestored.value[type][data.id] = true
      } else {
        recentlyRestored.value[type] = true
      }

      emit('restored', data)
    })
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
  if (props.associations) {
    form.fill(props.associations)
  }

  let contact = await createResource(form).catch(e => {
    if (e.isValidationError()) {
      Innoclapps.error(t('core::app.form_validation_failed'), 3000)
    }

    return Promise.reject(e)
  })

  let payload = {
    contact: contact,
    isRegularAction: actionType === null,
    action: actionType,
  }

  emit('created', payload)

  Innoclapps.success(t('core::resource.created'))

  return payload
}

async function prepareComponent() {
  const createFields = await getCreateFields(resourceName)
  const findFieldIndex = attr => findIndex(createFields, ['attribute', attr])

  // From props, same attribute name and prop name
  for (let attribute of ['companies', 'deals']) {
    if (!props[attribute]) continue
    let fIdx = findFieldIndex(attribute)

    // Perhaps is not visible?
    if (fIdx === -1) {
      form.set(
        attribute,
        props[attribute].map(record =>
          typeof record === 'object' ? record.id : record
        )
      )
    } else {
      createFields[fIdx].value = props[attribute]
    }
  }

  if (props.firstName) {
    createFields[findFieldIndex('first_name')].value = props.firstName
  }

  const lastNameFIdx = props.lastName && findFieldIndex('last_name')

  if (lastNameFIdx && createFields[lastNameFIdx]) {
    createFields[lastNameFIdx].value = props.lastName
  }

  const emailFIdx = props.email && findFieldIndex('email')

  if (emailFIdx && createFields[emailFIdx]) {
    createFields[emailFIdx].value = props.email
  }

  fields.value = createFields

  emit('ready', { fields, form })
}
</script>
