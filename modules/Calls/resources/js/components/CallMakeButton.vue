<template>
  <span v-i-tooltip="isDisabled ? callDropdownTooltip : null">
    <IDropdown v-if="hasMorePhoneNumbers">
      <IDropdownButton
        variant="primary"
        icon="Phone"
        class="w-full"
        :disabled="isDisabled"
        :text="$t('calls::call.make')"
        soft
      />

      <IDropdownMenu>
        <IDropdownItem
          v-for="(phoneNumber, index) in phoneNumbers"
          :key="phoneNumber.phoneNumber + phoneNumber.type + index"
          :icon="phoneNumber.type == 'mobile' ? 'DeviceMobile' : 'Phone'"
          @click="requestNewCall(phoneNumber.phoneNumber)"
        >
          {{ phoneNumber.phoneNumber }}
          <span class="text-xs"> ({{ phoneNumber.resourceDisplayName }}) </span>
        </IDropdownItem>
      </IDropdownMenu>
    </IDropdown>

    <IButton
      v-else
      icon="Phone"
      variant="primary"
      :disabled="!hasPhoneNumbers || isDisabled"
      :text="$t('calls::call.make')"
      soft
      @click="requestNewCall(onlyPhoneNumbers[0])"
    />
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import castArray from 'lodash/castArray'

import { useGate } from '@/Core/composables/useGate'

import { useVoip } from '../composables/useVoip'

const props = defineProps({
  resourceName: { required: true, type: String },
  resource: { required: true, type: Object },
})

const emit = defineEmits(['requested'])

const { t } = useI18n()
const { gate } = useGate()
const { hasVoIPClient } = useVoip()

const callDropdownTooltip = computed(() => {
  if (!hasVoIPClient) {
    return t('core::app.integration_not_configured')
  } else if (gate.userCant('use voip')) {
    return t('calls::call.no_voip_permissions')
  }

  return ''
})

const isDisabled = computed(() => gate.userCant('use voip') || !hasVoIPClient)

const phoneNumbers = computed(() => {
  let numbers = []

  numbers.push(...getPhoneNumbersFromResource(props.resource))

  switch (props.resourceName) {
    case 'contacts':
      numbers.push(
        ...getPhoneNumbersFromResource(props.resource.companies || [])
      )
      break
    case 'companies':
      numbers.push(
        ...getPhoneNumbersFromResource(props.resource.contacts || [])
      )
      break
    case 'deals':
      numbers.push(
        ...getPhoneNumbersFromResource(props.resource.companies || [])
      )

      numbers.push(
        ...getPhoneNumbersFromResource(props.resource.contacts || [])
      )
      break
  }

  return numbers
})

const onlyPhoneNumbers = computed(() =>
  phoneNumbers.value.map(phone => phone.phoneNumber)
)

const totalPhoneNumbers = computed(() => onlyPhoneNumbers.value.length)

const hasPhoneNumbers = computed(() => totalPhoneNumbers.value > 0)

const hasMorePhoneNumbers = computed(() => totalPhoneNumbers.value > 1)

function requestNewCall(phoneNumber) {
  emit('requested', phoneNumber)
}

function getPhoneNumbersFromResource(resource) {
  let numbers = []

  castArray(resource).forEach(resource => {
    numbers = numbers.concat(
      ...(resource.phones || []).map(phone => {
        return {
          type: phone.type,
          phoneNumber: phone.number,
          resourceDisplayName: resource.display_name,
        }
      })
    )
  })

  return numbers
}
</script>
