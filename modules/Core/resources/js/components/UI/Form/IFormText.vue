<template>
  <IText>
    <slot>{{ text }}</slot>
  </IText>
</template>

<script setup>
import { IText } from '../Text'

defineProps({ text: [String, Number] })
</script>
