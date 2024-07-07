<template>
  <Compose
    v-if="isComposing"
    ref="composeRef"
    :visible="isComposing"
    :to="to"
    @hidden="isComposing = false"
  />

  <BaseEmailField
    v-bind="$attrs"
    :column="column"
    :row="row"
    :field="field"
    :resource-name="resourceName"
    :resource-id="resourceId"
    @show="fetchEmailAccounts"
  >
    <template v-if="field.value" #start>
      <span
        v-i-tooltip="
          emailAccounts.length
            ? ''
            : $t('mailclient::mail.account.integration_not_configured')
        "
      >
        <IDropdownItem
          href="#"
          :disabled="!emailAccounts.length"
          :text="$t('mailclient::mail.create')"
          @click="compose(true)"
        />
      </span>
    </template>
  </BaseEmailField>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'

import BaseEmailField from '@/Core/fields/Index/EmailField.vue'

import { useEmailAccounts } from '../../composables/useEmailAccounts'
import Compose from '../../views/Emails/ComposeMessage.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps([
  'column',
  'row',
  'field',
  'resourceName',
  'resourceId',
])

const { emailAccounts, fetchEmailAccounts } = useEmailAccounts()

const isComposing = ref(false)
const composeRef = ref(null)

/**
 * Get the predefined TO property
 */
const to = computed(() => [
  {
    address: props.field.value,
    name: props.row.display_name || props.row.name,
    resourceName: props.resourceName,
    path: props.row.path, // for associations popover
    id: props.row.id,
  },
])

/**
 * Compose new email
 */
function compose(state = true) {
  isComposing.value = state

  nextTick(() => {
    composeRef.value.subjectRef.focus()
  })
}
</script>
