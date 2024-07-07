<template>
  <BillableFormProductsModal
    v-if="manageProducts"
    :resource-name="resourceName"
    :resource-id="resourceId"
    visible
    prefetch
    @saved="handleBillableModelSavedEvent"
    @hidden="manageProducts = false"
  />

  <DetailNumericField
    v-bind="$attrs"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :is-floating="isFloating"
    :field="{ ...field, readonly: hasProducts }"
    :resource="resource"
    :edit-action="
      field.onlyProducts || hasProducts ? initiateProductsModal : undefined
    "
    @updated="$emit('updated', $event)"
  >
    <template #numeric-field="{ formattedValue }">
      {{ formattedValue }}
      <span
        v-if="hasProducts"
        class="ml-1 text-xs text-neutral-500 dark:text-neutral-400"
      >
        ({{
          $t('billable::product.count', { count: resource.products_count })
        }})
      </span>
    </template>

    <template #after-inline-edit-form-fields="{ hidePopover }">
      <ILink
        class="inline-flex items-center"
        @click="initiateProductsModal(), hidePopover()"
      >
        <span v-t="'billable::product.manage'"></span>

        <Icon icon="Window" class="ml-2 mt-px size-4" />
      </ILink>
    </template>
  </DetailNumericField>
</template>

<script setup>
import { computed, ref } from 'vue'

import BillableFormProductsModal from '../../components/BillableFormProductsModal.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps([
  'resource',
  'resourceName',
  'resourceId',
  'field',
  'isFloating',
])

const emit = defineEmits(['updated'])

const hasProducts = computed(() => props.resource.products_count > 0)

const manageProducts = ref(false)

function handleBillableModelSavedEvent(billable) {
  emit(
    'updated',
    Object.assign({}, props.resource, {
      billable,
      [props.field.attribute]: billable.total,
      products_count: billable.products.length,
    })
  )
}

function initiateProductsModal(e) {
  // Prevent the popover from opening.
  if (e) {
    e.preventDefault()
  }

  manageProducts.value = true
}
</script>
