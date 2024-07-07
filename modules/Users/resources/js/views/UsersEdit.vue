<template>
  <ISlideover
    :ok-text="$t('core::app.save')"
    :ok-disabled="form.busy"
    :ok-loading="form.busy"
    :title="$t('users::user.edit')"
    form
    visible
    static-backdrop
    @hidden="$emit('hidden')"
    @submit="update"
  >
    <FieldsPlaceholder v-if="!componentReady" />

    <UserFormFields
      v-if="componentReady"
      v-model:name="form.name"
      v-model:email="form.email"
      v-model:roles="form.roles"
      v-model:password="form.password"
      v-model:password-confirmation="form.password_confirmation"
      v-model:timezone="form.timezone"
      v-model:locale="form.locale"
      v-model:date-format="form.date_format"
      v-model:time-format="form.time_format"
      v-model:notifications-settings="form.notifications_settings"
      v-model:super-admin="form.super_admin"
      v-model:access-api="form.access_api"
      :form="form"
      in-edit-mode
    />
  </ISlideover>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import reduce from 'lodash/reduce'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

import UserFormFields from '../components/UserFormFields.vue'

const emit = defineEmits(['updated', 'hidden'])

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const store = useStore()

const { currentUser } = useApp()

const { form } = useForm()
const componentReady = ref(false)
const originalLocale = ref(null)

async function update() {
  try {
    const user = await form.put(`/users/${route.params.id}`)
    store.commit('users/UPDATE', { id: user.id, item: user })

    Innoclapps.success(t('core::resource.updated'))

    if (
      user.locale !== originalLocale.value &&
      user.id == currentUser.value.id
    ) {
      window.location.reload(true)
    } else {
      emit('updated', user)
      router.back()
    }
  } catch (e) {
    if (e.isValidationError()) {
      Innoclapps.error(
        t('core::app.form_validation_failed_with_sections'),
        3000
      )
    }

    return Promise.reject(e)
  }
}

function prepareComponent() {
  Innoclapps.request(`/users/${route.params.id}`).then(({ data: user }) => {
    originalLocale.value = user.locale

    form.set({
      name: user.name,
      email: user.email,
      roles: user.roles.map(role => role.name),

      password: '',
      password_confirmation: '',

      timezone: user.timezone,
      locale: user.locale,
      date_format: user.date_format,
      time_format: user.time_format,

      notifications_settings: reduce(
        user.notifications.settings,
        function (obj, val) {
          obj[val.key] = val.availability

          return obj
        },
        {}
      ),
      super_admin: user.super_admin,
      access_api: user.access_api,
    })

    componentReady.value = true
  })
}

prepareComponent()
</script>
