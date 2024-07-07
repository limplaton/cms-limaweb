<template>
  <tr>
    <td
      width="30%"
      class="px-3 py-2.5 align-top text-sm font-medium text-neutral-900 dark:text-neutral-100"
    >
      <div class="relative w-60 sm:w-auto">
        <ICustomSelect
          v-model="selectedProduct"
          label="name"
          :create-option-provider="createNewProductViaSelect"
          :option-label="provideProductSelectFieldOptionLabel"
          :placeholder="$t('billable::product.choose_or_enter')"
          :options="productsForDropdown"
          :loading="retrievingProducts"
          truncate
          filterable
          taggable
          debounce
          @option-selected="handleProductChangeEvent"
          @cleared="handleProductChangeEvent(null)"
          @search="performSearch"
          @open="handleProductDropdownOpen"
        />

        <slot name="after-product-select" :product="product" :index="index" />

        <IFormTextarea
          v-show="selectedProduct"
          class="mt-1"
          :model-value="product.description"
          :name="'products' + '.' + index + '.description'"
          :placeholder="
            $t('billable::product.description') +
            ' ' +
            '(' +
            $t('core::app.optional') +
            ')'
          "
          :rows="3"
          @update:model-value="updateProduct({ description: $event })"
        />
      </div>
    </td>

    <td
      class="px-2 py-2.5 align-top text-sm font-medium text-neutral-900 dark:text-neutral-100"
    >
      <div class="w-32 sm:w-auto">
        <IFormNumericInput
          class="text-right"
          decimal-separator="."
          pattern=".*"
          :precision="2"
          :empty-value="1"
          :placeholder="$t('billable::product.quantity')"
          :model-value="product.qty"
          @update:model-value="updateProduct({ qty: $event })"
        />

        <IFormInput
          class="mt-1 text-right"
          :placeholder="$t('billable::product.unit')"
          :model-value="product.unit"
          @update:model-value="updateProduct({ unit: $event })"
        />
      </div>
    </td>

    <td
      class="px-2 py-2.5 align-top text-sm font-medium text-neutral-900 dark:text-neutral-100"
    >
      <div class="w-40 sm:w-auto">
        <IFormNumericInput
          class="text-right"
          :placeholder="$t('billable::product.unit_price')"
          :minus="true"
          :model-value="product.unit_price"
          @update:model-value="updateProduct({ unit_price: $event })"
        />
      </div>
    </td>

    <td
      v-show="taxType !== 'no_tax'"
      class="px-2 py-2.5 align-top text-sm font-medium text-neutral-900 dark:text-neutral-100"
    >
      <div class="w-44 sm:w-auto">
        <div class="flex items-center rounded-lg shadow-sm">
          <div class="relative">
            <IFormNumericInput
              class="z-10 grow rounded-r-none !pr-8"
              :placeholder="$t('billable::product.tax_percent')"
              :precision="3"
              :minus="true"
              :max="100"
              :model-value="product.tax_rate"
              @update:model-value="updateProduct({ tax_rate: $event })"
            />

            <div
              class="pointer-events-none absolute inset-y-0 right-0 z-20 flex items-center pr-3"
              v-text="'%'"
            />
          </div>

          <IFormInput
            class="-ml-px w-16 rounded-l-none text-center focus:z-20"
            :model-value="product.tax_label"
            @update:model-value="updateProduct({ tax_label: $event })"
          />
        </div>
      </div>
    </td>

    <td
      class="px-2 py-2.5 align-top text-sm font-medium text-neutral-900 dark:text-neutral-100"
    >
      <div class="relative w-44 rounded-lg shadow-sm sm:w-auto">
        <IFormNumericInput
          v-if="modelValue.discount_type == 'fixed'"
          class="!pr-12"
          :placeholder="$t('billable::product.discount_amount')"
          :model-value="product.discount_total"
          @update:model-value="updateProduct({ discount_total: $event })"
        />

        <IFormNumericInput
          v-else
          class="!pr-12"
          :placeholder="$t('billable::product.discount_percent')"
          :max="100"
          :precision="2"
          :model-value="product.discount_total"
          @update:model-value="updateProduct({ discount_total: $event })"
        />

        <div class="absolute inset-y-0 right-0 flex items-center">
          <IFormSelect
            class="rounded-l-none bg-transparent bg-none !pl-1 !pr-2 text-center ring-0 dark:bg-transparent"
            :model-value="product.discount_type"
            @update:model-value="updateProduct({ discount_type: $event })"
          >
            <option
              v-for="dType in discountTypes"
              :key="dType.value"
              :value="dType.value"
              v-text="dType.label"
            />
          </IFormSelect>
        </div>
      </div>
    </td>

    <td
      class="px-2 py-4 text-center align-top text-sm font-medium text-neutral-900 dark:text-neutral-100"
    >
      {{ formatMoney(amountBeforeTaxWithDiscountApplied) }}
    </td>

    <slot />
  </tr>
