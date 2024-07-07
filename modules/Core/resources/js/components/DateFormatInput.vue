<template>
  <IFormSelect>
    <option v-for="format in formats" :key="format" :value="format">
      {{ format }} [{{ localizedDate(new Date(), format) }}]
    </option>
  </IFormSelect>
</template>

<script setup>
import { useApp } from '../composables/useApp'
import { useDates } from '../composables/useDates'

const { localizedDate } = useDates()
const { scriptConfig } = useApp()

const formats = scriptConfig('date_formats')
</script>
