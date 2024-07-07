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

  <IndexNumericField
    v-bind="$attrs"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
    :edit-action="
      field.onlyProducts || hasProducts ? initiateProductsModal : undefined
    "
    @reload="$emit('reload')"
  >
    <template #numeric-field="{ formattedValue }">
      {{ formattedValue }}
      <span
        v-if="hasProducts"
        class="ml-1 text-xs text-neutral-500 dark:text-neutral-400"
      >
        ({{ $t('billable::product.count', { count: row.products_count }) }})
      </span>
    </template>

    <template #after-inline-edit-form-fields="{ hidePopover }">
      <ILink
        class="flex items-center"
        @click="initiateProductsModal(), hidePopover()"
      >
        <span v-t="'billable::product.manage'"></span>

        <Icon icon="Window" class="ml-2 mt-px size-4" />
      </ILink>
    </template>
  </IndexNumericField>
</template>

<script setup>
import { computed, ref } from 'vue'

import BillableFormProductsModal from '../../components/BillableFormProductsModal.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps(['resourceName', 'resourceId', 'row', 'field'])

const emit = defineEmits(['reload'])

const manageProducts = ref(false)

const hasProducts = computed(() => props.row.products_count > 0)

function initiateProductsModal(e) {
  // Prevent the popover from opening.
  if (e) {
    e.preventDefault()
  }

  manageProducts.value = true
}

function handleBillableModelSavedEvent() {
  emit('reload')
}
</script>
