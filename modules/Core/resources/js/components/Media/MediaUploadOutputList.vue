<template>
  <ul class="divide-y divide-neutral-200 dark:divide-neutral-500/30">
    <li
      v-for="(file, index) in files"
      :key="index + file.name"
      class="group flex items-center space-x-3 py-3 last:pb-0"
    >
      <div class="shrink-0">
        <span
          :class="[
            file.xhr && file.xhr.status >= 400
              ? 'bg-danger-500 text-white'
              : '',
            file.xhr && file.xhr.status < 400
              ? 'bg-success-500 text-white'
              : '',
            !file.xhr ? 'bg-neutral-600 text-neutral-100' : '',
            'inline-flex size-8 items-center justify-center rounded-full',
          ]"
        >
          <Icon
            v-if="file.xhr && file.xhr.status >= 400"
            icon="XSolid"
            class="size-5"
          />

          <Icon v-else-if="file.xhr" icon="Check" class="size-5" />

          <Icon v-else icon="CloudArrowUp" class="size-5" />
        </span>
      </div>

      <div class="min-w-0 flex-1 truncate">
        <ITextDark class="font-medium" :text="file.name" />

        <IText :text="localizedDateTime(new Date())" />
      </div>

      <div class="block shrink-0 md:hidden md:group-hover:block">
        <IButton
          v-show="!file.xhr || file.xhr.status >= 400"
          icon="XSolid"
          basic
          small
          @click="$emit('removeRequested', index)"
        />
      </div>
    </li>
  </ul>
</template>

<script setup>
import { useDates } from '@/Core/composables/useDates'

defineProps({
  files: { type: Array, required: true },
})

defineEmits(['removeRequested'])

const { localizedDateTime } = useDates()
</script>
