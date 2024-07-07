<template>
  <div class="text-center">
    <svg
      v-once
      class="mx-auto size-12 text-neutral-400"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
      aria-hidden="true"
    >
      <path
        vector-effect="non-scaling-stroke"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"
      />
    </svg>

    <ITextDark class="mt-2 font-medium">
      {{ title }}
    </ITextDark>

    <IText class="mx-auto max-w-xl">
      {{ description }}
    </IText>

    <div class="mt-6">
      <IButton
        variant="primary"
        :icon="buttonIcon"
        :text="buttonText"
        @click="handleClickEvent($event, 'click', to)"
      />

      <IButton
        v-if="secondButtonText"
        class="ml-4"
        :variant="secondButtonVariant"
        :icon="secondButtonIcon"
        :text="secondButtonText"
        @click="handleClickEvent($event, 'click2', secondButtonTo)"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

import IButton from './Button/IButton.vue'
import { IText, ITextDark } from './Text'

defineProps({
  title: String,
  description: String,
  buttonText: String,
  buttonIcon: { default: 'PlusSolid', type: String },
  to: [Object, String],
  secondButtonText: String,
  secondButtonIcon: { default: 'PlusSolid', type: String },
  secondButtonVariant: { default: 'secondary', type: String },
  secondButtonTo: [Object, String],
})

const emit = defineEmits(['click', 'click2'])

const router = useRouter()

function handleClickEvent(e, type, to) {
  if (to) {
    router.push(to)
  } else {
    emit(type, e)
  }
}
</script>
