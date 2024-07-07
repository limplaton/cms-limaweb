<template>
  <IModal
    size="sm"
    :ok-text="$t('core::app.create')"
    :ok-disabled="form.busy"
    :title="$t('webforms::form.create')"
    visible
    static-backdrop
    form
    @submit="create"
    @hidden="$router.back"
    @shown="() => $refs.inputTitleRef.focus()"
  >
    <IFormGroup
      label-for="title"
      :description="$t('webforms::form.title_visibility_info')"
      :label="$t('webforms::form.title')"
      required
    >
      <IFormInput id="title" ref="inputTitleRef" v-model="form.title" />

      <IFormError :error="form.getError('title')" />
    </IFormGroup>

    <div class="mb-2">
      <h5
        v-t="'webforms::form.style.style'"
        class="mb-3 font-medium text-neutral-700 dark:text-neutral-300"
      />

      <IFormGroup :label="$t('webforms::form.style.primary_color')">
        <IColorSwatch
          v-model="form.styles.primary_color"
          :swatches="swatches"
        />

        <IFormError :error="form.getError('styles.primary_color')" />
      </IFormGroup>

      <IFormGroup :label="$t('webforms::form.style.background_color')">
        <IColorSwatch
          v-model="form.styles.background_color"
          :swatches="swatches"
        />

        <IFormError :error="form.getError('styles.background_color')" />
      </IFormGroup>
    </div>
  </IModal>
</template>

<script setup>
import { useRouter } from 'vue-router'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

import { useWebForms } from '../composables/useWebForms'

const router = useRouter()
const { addWebForm } = useWebForms()
const { scriptConfig } = useApp()

const swatches = scriptConfig('favourite_colors')

const { form } = useForm({
  title: null,
  styles: {
    primary_color: '#4f46e5',
    background_color: '#F3F4F6',
  },
})

function create() {
  form.post('/forms').then(data => {
    addWebForm(data)

    router.push({
      name: 'web-form-edit',
      params: {
        id: data.id,
      },
    })
  })
}
</script>
