<template>
  <ICustomSelect
    v-if="(!manualLostReason && lostReasons.length > 0) || !allowCustomLocal"
    label="name"
    :options="lostReasons"
    :input-id="manualLostReason ? `${attribute}-hidden` : attribute"
    :input-name="attribute"
    @update:model-value="
      $emit('update:modelValue', $event ? $event.name : null)
    "
  />

  <div v-show="manualLostReason">
    <IFormTextarea
      :id="!manualLostReason ? `${attribute}-hidden` : attribute"
      rows="2"
      :model-value="modelValue"
      :name="attribute"
      @update:model-value="$emit('update:modelValue', $event)"
    />
  </div>

  <div
    v-if="lostReasons.length > 0 && allowCustomLocal"
    class="mt-2 inline-flex items-center space-x-1"
  >
    <ILink
      tabindex="-1"
      :text="
        $t(
          `deals::deal.lost_reasons.${
            manualLostReason
              ? 'choose_lost_reason'
              : 'choose_lost_reason_or_enter'
          }`
        )
      "
      basic
      @click="manualLostReason = !manualLostReason"
    />

    <ILink @click="manualLostReason = !manualLostReason">
      <Icon icon="ArrowRight" class="size-4" />
    </ILink>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'

import { useApp } from '@/Core/composables/useApp'

import { useLostReasons } from '../composables/useLostReasons'

const props = defineProps({
  modelValue: String,
  allowCustom: { type: Boolean, default: undefined },
  attribute: { default: 'lost_reason', type: String },
})

defineEmits(['update:modelValue'])

const { setting } = useApp()
const manualLostReason = ref(false)

const allowCustomLocal = computed(() =>
  props.allowCustom === undefined
    ? setting('allow_lost_reason_enter')
    : props.allowCustom
)

const { lostReasonsByName: lostReasons } = useLostReasons()

if (lostReasons.value.length === 0 && allowCustomLocal.value) {
  manualLostReason.value = true
}

onMounted(() => {
  nextTick(() => {
    if (props.modelValue) {
      manualLostReason.value = true
    }
  })
})
</script>
