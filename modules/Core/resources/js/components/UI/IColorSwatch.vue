<template>
  <div class="inline-flex flex-wrap">
    <button
      v-for="color in localSwatches"
      :key="color"
      type="button"
      class="mb-2 mr-1 inline-flex size-8 items-center justify-center rounded"
      :style="{
        backgroundColor: color,
      }"
      @click="
        color === modelValueLowerCase ? handleRemoval() : selectColor(color)
      "
    >
      <Icon
        v-if="color === modelValueLowerCase"
        icon="Check"
        class="size-5"
        :style="{ color: getContrast(color) }"
      />
    </button>

    <div class="relative">
      <input
        ref="customColorInputRef"
        class="m-0 -ml-0.5 -mt-1 h-[40px] w-[38px] cursor-pointer appearance-none border-0 bg-white p-0 outline-none [&::-webkit-color-swatch]:rounded"
        type="color"
        :name="name"
        :model-value="isCustomColorSelected ? modelValueLowerCase : '#ffffff'"
        @input="selectColorDebounced($event.target.value)"
      />

      <a
        v-if="isCustomColorSelected"
        href="#"
        class="absolute left-1.5 top-1.5 ml-px cursor-pointer"
        @click.prevent="removeCustomColor"
      >
        <Icon
          icon="Check"
          class="size-5"
          :style="{ color: getContrast(modelValueLowerCase) }"
        />
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

import { debounce } from '@/Core/utils'
import { getContrast } from '@/Core/utils'

const props = defineProps({
  modelValue: String,
  name: String,
  swatches: Array,
  customable: { default: true, type: Boolean },
})

const emit = defineEmits(['update:modelValue', 'input', 'removeRequested'])

const customColorInputRef = ref(null)

const localSwatches = computed(() =>
  (props.swatches || []).map(color => color.toLowerCase())
)

const modelValueLowerCase = computed(() =>
  !props.modelValue ? null : props.modelValue.toLowerCase()
)

const isCustomColorSelected = computed(() => {
  if (!props.modelValue) {
    return false
  }

  return (
    localSwatches.value.filter(color => color === modelValueLowerCase.value)
      .length === 0
  )
})

const selectColorDebounced = debounce(selectColor, 500)

function selectColor(value) {
  emit('update:modelValue', value)
  emit('input', value)
}

function handleRemoval() {
  selectColor(null)
  emit('removeRequested')
}

function removeCustomColor() {
  handleRemoval()
  // Show the picker after removal.
  customColorInputRef.value.click()
}
</script>
