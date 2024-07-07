<template>
  <ISlideover
    :ok-disabled="form.busy"
    :ok-loading="form.busy"
    :ok-text="$t('core::app.create')"
    :title="$t('users::user.create')"
    form
    visible
    static-backdrop
    @hidden="$emit('hidden')"
    @shown="() => $refs.formRef.$refs.name.focus()"
    @submit="create"
  >
    <UserFormFields
      ref="formRef"
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
    />
  </ISlideover>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import reduce from 'lodash/reduce'

import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { useForm } from '@/Core/composables/useForm'

import UserFormFields from '../components/UserFormFields.vue'

const emit = defineEmits(['created', 'hidden'])

const router = useRouter()
const store = useStore()
const { t } = useI18n()
const { scriptConfig, setting } = useApp()
const { guessTimezone } = useDates()

const { form } = useForm({
  name: '',
  email: '',
  roles: [],

  password: '',
  password_confirmation: '',

  timezone: guessTimezone(),
  locale: 'en',
  date_format: setting('date_format'),
  time_format: setting('time_format'),

  notifications_settings: reduce(
    scriptConfig('notifications_settings'),
    function (obj, val) {
      let channels = {}
      val.channels.forEach(channel => (channels[channel] = true))
      obj[val.key] = channels

      return obj
    },
    {}
  ),

  super_admin: false,
  access_api: false,

  avatar: null,
})

async function create() {
  try {
    let user = await form.post('/users')
    store.commit('users/ADD', user)

    emit('created', user)

    Innoclapps.success(t('core::resource.created'))
  } catch (e) {
    if (e.isValidationError()) {
      Innoclapps.error(
        t('core::app.form_validation_failed_with_sections'),
        3000
      )
    }

    return Promise.reject(e)
  }

  router.back()
}
</script>
