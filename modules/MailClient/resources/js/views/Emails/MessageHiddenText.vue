<template>
  <div v-show="text">
    <IButton
      v-i-tooltip="$t('mailclient::mail.trimmed_content')"
      class="px-3 py-0"
      small
      @click="showHiddenText = !showHiddenText"
    >
      <Icon icon="DotsHorizontal" />
    </IButton>

    <div v-show="showHiddenText" class="mt-5">
      <HtmlableLightbox :html="text" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

import HtmlableLightbox from '@/Core/components/Lightbox/HtmlableLightbox.vue'

defineProps({
  text: { type: String, required: true },
})

const showHiddenText = ref(false)
</script>
