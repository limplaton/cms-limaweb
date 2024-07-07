<template>
  <div>
    <IAlert
      v-for="(error, index) in errors"
      :key="index"
      class="mb-4"
      variant="danger"
    >
      <IAlertBody>{{ error }}</IAlertBody>
    </IAlert>

    <slot />

    <MediaUploadOutputList
      v-if="showOutput"
      :files="files"
      @remove-requested="remove"
    />

    <div :class="[wrapperClasses, 'relative']">
      <slot name="drop-placeholder" :upload="$refs.uploadRef">
        <div
          v-show="$refs.uploadRef && $refs.uploadRef.dropActive"
          v-t="'core::app.drop_files'"
          class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-neutral-200"
        />
      </slot>

      <FileUpload
        ref="uploadRef"
        v-model="files"
        :class="[
          baseClasses,
          uploadRef && uploadRef.active ? 'pointer-events-none opacity-50' : '',
        ]"
        :headers="{
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': $csrfToken,
        }"
        :disabled="$refs.uploadRef && $refs.uploadRef.active"
        :name="name"
        :multiple="multiple"
        :extensions="extensions"
        :accept="accept"
        :data="requestData"
        :drop="drop"
        :post-action="actionUrl"
        :input-id="inputId"
        @update:model-value="$emit('update:modelValue', $event)"
        @input-file="inputFile"
        @input-filter="inputFilter"
      >
        <slot name="upload-content">
          {{ selectButtonUploadText }}
        </slot>
      </FileUpload>

      <div class="ml-2 flex items-center space-x-2">
        <slot name="upload-button" :upload="$refs.uploadRef">
          <IButton
            v-if="showUploadButton && !automaticUpload"
            variant="primary"
            icon="CloudArrowUp"
            :text="uploadButtonText"
            soft
            pill
            @click="$refs.uploadRef.active = true"
          />
        </slot>

        <IButton
          v-show="allowCancel && $refs.uploadRef && $refs.uploadRef.active"
          variant="danger"
          :text="$t('core::app.cancel')"
          soft
          pill
          @click="$refs.uploadRef.active = false"
        />

        <IButton
          v-show="
            files.length > 0 && (!$refs.uploadRef || !$refs.uploadRef.active)
          "
          :text="$t('core::app.clear')"
          basic
          pill
          @click="clear"
        />
      </div>
    </div>
  </div>
</template>

<script>
const styles = {
  base: 'inline-flex items-center rounded-full border border-neutral-300 bg-white px-4 py-1 text-base/6 font-semibold text-neutral-700 dark:border-neutral-500/30 dark:bg-neutral-500/10 dark:text-white sm:text-sm/6',
  wrapper: 'flex items-center',
}
</script>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import findIndex from 'lodash/findIndex'

import { useApp } from '@/Core/composables/useApp'

import IButton from '../UI/Button/IButton.vue'

import MediaUploadOutputList from './MediaUploadOutputList.vue'

const props = defineProps({
  modelValue: {},
  baseClasses: {
    type: [Object, Array, String],
    default: styles.base,
  },
  wrapperClasses: {
    type: [Object, Array, String],
    default: styles.wrapper,
  },
  inputId: { default: 'media', type: String },
  actionUrl: String,
  name: { default: 'file', type: String },
  extensions: [Array, String],
  accept: { type: String, default: undefined },
  uploadText: String,
  selectFileText: String,
  allowCancel: { type: Boolean, default: true },
  showUploadButton: { type: Boolean, default: true },
  showOutput: { default: true, type: Boolean },
  automaticUpload: { default: true, type: Boolean },
  multiple: { default: true, type: Boolean },
  requestData: { type: Object, default: () => ({}) },
  // NOTE, drop is set to false as it's causing memory leaks
  // https://github.com/lian-yue/vue-upload-component/issues/294
  drop: Boolean,
})

const emit = defineEmits([
  'update:modelValue',
  'fileAccepted',
  'fileUploaded',
  'clear',
])

