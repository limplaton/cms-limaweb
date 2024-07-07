<template>
  <IDropdown
    :flip="flip"
    :adaptive-width="adaptiveWidth"
    @show="$emit('show')"
    @hide="$emit('hide')"
  >
    <IDropdownButton
      as="div"
      :style="{ width }"
      :class="['relative', { 'pointer-events-none': disabled }]"
      no-caret
    >
      <!-- On mobile pointer events are disabled to not open the keyboard on touch,
        in this case, the user will be able to select only from the dropdown provided values -->
      <IFormInput
        :id="inputId"
        v-bind="$attrs"
        v-model="selectedItem"
        autocomplete="off"
        :class="[
          'pointer-events-none pr-8',
          { 'sm:pointer-events-auto': !disabled },
        ]"
        :disabled="disabled"
        :placeholder="placeholder"
        @blur="inputBlur"
      />

      <IButton
        v-show="Boolean(selectedItem)"
        class="absolute right-1.5 top-1.5 sm:top-1"
        icon="XSolid"
        basic
        small
        @click.prevent="clearSelected"
      />
    </IDropdownButton>

    <IDropdownMenu
      :style="[
        {
          height: height,
          'overflow-y': maxHeight ? 'scroll' : null,
          'max-height': maxHeight || 'auto',
        },
      ]"
    >
      <IDropdownItem
        v-for="(item, index) in items"
        :key="index"
        :active="isSelected(item)"
        :text="item"
        :condensed="condensed"
        @click="itemPicked(item)"
      />
    </IDropdownMenu>
  </IDropdown>
</template>

<script setup>
import { shallowRef, watch } from 'vue'
import { useTimeoutFn } from '@vueuse/core'

import IButton from '../Button/IButton.vue'

import IFormInput from './IFormInput.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  inputId: { type: String, default: 'input-dropdown' },
  width: String,
  height: String,
  maxHeight: String,
  modelValue: String,
  placeholder: String,
  items: Array,
  disabled: Boolean,
  condensed: Boolean,
  flip: { type: [Boolean, Number], default: true },
  adaptiveWidth: { type: Boolean, default: true },
})

const emit = defineEmits([
  'update:modelValue',
  'blur',
  'cleared',
  'show',
  'hide',
])

const selectedItem = shallowRef(props.modelValue)

watch(selectedItem, newVal => {
  if (newVal !== props.modelValue) {
    emit('update:modelValue', newVal)
  }
})

watch(
  () => props.modelValue,
  newVal => {
    if (newVal !== selectedItem.value) {
      selectedItem.value = newVal
    }
  }
)

// eslint-disable-next-line no-unused-vars
function inputBlur(e) {
  // Allow timeout as if user  clicks on the dropdown item to have
  // a selected value in case @blur event is checking the value
  useTimeoutFn(() => emit('blur'), 500)
}

function itemPicked(item) {
  selectedItem.value = item
}

function isSelected(item) {
  return item === selectedItem.value
}

function clearSelected() {
  selectedItem.value = ''
  emit('cleared')
}
</script>
