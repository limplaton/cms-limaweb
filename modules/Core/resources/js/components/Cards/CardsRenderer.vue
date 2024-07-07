<template>
  <div
    class="flex flex-wrap space-x-0 space-y-4 lg:flex-nowrap lg:space-x-4 lg:space-y-0"
  >
    <template v-if="!componentReady">
      <div v-for="p in totalPlaceholders" :key="p" class="w-full lg:w-1/2">
        <CardPlaceholder pulse />
      </div>
    </template>

    <div
      v-for="card in cards"
      :key="card.uriKey"
      :class="card.width === 'half' ? 'w-full lg:w-1/2' : 'w-full'"
    >
      <component :is="card.component" :card="card" />
    </div>
  </div>
</template>

<script setup>
import { ref, shallowRef } from 'vue'

import CardPlaceholder from './CardPlaceholder.vue'

const props = defineProps({
  resourceName: { required: true, type: String },
  totalPlaceholders: { default: 2, type: Number },
})

const cards = shallowRef([])
const componentReady = ref(false)

/**
 * Fetch the resource cards
 */
async function fetch() {
  let { data } = await Innoclapps.request(`/${props.resourceName}/cards`)

  cards.value = data
  componentReady.value = true
}

fetch()
</script>
