<template>
  <div class="relative">
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div v-if="!lightbox" v-bind="$attrs" v-html="visibleText" />

    <HtmlableLightbox v-else :html="visibleText" v-bind="$attrs" />

    <div v-show="hasTextToCollapse">
      <slot name="action" :collapsed="localIsCollapsed" :toggle="toggle">
        <div
          v-show="localIsCollapsed"
          class="absolute bottom-0 h-1/2 w-full cursor-pointer bg-gradient-to-t to-transparent"
          :class="gradientFromClass"
          @click="toggle"
        />

        <ILink
          v-show="!localIsCollapsed"
          class="mt-2 block"
          :text="$t('core::app.show_less')"
          @click="toggle"
        />
      </slot>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import truncate from 'truncate-html'

import HtmlableLightbox from './Lightbox/HtmlableLightbox.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  text: { type: String, required: true },
  collapsed: { type: Boolean, default: true },
  keepWhitespaces: { type: Boolean, default: false },
  length: { default: 150, type: Number },
  gradientFromClass: {
    type: String,
    default: 'from-white dark:from-neutral-900',
  },
  stripTags: Boolean,
  lightbox: Boolean,
})

const emit = defineEmits(['update:collapsed', 'hasTextToCollapse'])

const localIsCollapsed = ref(props.collapsed)

const truncatedText = computed(() =>
  truncate(props.text, props.length, {
    stripTags: props.stripTags,
    keepWhitespaces: props.keepWhitespaces,
  })
)

const hasTextToCollapse = computed(() => props.text.length >= props.length)

const visibleText = computed(() =>
  localIsCollapsed.value ? truncatedText.value : props.text
)

function toggle() {
  localIsCollapsed.value = !localIsCollapsed.value
  emit('update:collapsed', localIsCollapsed.value)
}

watch(
  () => props.collapsed,
  newVal => (localIsCollapsed.value = newVal)
)

watch(
  hasTextToCollapse,
  newVal => {
    emit('hasTextToCollapse', newVal)
  },
  { immediate: true }
)
</script>
