<template>
  <textarea
    :id="id"
    ref="textareaRef"
    :value="modelValue"
    :name="name"
    :tabindex="tabindex"
    :autocomplete="autocomplete"
    :autofocus="autofocus"
    :required="required"
    :placeholder="placeholder"
    :pattern="pattern"
    :wrap="wrap"
    :minlength="minlength"
    :maxlength="maxlength"
    :rows="rows"
    :cols="cols"
    :disabled="disabled"
    :class="
      twMerge(
        [
          // Base
          'block w-full rounded-lg border-0 text-base/6 text-neutral-900 shadow-sm placeholder:text-neutral-400 disabled:bg-neutral-200 dark:bg-neutral-500/10 dark:text-white dark:placeholder-neutral-400 dark:disabled:bg-neutral-700/10',

          // Ring
          'ring-1 ring-inset ring-neutral-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:ring-neutral-500/30 dark:focus:ring-primary-600',

          // Sizing
          'px-3.5 py-2.5 sm:px-3 sm:py-1.5 sm:text-sm/6',

          // Resize
          resizeable ? 'resize-none overflow-y-hidden' : '',
        ],
        $attrs.class
      )
    "
    v-bind="{ ...$attrs, class: '' }"
    @blur="blurHandler"
    @focus="focusHandler"
    @keyup="keyupHandler"
    @keydown="keydownHandler"
    @input="inputHandler"
  ></textarea>
</template>

<script setup>
import { nextTick, onMounted, ref } from 'vue'
import { useTimeoutFn } from '@vueuse/core'
import { twMerge } from 'tailwind-merge'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  rows: [String, Number],
  cols: [String, Number],
  wrap: { type: String, default: 'soft' },
  resizeable: { type: Boolean, default: true },
  // When resizeable
  minHeight: { default: 60, type: [String, Number] },
  modelValue: [String, Number],
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
  'change',
  'keyup',
  'keydown',
])

const textareaRef = ref(null)
const valueWhenFocus = ref(null)

function resizeTextarea(event) {
  event.target.style.height = 'auto'
  event.target.style.height = event.target.scrollHeight + 'px'
}

function inputHandler(e) {
  if (props.resizeable) {
    resizeTextarea(e)
  }

  emit('input', e.target.value)
  emit('update:modelValue', e.target.value)
}

function focusHandler(e) {
  emit('focus', e)

  if (props.resizeable) {
    resizeTextarea(e)
  }

  valueWhenFocus.value = e.target.value
}

function blurHandler(e) {
  emit('blur', e)

  if (e.target.value !== valueWhenFocus.value) {
    emit('change', e.target.value)
  }
}

function keyupHandler(e) {
  emit('keyup', e)
}

function keydownHandler(e) {
  emit('keydown', e)
}

function blur() {
  textareaRef.value.blur()
}

function click() {
  textareaRef.value.click()
}

function focus(options) {
  textareaRef.value.focus(options)
}

function select() {
  textareaRef.value.select()
}

function setRangeText(replacement) {
  textareaRef.value.setRangeText(replacement)
}

onMounted(() => {
  if (props.resizeable) {
    nextTick(() => {
      useTimeoutFn(() => {
        textareaRef.value?.setAttribute(
          'style',
          'height:' +
            (textareaRef.value.scrollHeight || props.minHeight) +
            'px;'
        )
      }, 400)
    })
  }
})

defineExpose({ setRangeText, select, focus, click, blur })
</script>
