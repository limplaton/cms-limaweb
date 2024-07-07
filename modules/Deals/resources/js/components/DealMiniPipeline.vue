<template>
  <nav aria-label="Progress">
    <ol
      class="divide-y divide-neutral-300 overflow-hidden rounded-md border border-neutral-300 dark:divide-neutral-500/30 dark:border-neutral-500/30 lg:flex lg:flex-wrap lg:divide-y-0"
    >
      <PipelineStage
        v-for="(stage, index) in stages"
        :key="stage.id"
        :stage-id="stage.id"
        :stage-name="stage.name"
        :total-stages="stages.length"
        :time-in-stages="timeInStages"
        :deal-stage-id="dealStageId"
        :deal-id="dealId"
        :deal-status="dealStatus"
        :deal-stage-index="dealStageIndex"
        :index="index"
        @stage-updated="$emit('stageUpdated', $event)"
      />
    </ol>
  </nav>
</template>

<script setup>
import { computed } from 'vue'

import PipelineStage from './DealMiniPipelineStage.vue'

const props = defineProps({
  stages: { type: Array, required: true },
  dealId: { type: Number, required: true },
  dealStageId: { type: Number, required: true },
  dealStatus: { type: String, required: true },
  timeInStages: { type: Object, required: true },
})

defineEmits(['stageUpdated'])

const dealStageIndex = computed(() =>
  props.stages.findIndex(stage => stage.id == props.dealStageId)
)
</script>
