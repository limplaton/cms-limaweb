<template>
  <div class="mb-2 mt-4 grid grid-cols-12 gap-2">
    <div
      class="col-span-8 text-base/5 text-neutral-600 dark:text-neutral-100 sm:col-span-5 sm:col-start-5 sm:text-right sm:text-sm/5"
    >
      {{ $t('billable::billable.sub_total') }}

      <p
        v-show="hasDiscount"
        class="italic text-neutral-500 dark:text-neutral-300"
      >
        ({{
          $t('billable::billable.includes_discount', {
            amount: formatMoney(totalDiscount),
          })
        }})
      </p>
    </div>

    <div
      class="col-span-4 text-right text-base/5 text-neutral-600 dark:text-neutral-100 sm:col-span-3 sm:text-sm/5"
      v-text="formatMoney(subtotal)"
    />
  </div>

  <div
    v-for="tax in taxes"
    v-show="hasTax"
    :key="tax.key"
    class="mb-2 grid grid-cols-12 gap-2"
  >
    <div
      class="col-span-8 text-base/5 text-neutral-600 dark:text-neutral-100 sm:col-span-5 sm:col-start-5 sm:text-right sm:text-sm/5"
    >
      {{ tax.label }} ({{ tax.rate }}%)
    </div>

    <div
      class="col-span-4 text-right text-base/5 text-neutral-600 dark:text-neutral-100 sm:col-span-3 sm:text-sm/5"
    >
      <span>
        <span
          v-show="isTaxInclusive"
          v-t="'billable::billable.tax_amount_is_inclusive'"
        />
        {{ formatMoney(tax.raw_total) }}
      </span>
    </div>
  </div>

  <div class="grid grid-cols-12 gap-2">
    <div
      v-t="'billable::billable.total'"
      class="col-span-8 text-base/5 font-semibold text-neutral-900 dark:text-neutral-100 sm:col-span-5 sm:col-start-5 sm:text-right sm:text-sm/5"
    />

    <div
      class="col-span-4 text-right text-base/5 font-medium text-neutral-900 dark:text-neutral-100 sm:col-span-3 sm:text-sm/5"
      v-text="formatMoney(total)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

import { useAccounting } from '@/Core/composables/useAccounting'

const props = defineProps({
  taxType: { required: true },
  total: { required: true },
  totalDiscount: { required: true },
  subtotal: { required: true },
  taxes: { default: () => [] },
})

const { formatMoney } = useAccounting()

const hasDiscount = computed(() => props.totalDiscount > 0)
const hasTax = computed(() => props.taxType !== 'no_tax')
const isTaxInclusive = computed(() => props.taxType === 'inclusive')
</script>
