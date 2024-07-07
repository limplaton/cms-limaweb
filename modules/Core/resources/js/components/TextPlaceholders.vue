<template>
  <div
    class="rounded-md border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-500 dark:bg-neutral-700"
  >
    <div class="max-h-96 overflow-y-auto">
      <ITextDark
        v-for="placeholder in placeholders"
        :key="placeholder.tag"
        class="mb-1"
      >
        <span class="select-all font-semibold">
          {{ placeholder.interpolation_start }}
          {{ placeholder.tag }}
          {{ placeholder.interpolation_end }}
        </span>

        <span v-show="placeholder.description">
          - {{ placeholder.description }}
        </span>
      </ITextDark>
    </div>
  </div>
</template>

<script setup>
defineProps({
  placeholders: Array,
})
</script>
