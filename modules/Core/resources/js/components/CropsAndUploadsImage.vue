<template>
  <div>
    <div v-show="!edit" class="flex items-center space-x-4">
      <div v-if="hasImage">
        <slot name="image" :src="activeImageSrc">
          <IAvatar size="lg" :src="activeImageSrc" />
        </slot>
      </div>
      <!-- NOTE, drop is set to false as it's causing memory leaks -->
      <!-- https://github.com/lian-yue/vue-upload-component/issues/294 -->
      <div class="inline-flex items-center space-x-2">
        <FileUpload
          ref="uploadRef"
          v-model="tmpFile"
          extensions="jpg,jpeg,png"
          accept="image/png,image/jpeg"
          :class="[
            'inline-flex items-center rounded-full border border-neutral-300 bg-white px-4 py-1 text-base/6 font-semibold text-neutral-700 dark:border-neutral-500/30 dark:bg-neutral-500/10 dark:text-white sm:text-sm/6',
            uploadRef && uploadRef.active
              ? 'pointer-events-none opacity-50'
              : '',
          ]"
          :name="name"
          :input-id="name"
          :disabled="uploadRef && uploadRef.active"
          :headers="{
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': $csrfToken,
          }"
          :post-action="uploadUrl"
          :drop="false"
          @input-filter="inputFilter"
          @input-file="inputFile"
        >
          <ISpinner
            v-if="uploadRef && uploadRef.active"
            class="mr-1.5 inline-flex size-5 shrink-0 sm:size-4"
          />

          <span>
            {{ chooseTextLocal }}
          </span>
        </FileUpload>

        <IButton
          v-if="hasImage && showDelete"
          variant="danger"
          :text="$t('core::app.remove')"
          soft
          pill
          @click="remove"
        />
      </div>
    </div>

    <div v-show="hasTemporaryFile && edit">
      <div class="flex space-x-2">
        <IButton
          type="submit"
          variant="primary"
          :text="saveTextLocal"
          @click="editSave"
        />

        <IButton
          :text="cancelTextLocal"
          basic
          @click="() => uploadRef.clear()"
        />
      </div>

      <div
        v-if="hasTemporaryFile"
        class="mt-2 w-full overflow-hidden rounded-lg"
      >
        <img ref="editedFileRef" :src="tmpFile[0].url" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Cropper from 'cropperjs'

import 'cropperjs/dist/cropper.css'

const props = defineProps({
  showDelete: { type: Boolean, default: true },
  chooseText: String,
  saveText: String,
  cancelText: String,
  image: String,
  uploadUrl: { type: String, required: true },
  name: { type: String, default: 'image' },
  cropperOptions: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['success', 'cleared'])

const { t } = useI18n()

let cropper = null

const defaultCropperOptions = {
  aspectRatio: 1 / 1,
  viewMode: 1,
}

const edit = ref(false)
const tmpFile = ref([])

const uploadRef = ref(null)
const editedFileRef = ref(null)

watch(edit, newVal => {
  if (newVal) {
    nextTick(function () {
      if (!editedFileRef.value) {
        return
      }

      cropper = new Cropper(
        editedFileRef.value,
        Object.assign({}, defaultCropperOptions, props.cropperOptions)
      )
    })
  } else if (cropper) {
    cropper.destroy()
    cropper = null
  }
})

const chooseTextLocal = computed(
  () => props.chooseText || t('core::app.choose_image')
)

const saveTextLocal = computed(() => props.saveText || t('core::app.upload'))

const cancelTextLocal = computed(
  () => props.cancelText || t('core::app.cancel')
)

const hasImage = computed(() => hasTemporaryFile.value || props.image)

const activeImageSrc = computed(() =>
  hasTemporaryFile.value ? tmpFile.value[0].url : props.image
)

const hasTemporaryFile = computed(() => tmpFile.value.length > 0)

function remove() {
  edit.value = false
  tmpFile.value = []
  emit('cleared')
}

function editSave() {
  edit.value = false
  let oldFile = tmpFile.value[0]

  let binStr = atob(
    cropper.getCroppedCanvas().toDataURL(oldFile.type).split(',')[1]
  )

  let arr = new Uint8Array(binStr.length)

  for (let i = 0; i < binStr.length; i++) {
    arr[i] = binStr.charCodeAt(i)
  }

  let file = new File([arr], oldFile.name, {
    type: oldFile.type,
  })

  uploadRef.value.update(oldFile.id, {
    file,
    type: file.type,
    size: file.size,
    active: true,
  })
}

// eslint-disable-next-line no-unused-vars
function inputFile(newFile, oldFile, prevent) {
  if (newFile && !oldFile) {
    nextTick(function () {
      edit.value = true
    })
  }

  if (!newFile && oldFile) {
    edit.value = false
  }

  if (newFile && oldFile) {
    // Uploaded
    if (newFile.success !== oldFile.success) {
      emit('success', newFile.response)
    }

    // Error
    if (newFile.error !== oldFile.error) {
      // Nginx 413 Request Entity Too Large
      if (newFile.xhr.status === 413) {
        Innoclapps.error(t('core::app.file_too_large'))
        tmpFile.value = []

        return
      }

      let response = JSON.parse(newFile.xhr.response)
      Innoclapps.error(response.message)
    }
  }
}

function inputFilter(newFile, oldFile, prevent) {
  if (newFile && !oldFile) {
    if (!/\.(jpeg|png|jpg|gif|svg)$/i.test(newFile.name)) {
      Innoclapps.error(
        t('validation.image', {
          attribute: newFile.name,
        })
      )

      return prevent()
    }
  }

  if (newFile && (!oldFile || newFile.file !== oldFile.file)) {
    newFile.url = ''
    let URL = window.URL || window.webkitURL

    if (URL && URL.createObjectURL) {
      newFile.url = URL.createObjectURL(newFile.file)
    }
  }
}

onBeforeUnmount(() => {
  cropper && cropper.destroy()
})
</script>
