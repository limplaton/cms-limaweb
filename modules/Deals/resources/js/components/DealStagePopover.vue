<template>
  <IPopover
    v-if="authorizedToUpdate && status === 'open'"
    v-slot="{ hide }"
    @show="handleStagePopoverShowEvent"
  >
    <IPopoverButton
      as="button"
      class="flex flex-wrap items-center justify-center text-base/6 font-medium focus:outline-none sm:text-sm/6 md:flex-nowrap md:justify-start"
    >
      <span class="w-auto max-w-[15rem] truncate" v-text="dealPipeline.name" />

      <Icon icon="ChevronRightSolid" class="size-4" />

      <span
        class="w-auto max-w-[15rem] truncate text-left"
        v-text="dealStage.name"
      />

      <Icon
        icon="ChevronDownSolid"
        class="ml-2 hidden size-4 shrink-0 md:block"
      />
    </IPopoverButton>

    <IPopoverPanel class="w-80">
      <IPopoverBody>
        <ICustomSelect
          v-model="selectPipeline"
          class="mb-2"
          label="name"
          :options="pipelines"
          :clearable="false"
          @option-selected="handlePipelineChangedEvent"
          @update:model-value="
            form.errors.clear('pipeline_id'), form.errors.clear('stage_id')
          "
        />

        <IFormError :error="form.getError('pipeline_id')" />

        <ICustomSelect
          v-model="selectPipelineStage"
          label="name"
          :options="selectPipeline ? selectPipeline.stages : []"
          :clearable="false"
          @update:model-value="form.errors.clear('stage_id')"
        />

        <IFormError :error="form.getError('stage_id')" />
      </IPopoverBody>

      <IPopoverFooter class="flex justify-end space-x-2">
        <IButton
          :disabled="form.busy"
          :text="$t('core::app.cancel')"
          basic
          @click="hide"
        />

        <IButton
          variant="primary"
          :text="$t('core::app.save')"
          :loading="form.busy"
          :disabled="form.busy || !selectPipelineStage"
          @click="saveStageChange().then(hide)"
        />
      </IPopoverFooter>
    </IPopoverPanel>
  </IPopover>

  <div
    v-else
    class="flex flex-wrap items-center justify-center text-base/6 font-medium sm:justify-center sm:text-sm/6"
  >
    <span class="max-w-[15rem] truncate" v-text="dealPipeline.name" />

    <Icon icon="ChevronRightSolid" class="size-4" />

    <span class="max-w-[15rem] truncate" v-text="dealStage.name" />
  </div>
</template>

<script setup>
import { computed, shallowRef } from 'vue'

import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'

import { usePipelines } from '../composables/usePipelines'

const props = defineProps({
  dealId: { required: true, type: Number },
  pipeline: { required: true, type: Object }, // use directly from deal in case the pipeline is hidden from the current user
  stageId: { required: true, type: Number },
  status: { required: true, type: String },
  authorizedToUpdate: { required: true, type: Boolean },
})

const emit = defineEmits(['updated'])

const { orderedPipelines: pipelines } = usePipelines()
const { form } = useForm()
const { updateResource } = useResourceable(Innoclapps.resourceName('deals'))

const dealPipeline = computed(() => props.pipeline)

const dealStage = computed(
  () => props.pipeline.stages.filter(stage => stage.id == props.stageId)[0]
)

const selectPipeline = shallowRef(null)
const selectPipelineStage = shallowRef(null)

async function saveStageChange() {
  let updatedDeal = await updateResource(
    form.fill({
      pipeline_id: selectPipeline.value.id,
      stage_id: selectPipelineStage.value.id,
    }),
    props.dealId
  )

  emit('updated', updatedDeal)
}

function handleStagePopoverShowEvent() {
  selectPipeline.value = dealPipeline.value
  selectPipelineStage.value = dealStage.value
}

function handlePipelineChangedEvent(value) {
  if (value.id != props.pipeline.id) {
    // Use the first stage selected from the new pipeline
    selectPipelineStage.value = value.stages[0] || null
  } else if (value.id === props.pipeline.id) {
    // revent back to the original stage after the user select new stage
    // and goes back to the original without saving
    selectPipelineStage.value = dealStage.value
  }
}
</script>
