<template>
  <!--  When the key is array the value will be empty array instead of key e.q. lang.key -->
  <div v-if="!Array.isArray(modelValue)">
    <IFormTextarea v-model="model" rows="3" />

    <div v-if="isMissingSourceParameters" class="text-danger-500">
      <p class="mt-0.5">
        The parameters starting with ":" must not be translated or modified.
      </p>

      <p class="mt-1">
        Modified parameters: {{ missingParameters.join(', ') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import difference from 'lodash/difference'

const props = defineProps(['source'])

const model = defineModel()

// Matches: ":attribute" ":OTHER" ":value" ":AnotherExample"
const parametersRegex = /:(?:[a-z]+|[A-Z]+|[A-Z][a-zA-Z]*)\b/g

const missingParameters = computed(() => {
  let originalParameters = props.source.match(parametersRegex)

  let currentParameters = props.modelValue.match(
    parametersRegex,
    props.modelValue
  )

  return difference(originalParameters, currentParameters)
})

const isMissingSourceParameters = computed(
  () => missingParameters.value.length > 0
)
</script>
