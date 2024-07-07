<template>
  <UrlableLightbox
    v-model="activeLightboxImageIndex"
    :urls="imagesUrlsForLightbox"
  >
    <template #toolbar>
      <a
        v-for="type in ['preview_url', 'download_url']"
        :key="type"
        tabindex="-1"
        rel="noopener noreferrer"
        class="toolbar-btn"
        :href="activeLightboxMedia[type]"
        :target="type === 'preview_url' ? '_blank' : undefined"
      >
        <Icon
          class="size-5"
          :icon="type == 'preview_url' ? 'Eye' : 'DownloadSolid'"
        />
      </a>
    </template>
  </UrlableLightbox>

  <ul
    class="divide-y divide-neutral-200 dark:divide-neutral-500/30"
    v-bind="$attrs"
  >
    <li
      v-for="media in items"
      :key="media.id"
      class="group flex items-center space-x-3 py-3 last:pb-0"
    >
      <div class="shrink-0">
        <span
          class="inline-flex size-8 items-center justify-center rounded-full text-base/6 sm:text-sm/6"
          :class="[
            media.was_recently_created
              ? 'bg-success-500 text-white'
              : 'bg-neutral-200 text-neutral-400 dark:bg-neutral-700 dark:text-neutral-300',
          ]"
        >
          <Icon v-if="media.was_recently_created" icon="Check" class="size-5" />

          <span v-else v-text="media.extension"></span>
        </span>
      </div>

      <div class="min-w-0 flex-1 truncate">
        <ILink
          v-if="media.aggregate_type !== 'image'"
          class="font-medium"
          tabindex="0"
          :href="media.view_url"
          :text="media.file_name"
          basic
        />

        <ILink
          v-else
          class="font-medium"
          tabindex="0"
          :href="media.view_path"
          :text="media.file_name"
          basic
          @click.prevent="
            activeLightboxImageIndex = findIndexForLightbox(media.preview_url)
          "
        />

        <IText class="ml-2 inline" :text="formatBytes(media.size)" />

        <IText :text="localizedDateTime(media.created_at)" />
      </div>

      <div class="block shrink-0 md:hidden md:group-hover:block">
        <div class="flex items-center sm:space-x-2">
          <IButton :href="media.download_path" basic small download>
            <Icon icon="DownloadSolid" />
          </IButton>

          <IButton
            v-if="authorizeDelete && media.via_text_attribute !== true"
            icon="XSolid"
            basic
            small
            @click="$emit('deleteRequested', media)"
          />
        </div>
      </div>
    </li>
  </ul>
</template>

<script setup>
import { computed, ref } from 'vue'

import { useDates } from '@/Core/composables/useDates'
import { formatBytes } from '@/Core/utils'

import UrlableLightbox from '../Lightbox/UrlableLightbox.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  items: Array,
  authorizeDelete: Boolean,
})

defineEmits(['deleteRequested'])

const activeLightboxImageIndex = ref(null)

const { localizedDateTime } = useDates()

const mediaImages = computed(() =>
  props.items.filter(media => media.aggregate_type === 'image')
)

const imagesUrlsForLightbox = computed(() =>
  mediaImages.value.map(media => media.preview_url)
)

const activeLightboxMedia = computed(() => {
  return mediaImages.value[activeLightboxImageIndex.value]
})

function findIndexForLightbox(previewUrl) {
  return mediaImages.value.findIndex(media => media.preview_url === previewUrl)
}
</script>
