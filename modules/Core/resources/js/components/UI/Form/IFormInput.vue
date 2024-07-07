<template>
  <input
    :id="id"
    ref="inputRef"
    :class="
      twMerge(
        [
          // Base
          'block w-full rounded-lg border-0 text-base/6 text-neutral-900 shadow-sm placeholder:text-neutral-500/80 disabled:bg-neutral-200 dark:bg-neutral-500/10 dark:text-white dark:placeholder-neutral-300/70 dark:disabled:bg-neutral-700/10',

          // Ring
          'ring-1 ring-inset ring-neutral-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:ring-neutral-500/30 dark:focus:ring-primary-600',

          // Sizing
          'px-3.5 py-2.5 sm:px-3 sm:py-1.5 sm:text-sm/6',
        ],
        $attrs.class
      )
    "
    :value="modelValue"
    :name="name"
    :disabled="disabled"
    :autocomplete="autocomplete"
    :autofocus="autofocus"
    :type="type"
    :tabindex="tabindex"
    :required="required"
    :placeholder="placeholder"
    :pattern="pattern"
    :minlength="minlength"
    :maxlength="maxlength"
    :min="min"
    :max="max"
    v-bind="{ ...$attrs, class: '' }"
    @blur="blurHandler"
    @focus="focusHandler"
    @keyup="keyupHandler"
    @keydown="keydownHandler"
    @input="inputHandler"
  />
</template>

<script setup>
import { ref } from 'vue'
import { watch } from 'vue'
import { twMerge } from 'tailwind-merge'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  type: { type: String, default: 'text' },
  modelValue: [String, Number],
  debounce: { type: [Boolean, Number, String], default: false },
  max: { type: [String, Number], default: undefined },
  min: { type: [String, Number], default: undefined },
  autocomplete: String,
  maxlength: [String, Number],
  minlength: [String, Number],
  pattern: String,
  placeholder: String,
  id: String,
  name: String,
  disabled: Boolean,
  autofocus: Boolean,
  required: Boolean,
  tabindex: [String, Number],
})

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
  'input',
  'keyup',
  'keydown',
  'change',
])

import { debounce as debounceFn } from '@/Core/utils'

const inputRef = ref(null)
const valueWhenFocus = ref(null)

function updateValue(e) {
  const value = e.target.value

  emit('update:modelValue', value)
  emit('input', value)
}

let inputHandler

watch(
  () => props.debounce,
  newVal => {
    if (inputHandler && inputHandler.cancel) {
      inputHandler.cancel()
    }

    if (newVal !== false && newVal !== undefined) {
      inputHandler = debounceFn(updateValue, newVal)
    } else {
      inputHandler = updateValue
    }
  },
  { immediate: true }
)

function blurHandler(e) {
  emit('blur', e)

  if (e.target.value !== valueWhenFocus.value) {
    emit('change', e.target.value)
  }
}

function focusHandler(e) {
  emit('focus', e)

  valueWhenFocus.value = e.target.value
}

function keyupHandler(e) {
  emit('keyup', e)
}

function keydownHandler(e) {
  emit('keydown', e)
}

function blur() {
  inputRef.value.blur()
}

function click() {
  inputRef.value.click()
}

function focus(options) {
  inputRef.value.focus(options)
}

function select() {
  inputRef.value.select()
}

function setRangeText(replacement) {
  inputRef.value.setRangeText(replacement)
}

defineExpose({
  setRangeText,
  select,
  focus,
  click,
  blur,
  inputRef,
})
</script>
