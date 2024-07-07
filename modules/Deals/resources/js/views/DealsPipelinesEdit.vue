<template>
  <ICardHeader>
    <ICardHeading :text="$t('deals::deal.pipeline.edit')" />
  </ICardHeader>

  <ICard as="form" :overlay="!componentReady" @submit.prevent="update">
    <ICardBody>
      <IFormGroup
        label-for="name"
        :label="$t('deals::deal.pipeline.name')"
        required
      >
        <IFormInput id="name" v-model="form.name" name="name" type="text" />

        <IFormError :error="form.getError('name')" />
      </IFormGroup>

      <IFormGroup class="mt-4">
        <VisibilityGroupSelector
          v-model:type="form.visibility_group.type"
          v-model:dependsOn="form.visibility_group.depends_on"
          :disabled="pipeline.is_primary"
        />
      </IFormGroup>

      <IAlert class="mt-4" :show="componentReady && pipeline.is_primary">
        <IAlertBody>
          {{ $t('deals::deal.pipeline.visibility_group.primary_restrictions') }}
        </IAlertBody>
      </IAlert>
    </ICardBody>

    <div class="px-6">
      <ITable class="[--gutter:theme(spacing.6)]" bleed>
        <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
          <ITableRow>
            <ITableHeader>
              {{ $t('deals::deal.stage.name') }}
            </ITableHeader>

            <ITableHeader>
              {{ $t('deals::deal.stage.win_probability') }}
            </ITableHeader>
          </ITableRow>
        </ITableHead>

        <SortableDraggable
          v-model="form.stages"
          tag="tbody"
          v-bind="$draggable.common"
          handle=".draggable-handle"
          :item-key="(item, index) => index"
        >
          <template #item="{ element, index }">
            <ITableRow>
              <ITableCell class="w-full sm:w-auto">
                <div class="flex space-x-2">
                  <div class="relative flex grow">
                    <div
                      class="absolute inset-y-0 left-0 flex items-center pl-3"
                    >
                      <Icon
                        icon="Selector"
                        class="draggable-handle size-5 cursor-move text-neutral-500"
                      />
                    </div>

                    <div
                      v-if="element.id"
                      class="absolute inset-y-0 left-11 hidden w-14 truncate px-1 sm:flex sm:items-center sm:justify-center"
                    >
                      ID: {{ element.id }}
                    </div>

                    <IFormInput
                      ref="stageNameInputRef"
                      v-model="form.stages[index].name"
                      :class="element.id ? 'pl-10 sm:pl-28' : '!pl-10'"
                      @keydown.enter.prevent="newStage"
                    />
                  </div>

                  <IButton icon="Trash" basic @click="deleteStage(index)" />
                </div>

                <IFormError
                  :error="form.getError('stages.' + index + '.name')"
                />
              </ITableCell>

              <ITableCell>
                <div class="flex items-center">
                  <div class="mr-4 grow">
                    <input
                      v-model="form.stages[index].win_probability"
                      type="range"
                      class="h-2 w-full appearance-none rounded-lg border border-neutral-200 bg-neutral-200 focus:bg-neutral-300 focus:outline-none dark:border-neutral-500 dark:bg-neutral-700 dark:focus:bg-neutral-800"
                      :min="1"
                      :max="100"
                    />
                  </div>

                  <div>
                    {{ form.stages[index].win_probability }}
                  </div>
                </div>

                <IFormError
                  :error="form.getError('stages.' + index + '.win_probability')"
                />
              </ITableCell>
            </ITableRow>
          </template>
        </SortableDraggable>

        <tfoot>
          <ITableRow>
            <ITableCell colspan="2" class="px-5 py-3">
              <IButton
                variant="primary"
                :text="$t('deals::deal.stage.add')"
                soft
                @click="newStage"
              />
            </ITableCell>
          </ITableRow>
        </tfoot>
      </ITable>
    </div>

    <ICardFooter class="text-right">
      <IButton
        type="submit"
        variant="primary"
        :disabled="form.busy"
        :text="$t('core::app.save')"
        @click="update"
      />
    </ICardFooter>
  </ICard>
</template>

<script setup>
import { nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import map from 'lodash/map'

import VisibilityGroupSelector from '@/Core/components/VisibilityGroupSelector.vue'
import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'

import { usePipelines } from '../composables/usePipelines'

const { t } = useI18n()
const route = useRoute()
const { resetStoreState } = useApp()
const { setPipeline, fetchPipeline } = usePipelines()
const { updateResource } = useResourceable('pipelines')

const stageNameInputRef = ref(null)

const pipeline = ref({})
const componentReady = ref(false)

const { form } = useForm({
  name: null,
  stages: [],
  visibility_group: {
    type: 'all',
    depends_on: [],
  },
})

async function update() {
  form.stages = map(form.stages, (stage, index) => {
    stage.display_order = index

    return stage
  })

  const pipeline = await updateResource(form, route.params.id, {
    params: {
      with: 'visibilityGroup.users;visibilityGroup.teams',
    },
  })

  setPipeline(pipeline.id, pipeline)
  resetStoreState()
  // Update the stages in case new stages are created so we can have the ID's
  form.stages = pipeline.stages

  Innoclapps.success(t('deals::deal.pipeline.updated'))
}

function newStage() {
  form.stages.push({
    name: '',
    win_probability: 100,
  })

  nextTick(() => {
    stageNameInputRef.value.focus()
  })
}

function removeStageFromForm(index) {
  form.stages.splice(index, 1)
}

async function deleteStage(index) {
  let stageId = form.stages[index].id

  // Form not yet saved, e.q. user added new stage then want to
  // delete before saving the form
  if (!stageId) {
    removeStageFromForm(index)

    return
  }

  await Innoclapps.confirm()
  await Innoclapps.request().delete(`/pipeline-stages/${stageId}`)

  resetStoreState()
  removeStageFromForm(index)
}

async function prepareComponent() {
  try {
    const response = await fetchPipeline(route.params.id, {
      params: {
        with: 'visibilityGroup.users;visibilityGroup.teams',
      },
    })

    pipeline.value = response

    form.fill('name', response.name)
    form.fill('stages', response.stages)

    if (response.visibility_group) {
      form.fill('visibility_group', response.visibility_group)
    }

    if (form.stages.length === 0) {
      newStage()
    }
  } finally {
    componentReady.value = true
  }
}

prepareComponent()
</script>
