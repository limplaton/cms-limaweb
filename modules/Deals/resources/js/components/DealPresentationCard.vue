<template>
  <PresentationChart :card="card" :request-query-string="requestQueryString">
    <template #actions>
      <IDropdown>
        <IDropdownButton class="md:mr-3" basic>
          <span
            class="block max-w-32 truncate"
            v-text="selectedPipeline.name"
          />
        </IDropdownButton>

        <IDropdownMenu>
          <IDropdownItem
            v-for="pipeline in pipelines"
            :key="pipeline.id"
            :text="pipeline.name"
            :active="selectedPipeline.id === pipeline.id"
            @click="selectedPipeline = pipeline"
          />
        </IDropdownMenu>
      </IDropdown>
    </template>
  </PresentationChart>
</template>

<script setup>
import { computed, shallowRef } from 'vue'

import { usePipelines } from '../composables/usePipelines'

const props = defineProps({
  card: Object,
})

const { orderedPipelines: pipelines, findPipelineById } = usePipelines()
const selectedPipeline = shallowRef(findPipelineById(props.card.pipeline_id)) // active selected pipeline

const requestQueryString = computed(() => ({
  pipeline_id: selectedPipeline.value.id,
}))
</script>
