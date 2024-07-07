<!-- eslint-disable vue/no-v-html -->
<template>
  <ITextBlock class="wysiwyg-text mb-3">
    <div v-html="section.message" />
  </ITextBlock>
</template>

<script setup>
import propsDefinition from './props'

defineProps(propsDefinition)
</script>
