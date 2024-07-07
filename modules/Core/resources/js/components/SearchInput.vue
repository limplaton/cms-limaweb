<template>
  <div
    class="relative rounded-lg text-neutral-400 focus-within:text-neutral-700 dark:focus-within:text-neutral-200"
  >
    <div
      class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
    >
      <Icon icon="MagnifyingGlassSolid" class="size-5" />
    </div>

    <IFormInput
      v-bind="$attrs"
      v-model="model"
      class="!pl-11"
      type="search"
      :placeholder="placeholder || $t('core::app.search')"
      debounce
      @click="$emit('click', $event)"
    />
  </div>
</template>

<script setup>
defineOptions({ inheritAttrs: false })

defineProps({
  placeholder: String,
})

defineEmits(['click'])

const model = defineModel()
</script>
