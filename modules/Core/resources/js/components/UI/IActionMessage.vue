<template>
  <Transition
    leave-active-class="transition ease-in duration-1000"
    leave-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <IText v-show="show">
      <slot>
        {{ message }}
      </slot>
    </IText>
  </Transition>
</template>

<script setup>
import { IText } from './Text'

defineProps({ message: String, show: Boolean })
</script>
