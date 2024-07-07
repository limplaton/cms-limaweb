<template>
  <ISlideover
    :ok-text="$t('core::app.save')"
    :ok-disabled="form.busy"
    :title="modalTitle"
    visible
    form
    static-backdrop
    @submit="update"
    @hidden="$router.back"
  >
    <FieldsPlaceholder v-if="!componentReady" />

    <RolesFormFields
      v-else
      v-model:name="form.name"
      v-model:permissions="form.permissions"
      :form="form"
    />
  </ISlideover>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

import { useForm } from '@/Core/composables/useForm'

import { useRoles } from '../../composables/useRoles'

import RolesFormFields from './RolesFormFields.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { fetchRole, setRole } = useRoles()

const componentReady = ref(false)

const { form } = useForm({
  name: '',
  permissions: [],
})

const modalTitle = computed(() => t('core::role.edit') + ' ' + form.name)

function update() {
  form.put(`/roles/${route.params.id}`).then(role => {
    setRole(role.id, role)
    Innoclapps.success(t('core::role.updated'))
    router.back()
  })
}

async function prepareComponent(id) {
  const role = await fetchRole(id)

  form.set({
    name: role.name,
    permissions: role.permissions.map(permission => permission.name),
  })

  nextTick(() => (componentReady.value = true))
}

prepareComponent(route.params.id)
</script>
