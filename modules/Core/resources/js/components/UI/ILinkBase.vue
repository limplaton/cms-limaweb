<template>
  <component
    :is="as"
    :class="
      twMerge(
        plain
          ? ''
          : [
              styles.base,
              basic ? styles.basic : styles.variants[variant ?? 'primary'],
            ],
        $attrs.class
      )
    "
    v-bind="{ ...$attrs, class: '' }"
  >
    <slot></slot>
  </component>
</template>

<script>
const styles = {
  base: 'text-base/6 no-underline sm:text-sm/6 focus:outline-none',
  basic:
    'text-neutral-800 hover:text-neutral-600 dark:text-neutral-100 dark:hover:text-neutral-200',
  variants: {
    primary:
      'text-primary-600 hover:text-primary-900 dark:text-primary-300 dark:hover:text-primary-400',
    info: 'text-info-900 hover:text-info-700 dark:text-info-400 dark:hover:text-info-500',
    success:
      'text-success-900 hover:text-success-700 dark:text-success-400 dark:hover:text-success-500',
    danger:
      'text-danger-500 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-500',
    warning:
      'text-warning-700 hover:text-warning-600 dark:text-warning-400 dark:hover:text-warning-500',
  },
}
</script>

<script setup>
import { twMerge } from 'tailwind-merge'

defineOptions({ inheritAttrs: false })

defineProps({
  as: { type: [String, Object], default: 'a' },
  basic: Boolean,
  plain: Boolean,
  variant: {
    type: String,
    default: 'primary',
    validator: value => Object.keys(styles.variants).includes(value),
  },
})
</script>
