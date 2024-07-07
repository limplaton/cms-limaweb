<template>
  <div v-show="isVisible">
    <ITextDark class="space-x-1">
      <span class="font-semibold">{{ label }}:</span>

      <span v-for="address in wrappedAddresses" :key="address.address">
        <MessageAddress :address="address" />

        <span
          v-if="!hasAddresses"
          v-text="'(' + $t('mailclient::inbox.unknown_address') + ')'"
        />
      </span>
    </ITextDark>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import castArray from 'lodash/castArray'

import MessageAddress from './MessageAddress.vue'

const props = defineProps({
  showWhenEmpty: { default: true, type: Boolean },
  label: String,
  addresses: {},
})

const wrappedAddresses = computed(() =>
  castArray(props.addresses ? props.addresses : [])
)

const hasAddresses = computed(
  () => !props.addresses || wrappedAddresses.value.length > 0
)

/**
 * If "addresses" is empty, it can be a draft message with not yet added e.q. to headers
 */
const isVisible = computed(() => {
  if (props.showWhenEmpty) {
    return true
  }

  if (!hasAddresses.value && props.showWhenEmpty === false) {
    return false
  }

  return true
})
</script>
