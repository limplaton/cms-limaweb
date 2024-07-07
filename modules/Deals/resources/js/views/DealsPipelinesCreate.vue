<template>
  <IModal
    size="sm"
    :ok-text="$t('core::app.create')"
    :ok-disabled="form.busy"
    :title="$t('deals::deal.pipeline.create')"
    form
    visible
    @submit="create"
    @shown="() => $refs.inputNameRef.focus()"
    @hidden="$router.back"
  >
    <IFormGroup
      label-for="name"
      :label="$t('deals::deal.pipeline.name')"
      required
    >
      <IFormInput
        id="name"
        ref="inputNameRef"
        v-model="form.name"
        name="name"
        type="text"
      />

      <IFormError :error="form.getError('name')" />
    </IFormGroup>
  </IModal>
</template>

<script setup>
import { useRouter } from 'vue-router'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

import { usePipelines } from '../composables/usePipelines'

const router = useRouter()
const { addPipeline } = usePipelines()
const { resetStoreState } = useApp()

const { form } = useForm({
  name: null,
})

function create() {
  form.post('/pipelines').then(pipeline => {
    addPipeline(pipeline)
    resetStoreState()
    router.push('/settings/deals/pipelines/' + pipeline.id + '/edit')
  })
}
</script>
