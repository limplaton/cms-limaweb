<template>
  <ISlideover
    id="editProductModal"
    :ok-disabled="form.busy || (hasFields && !product.authorizations.update)"
    :ok-loading="form.busy"
    :ok-text="$t('core::app.save')"
    :title="$t('billable::product.edit')"
    visible
    static-backdrop
    form
    @hidden="$router.back"
    @submit="update"
  >
    <FieldsPlaceholder v-if="!hasFields" />

    <FormFields
      :fields="fields"
      :form="form"
      :resource-name="resourceName"
      :resource-id="$route.params.id"
      is-floating
      @update-field-value="form.fill($event.attribute, $event.value)"
      @set-initial-value="form.set($event.attribute, $event.value)"
    />
  </ISlideover>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

import { useProducts } from '../composables/useProducts'

const emit = defineEmits(['updated'])

const resourceName = Innoclapps.resourceName('products')

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { fetchProduct } = useProducts()

const { fields, hasFields, getUpdateFields, hydrateFields } =
  useResourceFields()

const { form } = useForm()
const { updateResource } = useResourceable(resourceName)

const product = ref(null)

async function update() {
  let product = await updateResource(form, route.params.id)

  emit('updated', product)

  Innoclapps.success(t('billable::product.updated'))
  router.back()
}

async function prepareComponent() {
  const [_product, _fields] = await Promise.all([
    fetchProduct(route.params.id),
    getUpdateFields(resourceName, route.params.id),
  ])

  fields.value = _fields
  hydrateFields(_product)
  product.value = _product
}

prepareComponent()
</script>
