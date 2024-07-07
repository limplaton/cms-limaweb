<template>
  <div>
    <MediaItemsList
      :items="localMedia"
      :authorize-delete="authorizeDelete"
      @delete-requested="$confirm(() => destroy($event))"
    />

    <IText
      v-show="!hasMedia"
      class="mt-2"
      :text="$t('core::app.no_attachments')"
    />

    <div class="mt-3">
      <MediaUpload
        :input-id="
          'media-' +
          resourceName +
          '-' +
          resourceId +
          (isFloating ? '-floating' : '')
        "
        :action-url="`${$scriptConfig(
          'apiURL'
        )}/${resourceName}/${resourceId}/media`"
        @file-uploaded="uploadedEventHandler"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import orderBy from 'lodash/orderBy'

import MediaItemsList from './MediaItemsList.vue'
import MediaUpload from './MediaUpload.vue'

const props = defineProps({
  resourceName: { type: String, required: true },
  resourceId: { type: Number, required: true },
  media: { type: Array, required: true },
  authorizeDelete: { required: true, type: Boolean },
  isFloating: { type: Boolean, required: false },
})

const emit = defineEmits(['deleted', 'uploaded'])

const localMedia = computed(() => {
  return orderBy(props.media, media => new Date(media.created_at), ['desc'])
})

const total = computed(() => {
  return props.media.length
})

const hasMedia = computed(() => total.value > 0)

function uploadedEventHandler(media) {
  emit('uploaded', media)
}

async function destroy(media) {
  await Innoclapps.request().delete(
    `/${props.resourceName}/${props.resourceId}/media/${media.id}`
  )

  emit('deleted', media)
}
</script>
