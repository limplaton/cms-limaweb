<template>
  <ILinkBase
    v-if="href"
    v-bind="$attrs"
    :href="href"
    :class="classes"
    :data-active="active || undefined"
    plain
  >
    <Icon v-if="icon" :icon="icon" />

    <slot :text="text">
      {{ text }}
    </slot>
  </ILinkBase>

  <button
    v-else
    ref="elementRef"
    type="button"
    :data-active="active || undefined"
    :disabled="disabled || undefined"
    :tabindex="disabled ? '-1' : undefined"
    :class="classes"
    v-bind="$attrs"
    @click="handleClickEvent"
  >
    <Icon v-if="icon" v-show="!loading && !isBeingConfirmed" :icon="icon" />

    <ISpinner v-if="loading" />

    <slot :text="text" :is-being-confirmed="isBeingConfirmed">
      {{ !isBeingConfirmed ? text : confirmText }}
    </slot>
  </button>
</template>

<script>
const variants = [
  'primary',
  'secondary',
  'success',
  'info',
  'warning',
  'danger',
]
</script>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import ILinkBase from '../ILinkBase.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  text: [String, Number],
  icon: String,
  to: [Object, String],
  active: Boolean,
  soft: Boolean,
  ghost: Boolean,
  pill: Boolean,
  basic: Boolean,
  disabled: Boolean,
  loading: Boolean,
  small: Boolean,
  block: Boolean,
  href: String,
  confirmable: Boolean,
  confirmText: { type: [String, Number], default: 'Confirm' },
  confirmVariant: { type: String, default: 'danger' },
  variant: {
    type: String,
    default: 'secondary',
    validator: value => variants.includes(value),
  },
})

const emit = defineEmits(['click', 'confirmed'])

const router = useRouter()

const elementRef = ref(null)
const isBeingConfirmed = ref(false)

const currentVariant = computed(() =>
  !isBeingConfirmed.value
    ? !props.basic
      ? props.variant
      : null
    : props.confirmVariant || props.variant
)

const classes = computed(() => [
  'btn',
  {
    'btn-primary': currentVariant.value === 'primary',
    'btn-secondary': currentVariant.value === 'secondary',
    'btn-success': currentVariant.value === 'success',
    'btn-info': currentVariant.value === 'info',
    'btn-warning': currentVariant.value === 'warning',
    'btn-danger': currentVariant.value === 'danger',
    'btn-soft': props.soft,
    'btn-ghost': props.ghost,
    'btn-pill': props.pill,
    'btn-basic': props.basic && !isBeingConfirmed.value,
    'btn-sm': props.small,
    'w-full': props.block,
  },
])

function requiresConfirmation(e) {
  if (!props.confirmable) {
    return
  }

  if (isBeingConfirmed.value === false) {
    elementRef.value.style.minWidth = elementRef.value.offsetWidth + 'px'
    isBeingConfirmed.value = true

    elementRef.value.focus()
    e.preventDefault()
    e.stopPropagation()

    return true
  }

  emit('confirmed', e)
  isBeingConfirmed.value = false
  elementRef.value.style.minWidth = null
}

function handleClickEvent(e) {
  // Early return if the button is disabled
  if (props.disabled) {
    return
  }

  // Handle confirmable buttons.
  if (requiresConfirmation(e)) {
    return
  }

  if (props.to) {
    router.push(props.to)
  } else {
    emit('click', e)
  }
}

function focus() {
  elementRef.value.focus()
}

defineExpose({ focus })
</script>