</template>

<script setup>
import { computed, onMounted, ref, shallowRef } from 'vue'
import { useI18n } from 'vue-i18n'

import { useAccounting } from '@/Core/composables/useAccounting'
import { useApp } from '@/Core/composables/useApp'
import { deepCloneWithUniqueFormId } from '@/Core/composables/useForm'

import { useProducts } from '../composables/useProducts'

import { blankProduct, totalProductAmountWithDiscount } from './utils'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  taxType: { required: true, type: String },
  index: { required: true, type: Number },
})

const emit = defineEmits(['update:modelValue', 'productSelected'])

const { t } = useI18n()

const { formatMoney } = useAccounting()
const { scriptConfig, setting } = useApp()

const {
  limitedNumberOfActiveProducts: products,
  retrieveLimitedNumberOfActiveProducts,
  limitedNumberOfActiveProductsRetrieved,
  fetchActiveProducts,
  fetchProductByName,
} = useProducts()

const searchResults = shallowRef(null)

// use local var so the loader is shown only on one field not on all
const retrievingProducts = ref(false)

const product = ref(props.modelValue || [])

let selectedProduct = ref(null)

const productsForDropdown = computed(
  () => searchResults.value || products.value
)

function handleProductDropdownOpen() {
  if (limitedNumberOfActiveProductsRetrieved.value) {
    return
  }

  retrievingProducts.value = true

  retrieveLimitedNumberOfActiveProducts().then(
    () => (retrievingProducts.value = false)
  )
}

function performSearch(search, loading) {
  if (search == '') {
    loading(false)
    searchResults.value = null

    return
  }

  loading(true)

  fetchActiveProducts({ params: { q: search } })
    .then(({ data }) => {
      searchResults.value = data
    })
    .finally(() => loading(false))
}

onMounted(() => {
  selectedProduct.value = props.modelValue || []
})

const discountTypes = [
  { label: scriptConfig('currency.iso_code'), value: 'fixed' },
  { label: '%', value: 'percent' },
]

/**
 * Get the amount before any tax calculations and with discount applied
 * for the last amount column
 */
const amountBeforeTaxWithDiscountApplied = computed(() => {
  return totalProductAmountWithDiscount(
    props.modelValue,
    props.taxType === 'inclusive'
  )
})

/**
 * Create new product for select
 */
function createNewProductViaSelect(newOption) {
  return blankProduct({
    name: newOption,
    discount_type: setting('discount_type'),
    tax_rate: setting('tax_rate'),
    tax_label: setting('tax_label'),
  })
}

/**
 * Provide the select field option label
 */
function provideProductSelectFieldOptionLabel(option) {
  // Allow sku in label to be searchable as well
  return option.sku ? `${option.sku}: ${option.name}` : option.name
}

/**
 * Handle the product change event
 */
async function handleProductChangeEvent(product) {
  if (!product) {
    updateProduct({ name: null, product_id: null })

    return
  }

  const billableProduct = {
    product_id: product.id,
    name: product.name,
    description: product.description,
    unit_price: product.unit_price || 0,
    unit: product.unit,
    tax_rate: product.tax_rate || 0,
    tax_label: product.tax_label,
  }

  // We will try to find an existing product from the product list
  // based on the name user entered, users may enter names but don't realize
  // that the product already exists, in this case, we will help the user
  // to pre-use the product and prevent creating this product.
  const productByName = await fetchProductByName(product.name)

  if (!productByName) {
    Innoclapps.info(
      t('billable::product.will_be_added_as_new', { name: product.name })
    )
    // Set the "product_id" to null in case, previously there was selected existing product.
    billableProduct.product_id = null
  } else {
    // Update the "product_id", as the value may be empty.
    billableProduct.product_id = productByName.id

    // Add the latest selected product from search to the loaded products list.
    if (!products.value.find(p => p.id == productByName.id)) {
      products.value.unshift(productByName)
    }
  }

  updateProduct(billableProduct)
  emit('productSelected', { product: product.value, index: props.index })
}

function updateProduct(property, value = null) {
  let modelValue = deepCloneWithUniqueFormId(
    props.modelValue,
    typeof property === 'object' ? property : { [property]: value }
  )

  emit('update:modelValue', modelValue)
  product.value = modelValue
}
</script>
