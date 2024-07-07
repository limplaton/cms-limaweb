<template>
  <div class="mb-1 mt-3">
    <ITextDisplay :class="[field.titleIcon ? 'inline-flex items-center' : '']">
      <Icon
        v-if="field.titleIcon"
        class="mr-1.5 size-4"
        :icon="field.titleIcon"
      />

      <span v-text="field.title"></span>
    </ITextDisplay>
    <!-- eslint-disable -->
    <IText
      v-html="field.message"
    />
    <!-- eslint-enable -->
  </div>
</template>

<script setup>
defineOptions({ inheritAttrs: false })

defineProps({
  field: { type: Object, require: true },
})
</script>
