<template>
  <div
    class="flex h-14 w-full flex-col justify-center rounded-lg p-2 md:h-32 md:p-4"
    :style="{ background: swatch.hex }"
  >
    <div
      class="flex items-center justify-between px-4 md:mt-auto md:block md:px-0"
      :style="{ color: getContrast(swatch.hex) }"
    >
      <div class="text-center text-sm font-medium">
        {{ swatch.stop }}
      </div>

      <div class="text-center text-xs uppercase opacity-90">
        <label class="cursor-pointer" :for="swatch.hex">
          {{ swatch.hex }}
        </label>

        <input
          :id="swatch.hex"
          type="color"
          class="h-0 [&::-moz-color-swatch]:border-0 [&::-webkit-color-swatch-wrapper]:p-0 [&::-webkit-color-swatch]:border-0"
          :value="swatch.hex"
          @input="handleSwatchColorInput"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { debounce, getContrast } from '@/Core/utils'

defineProps(['swatch', 'color'])

const emit = defineEmits(['update:hex'])

const handleSwatchColorInput = debounce(
  e => emit('update:hex', e.target.value),
  300
)
</script>
