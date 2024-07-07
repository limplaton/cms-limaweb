<template>
  <select
    :id="id"
    ref="selectRef"
    :class="
      twMerge(
        [
          // Base
          'block w-full rounded-lg border-0 text-base/6 text-neutral-900 shadow-sm placeholder:text-neutral-400 disabled:bg-neutral-200 disabled:opacity-100 dark:bg-neutral-500/10 dark:text-white dark:placeholder-neutral-400 dark:disabled:bg-neutral-700/10',

          // Ring
          'ring-1 ring-inset ring-neutral-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:ring-neutral-500/30 dark:focus:ring-primary-600',

          // Sizing
          'py-2.5 pl-3.5 pr-[calc(theme(spacing[3.5])+24px)] sm:py-1.5 sm:pl-3 sm:pr-[calc(theme(spacing.3)+24px)] sm:text-sm/6',

          // Option
          '[&>option]:bg-white [&>option]:dark:bg-neutral-800',
        ],
        $attrs.class
      )
    "
    :name="name"
    :autofocus="autofocus"
    :placeholder="placeholder"
    :tabindex="tabindex"
    :disabled="disabled"
    :required="required"
    :multiple="multiple"
    :value="modelValue"
    v-bind="{ ...$attrs, class: '' }"
    @blur="blurHandler"
    @focus="focusHandler"
    @input="inputHandler"
    @change="changeHandler"
  >
    <slot />
  </select>
</template>

<script setup>
import { ref } from 'vue'
import { twMerge } from 'tailwind-merge'

defineOptions({ inheritAttrs: false })

defineProps({
  modelValue: {},
  placeholder: String,
  multiple: Boolean,
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
  'change',
])

const selectRef = ref(null)

function changeHandler(e) {
  emit('update:modelValue', e.target.value)
  emit('change', e.target.value)
}

function inputHandler(e) {
  emit('input', e.target.value)
}

function blurHandler(e) {
  emit('blur', e)
}

function focusHandler(e) {
  emit('focus', e)
}

function blur() {
  selectRef.value.blur()
}

function focus(options) {
  selectRef.value.focus(options)
}

defineExpose({ blur, focus })
</script>
