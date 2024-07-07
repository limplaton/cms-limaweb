<!-- eslint-disable vue/no-v-html -->
<template>
  <div v-if="showIntroductionSection" class="mb-3">
    <ITextDisplay :text="section.title" />

    <ITextBlock class="wysiwyg-text">
      <div v-html="section.message" />
    </ITextBlock>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import propsDefinition from './props'

const props = defineProps(propsDefinition)

const showIntroductionSection = computed(
  () => props.section.title || props.section.message
)
</script>
