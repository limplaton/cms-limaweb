<template>
  <IFormGroup>
    <MediaUpload
      v-model="files"
      wrapper-classes=""
      base-classes="group block rounded-lg border border-dashed border-neutral-300 dark:border-neutral-400 w-full py-4 sm:py-5 hover:border-neutral-400 cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-700/60 font-medium text-center"
      :drop="true"
      :multiple="section.multiple"
      :show-upload-button="false"
      :automatic-upload="false"
      :name="section.requestAttribute"
      :input-id="section.requestAttribute"
    >
      <template #upload-content>
        <div class="flex flex-col items-center">
          <Icon
            icon="CloudArrowUp"
            class="size-7 text-neutral-600 dark:text-neutral-300 dark:group-hover:text-white"
          />
          <!-- eslint-disable -->
          <p
            class="mt-1 max-w-sm text-sm text-neutral-700 dark:text-neutral-100 dark:group-hover:text-white"
            v-html="section.label"
          />
          <!-- eslint-enable -->
        </div>
      </template>
    </MediaUpload>

    <IFormError :error="form.getError(section.requestAttribute)" />
  </IFormGroup>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

import MediaUpload from '@/Core/components/Media/MediaUpload.vue'

import propsDefinition from './props'

const props = defineProps(propsDefinition)

const emit = defineEmits({
  fillFormAttribute: ({ attribute, value }) => {
    if (attribute && typeof value != 'undefined') {
      return true
    } else {
      console.warn('Invalid "fillFormAttribute" event payload!')

      return false
    }
  },
})

const files = ref([])
const totalFiles = computed(() => files.value.length)

watch(totalFiles, () => {
  let eventPayload = { attribute: props.section.requestAttribute }

  if (files.value.length === 0) {
    eventPayload.value = props.section.multiple ? [] : null
  } else {
    if (props.section.multiple) {
      eventPayload.value = files.value.map(file => file.file)
    } else {
      eventPayload.value = files.value[0].file
    }
  }

  emit('fillFormAttribute', eventPayload)
})
</script>
