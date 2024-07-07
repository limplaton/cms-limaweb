<template>
  <ISlideover
    :ok-disabled="form.busy"
    :ok-text="$t('core::app.create')"
    :title="$t('core::role.create')"
    visible
    form
    static-backdrop
    @submit="save"
    @hidden="$router.back"
    @shown="handleModalShown"
  >
    <RolesFormFields
      ref="formRef"
      v-model:name="form.name"
      v-model:permissions="form.permissions"
      :form="form"
      in-create-mode
    />
  </ISlideover>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import { useForm } from '@/Core/composables/useForm'

import { useRoles } from '../../composables/useRoles'

import RolesFormFields from './RolesFormFields.vue'

const { t } = useI18n()

const formRef = ref(null)
const router = useRouter()
const { addRole } = useRoles()

const { form } = useForm({
  name: '',
  permissions: [],
})

function handleModalShown() {
  formRef.value.nameRef.focus()
}

function save() {
  form.post('/roles').then(role => {
    addRole(role)
    Innoclapps.success(t('core::role.created'))
    router.back()
  })
}
</script>
