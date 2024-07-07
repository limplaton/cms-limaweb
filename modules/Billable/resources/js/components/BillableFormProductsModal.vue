<template>
  <IModal
    id="productsModal"
    size="xxl"
    :ok-text="$t('core::app.save')"
    :ok-disabled="form.busy"
    :title="$t('billable::product.manage')"
    :visible="visible"
    static-backdrop
    @ok="save"
    @hidden="handleModalHiddenEvent"
    @show="handleModalShowEvent"
  >
    <IOverlay :show="!formReady">
      <BillableFormTaxTypes v-model="form.tax_type" class="mb-4 mt-6" />

      <BillableFormTableProducts
        v-if="formReady"
        ref="productsRef"
        v-model:products="form.products"
        v-model:removed-products="form.removed_products"
        :tax-type="form.tax_type"
      >
        <template #after-product-select="{ index }">
          <IFormError :error="form.getError('products.' + index + '.name')" />
        </template>
      </BillableFormTableProducts>
    </IOverlay>
  </IModal>
</template>

<script setup>
import { nextTick, ref } from 'vue'
import { whenever } from '@vueuse/core'

import { useForm } from '@/Core/composables/useForm'

import BillableFormTableProducts from './BillableFormTableProducts.vue'
import BillableFormTaxTypes from './BillableFormTaxTypes.vue'

const props = defineProps({
  billable: Object,
  visible: Boolean,
  prefetch: Boolean,
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: Number },
})

const emit = defineEmits(['saved', 'hidden'])

const { form } = useForm({
  products: [],
  removed_products: [],
})

const productsRef = ref(null)
const formReady = ref(false)

function handleModalHiddenEvent() {
  emit('hidden')
  formReady.value = false
}

async function save() {
  let billable = await form.post(
    `${props.resourceName}/${props.resourceId}/billable`
  )

  emit('saved', billable)

  Innoclapps.dialog().hide('productsModal')
}

async function handleModalShowEvent() {
  let billable = structuredClone(
    props.prefetch ? await fetchBillable() : props.billable || {}
  )

  form.set('tax_type', billable.tax_type || 'exclusive')
  form.set('products', billable.products || [])

  formReady.value = true
}

whenever(
  formReady,
  () => {
    if (form.products.length === 0) {
      nextTick(productsRef.value.insertNewLine)
    }
  },
  { flush: 'post' }
)

async function fetchBillable() {
  let { data } = await Innoclapps.request(
    `/${props.resourceName}/${props.resourceId}/billable`
  )

  return data
}
</script>
