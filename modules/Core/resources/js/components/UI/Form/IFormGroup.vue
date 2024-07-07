<template>
  <div class="mb-3">
    <slot name="label" :label="label" :label-for="labelFor">
      <IFormLabel
        v-if="!optional && label"
        class="mb-1"
        :for="labelFor"
        :label="label"
        :required="required"
      />

      <div v-else-if="label" class="flex">
        <IFormLabel class="mb-1 grow" :for="labelFor" :label="label" />

        <span
          v-t="'core::app.optional'"
          class="mb-px self-end text-xs text-neutral-600 dark:text-neutral-200"
        />
      </div>
    </slot>

    <slot />

    <IFormText v-if="description" class="mt-1" :text="description" />
  </div>
</template>

<script setup>
import IFormLabel from './IFormLabel.vue'
import IFormText from './IFormText.vue'

defineProps({
  label: String,
  labelFor: String,
  description: String,
  optional: Boolean,
  required: Boolean,
})
</script>
