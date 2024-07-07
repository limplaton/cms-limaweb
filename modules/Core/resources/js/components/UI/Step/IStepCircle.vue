<template>
  <li
    :class="[
      !isLast
        ? {
            'pr-8 sm:pr-20': spacing === 'md',
            'pr-10 sm:pr-36': spacing === 'lg',
            'pr-10 sm:pr-44': spacing === 'xl',
          }
        : '',
      'relative',
    ]"
  >
    <template v-if="status === 'complete'">
      <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="h-0.5 w-full bg-primary-600" />
      </div>

      <a
        href="#"
        class="relative flex size-8 items-center justify-center rounded-full bg-primary-600 hover:bg-primary-900"
        @click.prevent="$emit('click', $event)"
      >
        <Icon icon="Check" class="size-5 text-white" />

        <span class="mt-20 shrink-0 text-sm" v-text="name" />
      </a>
    </template>

    <template v-else-if="status === 'current'">
      <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="h-0.5 w-full bg-neutral-200 dark:bg-neutral-700" />
      </div>

      <a
        href="#"
        class="relative flex size-8 items-center justify-center rounded-full border-2 border-primary-600 bg-neutral-50 dark:bg-neutral-800"
        aria-current="step"
        @click.prevent="$emit('click', $event)"
      >
        <span
          class="h-2.5 w-2.5 rounded-full bg-primary-600"
          aria-hidden="true"
        />

        <span class="mt-20 shrink-0 text-sm font-semibold" v-text="name" />
      </a>
    </template>

    <template v-else>
      <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="h-0.5 w-full bg-neutral-200 dark:bg-neutral-700" />
      </div>

      <a
        href="#"
        class="group relative flex size-8 items-center justify-center rounded-full border-2 border-neutral-300 bg-neutral-50 hover:border-neutral-400 dark:border-neutral-600 dark:bg-neutral-800"
        @click.prevent="$emit('click', $event)"
      >
        <span
          class="h-2.5 w-2.5 rounded-full bg-transparent group-hover:bg-neutral-300"
          aria-hidden="true"
        />

        <span class="mt-20 shrink-0 text-sm" v-text="name" />
      </a>
    </template>
  </li>
</template>

<script setup>
defineProps({
  name: String,
  isLast: Boolean,
  spacing: {
    type: String,
    default: 'md',
    validator(value) {
      return ['md', 'lg', 'xl'].includes(value)
    },
  },
  status: {
    type: String,
    validator(value) {
      return ['complete', 'current', ''].includes(value)
    },
  },
})
defineEmits(['click'])
</script>