const { t } = useI18n()
const { scriptConfig } = useApp()

const uploadRef = ref(null)
const files = ref([])
const errors = ref([])

const uploadButtonText = computed(
  () => props.uploadText || t('core::app.upload')
)

const selectButtonUploadText = computed(
  () => props.selectFileText || t('core::app.select_file')
)

function handleResponse(xhr) {
  // Nginx 413 Request Entity Too Large
  if (xhr.status === 413) {
    Innoclapps.error(t('core::app.file_too_large'))

    return
  }

  let response = JSON.parse(xhr.response)
  let isSuccess = xhr.status < 400

  if (response.message) {
    if (isSuccess) {
      Innoclapps.success(response.message)
    } else {
      Innoclapps.error(response.message)
    }
  }

  if (xhr.status === 422) {
    errors.value = response.errors
  }

  return response
}

/**
 * Remove file from the queue
 */
function remove(index) {
  files.value.splice(index, 1)
  emit('update:modelValue', files.value)
}

function clear() {
  uploadRef.value.clear()
  errors.value = []
  emit('clear')
}

function validateExtensions(file) {
  if (!props.extensions) {
    return true
  }

  let validateExtensions = props.extensions

  if (Array.isArray(validateExtensions)) {
    validateExtensions = validateExtensions.join('|')
  } else if (typeof validateExtensions == 'string') {
    validateExtensions = validateExtensions.replace(',', '|')
  }

  var regex = RegExp('.(' + validateExtensions + ')', 'i')

  if (!regex.test(file.name)) {
    Innoclapps.error(
      t('validation.mimes', {
        attribute: t('core::app.file').toLowerCase(),
        values: [validateExtensions],
      })
    )

    return false
  }

  return true
}

function isNewFile(newFile, oldFile) {
  return newFile && !oldFile
}

function isUpdatedFile(newFile, oldFile) {
  return newFile && oldFile
}

function shouldStartUpload(newFile, oldFile) {
  return newFile.active !== oldFile.active
}

/**
 * A file change detected
 */
function inputFile(newFile, oldFile) {
  if (isNewFile(newFile, oldFile)) {
    // console.log('add file')
    // Add file
  }

  if (isUpdatedFile(newFile, oldFile)) {
    // Update file
    // console.log('update file')
    // Start upload
    if (shouldStartUpload(newFile, oldFile)) {
      // console.log('Start upload', newFile.active, newFile)
    }

    // Upload progress
    if (newFile.progress !== oldFile.progress) {
      // console.log('progress', newFile.progress, newFile)
    }

    // Upload error
    if (newFile.error !== oldFile.error) {
      if (newFile.xhr.response /* perhaps canceled */) {
        handleResponse(newFile.xhr)
      }
    }

    // Uploaded successfully
    if (newFile.success !== oldFile.success) {
      // console.log('success', newFile.success, newFile)
      emit('fileUploaded', handleResponse(newFile.xhr))
      remove(findIndex(files.value, ['name', newFile.name]))
    }
  }

  if (!props.automaticUpload) {
    return
  }

  if (
    Boolean(newFile) !== Boolean(oldFile) ||
    oldFile.error !== newFile.error
  ) {
    if (!uploadRef.value.active && newFile && !newFile.xhr) {
      // console.log('Automatic upload')
      uploadRef.value.active = true
    }
  }
}

const inputFilter = function (newFile, oldFile, prevent) {
  if (newFile && !oldFile) {
    // Extentesion validator
    if (!validateExtensions(newFile)) {
      return prevent()
    }

    // File size validator
    if (newFile.size >= 0 && newFile.size > scriptConfig('max_upload_size')) {
      Innoclapps.error('File too big')

      return prevent()
    }

    newFile.blob = ''
    let URL = window.URL || window.webkitURL

    if (URL && URL.createObjectURL) {
      newFile.blob = URL.createObjectURL(newFile.file)
    }

    // this.file = newFile
    nextTick(() => emit('fileAccepted', newFile))
  }
}
</script>
