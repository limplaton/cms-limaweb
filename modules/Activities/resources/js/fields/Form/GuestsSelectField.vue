<template>
  <BaseFormField
    v-slot="{ fieldId }"
    :resource-name="resourceName"
    :field="field"
    :value="modelValue"
    :is-floating="isFloating"
  >
    <FormFieldGroup
      :field="field"
      :label="field.label"
      :field-id="fieldId"
      :validation-errors="validationErrors"
      as-paragraph-label
    >
      <div class="block">
        <div class="inline-block">
          <GuestsSelect
            ref="guestsSelectRef"
            :model-value="modelValue"
            :guests="guests"
            :contacts="contacts"
            @update:model-value="updateModelValue"
          />
        </div>
      </div>
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { computed, ref } from 'vue'

import FormFieldGroup from '@/Core/fields/FormFieldGroup.vue'

import GuestsSelect from '../../components/ActivityGuestsSelect.vue'

const props = defineProps({
  field: { type: Object, required: true },
  resourceName: String,
  resourceId: [String, Number],
  validationErrors: Object,
  isFloating: Boolean,
  modelValue: {
    type: Object,
    default: () => ({
      contacts: [],
      users: [],
    }),
  },
})

const emit = defineEmits(['update:modelValue', 'setInitialValue'])

const guests = ref([])
const guestsSelectRef = ref(null)
const contacts = computed(() => props.field.contacts || [])

function updateModelValue(value) {
  emit('update:modelValue', value)
}

function setInitialValue() {
  guests.value = props.field.value || []

  const initialValue = {
    contacts: [],
    users: [],
  }

  guests.value.forEach(guest => {
    if (guest.resource_name === 'users') {
      initialValue.users.push(guest.id)
    } else {
      initialValue.contacts.push(guest.id)
    }
  })

  emit('setInitialValue', initialValue)
}

setInitialValue()
</script>
