<template>
  <div>
    <UrlableLightbox v-model="activeIndex" :urls="imagesUrls" />
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div ref="htmlWrapperRef" @click="handleWrapperClickEvent" v-html="html" />
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'

import UrlableLightbox from './UrlableLightbox.vue'

const props = defineProps({ html: String })

const activeIndex = ref(null)

const htmlWrapperRef = ref(null)

const imagesUrls = ref([])

function handleWrapperClickEvent(e) {
  if (
    e.target.tagName === 'IMG' &&
    e.target.dataset !== undefined &&
    e.target.dataset.lightboxIndex >= 0 &&
    e.target.parents('a')[0]?.tagName !== 'A'
  ) {
    activeIndex.value = parseInt(e.target.dataset.lightboxIndex)
  }
}

function parseAvailableImages() {
  imagesUrls.value = []

  Array.from(htmlWrapperRef.value.getElementsByTagName('img')).forEach(img => {
    if (
      img.src &&
      imagesUrls.value.indexOf(img.src) === -1 &&
      img.parents('a')[0]?.tagName !== 'A' // no lightbox for images wrapped in links
    ) {
      imagesUrls.value.push(img.src)
      img.classList.add('cursor-pointer')
      img.classList.add('hover:opacity-90')
      img.dataset.lightboxIndex = imagesUrls.value.length - 1
    }
  })
}

watch(() => props.html, parseAvailableImages, {
  flush: 'post',
})

onMounted(parseAvailableImages)
</script>
