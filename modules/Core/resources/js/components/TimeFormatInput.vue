<template>
  <IFormSelect>
    <option v-for="format in formats" :key="format" :value="format">
      {{ format }} [{{ localizedTime(new Date(), format) }}]
    </option>
  </IFormSelect>
</template>

<script setup>
import { useApp } from '../composables/useApp'
import { useDates } from '../composables/useDates'

const { scriptConfig } = useApp()
const { localizedTime } = useDates()

const formats = scriptConfig('time_formats')
</script>
