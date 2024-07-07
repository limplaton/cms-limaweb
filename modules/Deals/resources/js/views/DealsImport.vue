<template>
  <MainLayout>
    <ResourceImport
      :resource-name="resourceName"
      :request-query-string="{ pipeline_id: pipeline.id }"
    >
      <template #header>
        <ICardHeading class="flex items-center">
          <I18nT scope="global" keypath="deals::deal.import_in">
            <template #pipeline>
              <div class="ml-3 w-56">
                <ICustomSelect
                  v-model="pipeline"
                  label="name"
                  :clearable="false"
                  :options="pipelines"
                />
              </div>
            </template>
          </I18nT>
        </ICardHeading>
      </template>
    </ResourceImport>
  </MainLayout>
</template>

<script setup>
import { shallowRef } from 'vue'

import ResourceImport from '@/Core/components/Import/ResourceImport.vue'

import { usePipelines } from '../composables/usePipelines'

const resourceName = Innoclapps.resourceName('deals')

const { orderedPipelines: pipelines } = usePipelines()

const pipeline = shallowRef(pipelines.value[0])
</script>
