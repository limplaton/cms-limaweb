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
          :icon="type === 'preview_url' ? 'Eye' : 'DownloadSolid'"
        />
      </a>
    </template>
  </UrlableLightbox>

  <ITextBlockDark as="dd">
    <ul
      role="list"
      class="divide-y divide-neutral-100 rounded-lg border border-neutral-200 bg-white dark:divide-neutral-800 dark:border-neutral-800 dark:bg-neutral-900"
    >
      <li
        v-for="media in attachments"
        :key="media.id"
        class="flex items-center justify-between py-4 pl-4 pr-5"
      >
        <div class="flex w-0 flex-1 items-center">
          <Icon icon="PaperClip" class="size-5 shrink-0 text-neutral-400" />

          <div class="ml-4 flex min-w-0 flex-1 gap-2">
            <span class="truncate font-medium">
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
                  activeLightboxImageIndex = findIndexForLightbox(
                    media.preview_url
                  )
                "
              />
            </span>

            <span
              class="shrink-0 text-neutral-400"
              v-text="formatBytes(media.size)"
            />
          </div>
        </div>

        <div class="ml-4 shrink-0">
          <ILink
            class="font-medium"
            :href="media.download_url"
            :text="$t('core::app.download')"
            download
          />
        </div>
      </li>
    </ul>
  </ITextBlockDark>
</template>

<script setup>
import { computed, ref } from 'vue'

import UrlableLightbox from '@/Core/components/Lightbox/UrlableLightbox.vue'
import { formatBytes } from '@/Core/utils'

const props = defineProps({
  attachments: { required: true, type: Array },
})

const activeLightboxImageIndex = ref(null)

const mediaImages = computed(() =>
  props.attachments.filter(media => media.aggregate_type === 'image')
)

const imagesUrlsForLightbox = computed(() =>
  mediaImages.value.map(media => media.preview_url)
)

const activeLightboxMedia = computed(() => {
  return mediaImages.value[activeLightboxImageIndex.value]
})

function findIndexForLightbox(previewUrl) {
  return props.attachments.findIndex(media => media.preview_url === previewUrl)
}
</script>
