<template>
  <ISlideover
    id="createCompanyModal"
    :visible="visible"
    :title="title || $t('contacts::company.create')"
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

    <div v-show="fieldsVisible">
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
          <ILink
            class="-mt-1 block text-right"
            @click="dealBeingCreated = true"
          >
            &plus; {{ $t('deals::deal.create') }}
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

        <template v-if="trashedCompanyByEmail !== null" #after-email-field>
          <IAlert
            v-slot="{ variant }"
            class="mb-3"
            dismissible
            @dismissed="
              ;(recentlyRestored.byEmail = false),
                (trashedCompanyByEmail = null)
            "
          >
            <IAlertBody>
              {{ $t('contacts::company.exists_in_trash_by_email') }}
            </IAlertBody>

            <IAlertActions>
              <IButton
                v-if="!recentlyRestored.byEmail"
                :variant="variant"
                :text="$t('core::app.soft_deletes.restore')"
                ghost
                @click="restoreTrashed(trashedCompanyByEmail.id, 'byEmail')"
              />

              <IButton
                v-if="recentlyRestored.byEmail"
                :variant="variant"
                :to="{
                  name: 'view-company',
                  params: { id: trashedCompanyByEmail.id },
                }"
                :text="$t('core::app.view_record')"
                ghost
              />
            </IAlertActions>
          </IAlert>
        </template>

        <template v-if="trashedCompanyByName !== null" #after-name-field>
          <IAlert
            v-if="trashedCompanyByName"
            v-slot="{ variant }"
            class="mb-3"
            dismissible
            @dismissed="
              ;(recentlyRestored.byName = false), (trashedCompanyByName = null)
            "
          >
            <IAlertBody>
              {{ $t('contacts::company.exists_in_trash_by_name') }}
            </IAlertBody>

            <IAlertActions>
              <IButton
                v-if="!recentlyRestored.byName"
                :variant="variant"
                :text="$t('core::app.soft_deletes.restore')"
                ghost
                @click="restoreTrashed(trashedCompanyByName.id, 'byName')"
              />

              <IButton
                v-if="recentlyRestored.byName"
                :variant="variant"
                :to="{
                  name: 'view-company',
                  params: { id: trashedCompanyByName.id },
                }"
                :text="$t('core::app.view_record')"
                ghost
              />
            </IAlertActions>
          </IAlert>
        </template>

        <template v-if="trashedCompaniesByPhone.length > 0" #after-phones-field>
          <IAlert
            v-for="(company, index) in trashedCompaniesByPhone"
            :key="company.id"
            v-slot="{ variant }"
            class="mb-3"
            dismissible
            @dismissed="
              ;(recentlyRestored.byPhone[company.id] = false),
                (trashedCompaniesByPhone[index] = null)
            "
          >
            <IAlertBody>
              {{
                $t('contacts::company.exists_in_trash_by_phone', {
                  company: company.display_name,
                  phone_numbers: company.phones
                    .map(phone => phone.number)
                    .join(','),
                })
              }}
            </IAlertBody>

            <IAlertActions>
              <IButton
                v-if="!recentlyRestored.byPhone[company.id]"
                :variant="variant"
                :text="$t('core::app.soft_deletes.restore')"
                ghost
                @click="restoreTrashed(company.id, 'byPhone')"
              />

              <IButton
                v-if="recentlyRestored.byPhone[company.id]"
                :variant="variant"
                :to="{
                  name: 'view-company',
                  params: { id: company.id },
                }"
                :text="$t('core::app.view_record')"
                ghost
              />
            </IAlertActions>
          </IAlert>
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
  </ISlideover>
</template>

<script setup>
import { computed, ref, shallowRef } from 'vue'
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

  contacts: Array,
  deals: Array,
})

const emit = defineEmits(['created', 'restored', 'update:visible', 'ready'])

const { t } = useI18n()
const router = useRouter()

const resourceName = Innoclapps.resourceName('companies')

const dealBeingCreated = ref(false)
const contactBeingCreated = ref(false)

const phoneField = computed(() => findField('phones'))

const trashedCompanyByEmail = shallowRef(null)
const trashedCompanyByName = shallowRef(null)
const trashedCompaniesByPhone = ref([])

const recentlyRestored = ref({
  byName: false,
  byEmail: false,
  byPhone: {},
})

const { fields, hasFields, findField, getCreateFields } = useResourceFields()

const { form } = useForm()
const { createResource } = useResourceable(resourceName)

whenever(() => props.visible, prepareComponent, { immediate: true })

watchDebounced(
  () => form.email,
  newVal => {
    if (!newVal) {
      trashedCompanyByEmail.value = null

      return
    }

    searchTrashedCompanies(newVal, 'email').then(({ data: companies }) => {
      trashedCompanyByEmail.value = companies.length > 0 ? companies[0] : null
    })
  },
  { debounce: 500 }
)

watchDebounced(
  () => form.name,
  newVal => {
    if (!newVal) {
      trashedCompanyByName.value = null

      return
    }

    searchTrashedCompanies(newVal, 'name').then(({ data: companies }) => {
      trashedCompanyByName.value = companies.length > 0 ? companies[0] : null
    })
  },
  { debounce: 500 }
)

watchDebounced(
  () => form.phones,
  newVal => {
    if (!newVal) return

    const numbers = newVal
      .filter(
        phone =>
          !phone.number ||
          !(
            phoneField.value.callingPrefix &&
            phoneField.value.callingPrefix.trim() === phone.number
          )
      )
      .map(phone => phone.number)

    if (numbers.length === 0) {
      trashedCompaniesByPhone.value = []

      return
    }

    Innoclapps.request('/trashed/companies/search', {
      params: {
        q: numbers.join(','),
        search_fields: 'phones.number:in',
      },
    }).then(({ data: companies }) => {
      trashedCompaniesByPhone.value = companies
    })
  },
  { debounce: 500, deep: true }
)

function onAfterCreate(data) {
  data.indexRoute = { name: 'company-index' }

  if (data.action === 'go-to-list') {
    return router.push(data.indexRoute)
  }

  if (data.action === 'create-another') return

  if (props.redirectToView) {
    let company = data.company
    router.company = company

    router.push({
      name: 'view-company',
      params: {
        id: company.id,
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

function searchTrashedCompanies(q, field) {
  return Innoclapps.request('/trashed/companies/search', {
    params: {
      q: q,
      search_fields: field + ':=',
    },
  })
}

function restoreTrashed(id, type) {
  Innoclapps.request()
    .post(`/trashed/companies/${id}`)
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
  let company = await createResource(form).catch(e => {
    if (e.isValidationError()) {
      Innoclapps.error(t('core::app.form_validation_failed'), 3000)
    }

    return Promise.reject(e)
  })

  let payload = {
    company: company,
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
  for (let attribute of ['contacts', 'deals']) {
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

  fields.value = createFields

  emit('ready', { fields, form })
}
</script>
