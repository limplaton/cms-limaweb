<template>
  <ISlideover
    id="createProductModal"
    :title="$t('billable::product.create')"
    visible
    static-backdrop
    form
    @submit="create"
    @hidden="$router.back"
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
      <template v-if="trashedProduct !== null" #after-name-field>
        <IAlert
          v-slot="{ variant }"
          class="mb-3"
          dismissible
          @dismissed="
            ;(recentlyRestored.byName = false), (trashedProduct = null)
          "
        >
          <IAlertBody>
            {{ $t('billable::product.exists_in_trash_by_name') }}
          </IAlertBody>

          <IAlertActions>
            <IButton
              v-if="!recentlyRestored.byName"
              :variant="variant"
              :text="$t('core::app.soft_deletes.restore')"
              ghost
              @click="restoreTrashed(trashedProduct.id, 'byName')"
            />

            <IButton
              v-if="recentlyRestored.byName"
              :variant="variant"
              :text="$t('core::app.view_record')"
              ghost
              @click="
                $router.replace({
                  name: 'view-product',
                  params: { id: trashedProduct.id },
                })
              "
            />
          </IAlertActions>
        </IAlert>
      </template>
    </FormFields>

    <template #modal-ok>
      <IExtendedDropdown
        type="submit"
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
            :text="$t('core::app.create_and_go_to_list')"
            @click="createAndGoToList"
          />
        </IDropdownMenu>
      </IExtendedDropdown>
    </template>
  </ISlideover>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { watchDebounced } from '@vueuse/core'

import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

const emit = defineEmits(['created', 'restored'])

const resourceName = Innoclapps.resourceName('products')

const { t } = useI18n()
const router = useRouter()

const { fields, hasFields, getCreateFields } = useResourceFields()
const { form } = useForm()
const { createResource } = useResourceable(resourceName)

const trashedProduct = ref(null)

const recentlyRestored = ref({
  byName: false,
})

watchDebounced(
  () => form.name,
  newVal => {
    if (!newVal) {
      trashedProduct.value = null

      return
    }

    Innoclapps.request('/trashed/products/search', {
      params: {
        q: newVal,
        search_fields: 'name:=',
      },
    }).then(({ data: products }) => {
      trashedProduct.value = products.length > 0 ? products[0] : null
    })
  },
  { debounce: 500 }
)

function create() {
  makeCreateRequest().then(() => router.back())
}

function createAndAddAnother() {
  makeCreateRequest().then(() => form.reset())
}

function createAndGoToList() {
  makeCreateRequest().then(() => router.push('/products'))
}

async function makeCreateRequest() {
  try {
    let product = await createResource(form)

    emit('created', product)

    Innoclapps.success(t('billable::product.created'))

    return product
  } catch (e) {
    if (e.isValidationError()) {
      Innoclapps.error(t('core::app.form_validation_failed'), 3000)
    }

    return Promise.reject(e)
  }
}

async function restoreTrashed(id, type) {
  await Innoclapps.request().post(`/trashed/products/${id}`)

  recentlyRestored.value[type] = true
  emit('restored', trashedProduct)
}

async function prepareComponent() {
  const createFields = await getCreateFields(resourceName)
  fields.value = createFields
}

prepareComponent()
</script>
