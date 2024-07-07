<template>
  <IModal
    size="sm"
    :ok-text="$t('core::app.create')"
    :ok-disabled="form.busy"
    :title="$t('brands::brand.create')"
    visible
    static-backdrop
    form
    @submit="create"
    @hidden="$router.back"
    @shown="() => $refs.inputNameRef.focus()"
  >
    <IFormGroup
      label-for="name"
      :label="$t('brands::brand.form.name')"
      required
    >
      <IFormInput
        id="name"
        ref="inputNameRef"
        v-model="form.name"
        @change="!form.display_name ? (form.display_name = $event) : undefined"
      />

      <IFormError :error="form.getError('name')" />
    </IFormGroup>

    <IFormGroup
      label-for="display_name"
      :label="$t('brands::brand.form.display_name')"
      required
    >
      <IFormInput id="display_name" v-model="form.display_name" />

      <IFormError :error="form.getError('display_name')" />
    </IFormGroup>

    <IFormGroup :label="$t('brands::brand.form.primary_color')">
      <IColorSwatch
        v-model="form.config.primary_color"
        :swatches="$scriptConfig('favourite_colors')"
      />

      <IFormError :error="form.getError('config.primary_color')" />
    </IFormGroup>

    <IFormGroup>
      <IFormCheckboxField>
        <IFormCheckbox v-model:checked="form.is_default" />

        <IFormCheckboxLabel :text="$t('brands::brand.form.is_default')" />
      </IFormCheckboxField>

      <IFormError :error="form.getError('is_default')" />
    </IFormGroup>

    <IFormGroup class="mt-4">
      <VisibilityGroupSelector
        v-model:type="form.visibility_group.type"
        v-model:dependsOn="form.visibility_group.depends_on"
      />
    </IFormGroup>
  </IModal>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import VisibilityGroupSelector from '@/Core/components/VisibilityGroupSelector.vue'
import { useForm } from '@/Core/composables/useForm'

import { useBrands } from '../composables/useBrands'

const { t } = useI18n()
const router = useRouter()

const { addBrand } = useBrands()

const { form } = useForm({
  name: '',
  display_name: '',
  is_default: false,
  config: {
    primary_color: '#4f46e5',
  },
  visibility_group: {
    type: 'all',
    depends_on: [],
  },
})

function create() {
  form.post('/brands').then(brand => {
    addBrand(brand)

    Innoclapps.success(t('brands::brand.created'))

    router.push({
      name: 'edit-brand',
      params: {
        id: brand.id,
      },
    })
  })
}
</script>
