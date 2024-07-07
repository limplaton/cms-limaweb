<template>
  <IModal
    size="sm"
    :ok-text="$t('users::user.send_invitation')"
    :ok-disabled="form.busy"
    :title="$t('users::user.invite')"
    form
    static-backdrop
    visible
    @submit="invite"
    @hidden="$router.back"
    @shown="handleModalShown"
  >
    <IText>
      {{
        $t('users::user.invitation_expires_after_info', {
          total: $scriptConfig('invitation.expires_after'),
        })
      }}
    </IText>

    <div
      class="mb-4 border-b border-neutral-200 pt-4 dark:border-neutral-500/30"
    />

    <div class="mb-3 flex">
      <IFormLabel
        class="grow text-neutral-900 dark:text-neutral-100"
        for="email0"
        :label="$t('users::user.email')"
        required
      />

      <IButton
        v-i-tooltip="$t('core::app.add_another')"
        icon="PlusSolid"
        basic
        small
        @click="addEmail"
      />
    </div>

    <div
      v-for="(email, index) in form.emails"
      :key="index"
      class="relative mb-3"
    >
      <IFormInput
        :id="'email' + index"
        ref="emailsRef"
        v-model="form.emails[index]"
        type="email"
        :placeholder="$t('users::user.email')"
        @keydown.enter.prevent="addEmail"
      />

      <IButton
        v-show="index > 0"
        class="absolute right-1.5 top-1.5 sm:top-1"
        icon="XSolid"
        basic
        small
        @click="removeEmail(index)"
      />

      <IFormError :error="form.getError('emails.' + index)" />
    </div>

    <IFormGroup label-for="roles" class="mt-3" :label="$t('core::role.roles')">
      <ICustomSelect
        v-model="form.roles"
        input-id="roles"
        label="name"
        :placeholder="$t('users::user.roles')"
        :options="rolesNames"
        :multiple="true"
      />
    </IFormGroup>

    <IFormGroup label-for="teams" :label="$t('users::team.teams')">
      <ICustomSelect
        v-model="form.teams"
        input-id="teams"
        label="name"
        :placeholder="$t('users::team.teams')"
        :options="teams"
        :reduce="team => team.id"
        :multiple="true"
      />
    </IFormGroup>

    <div
      :class="[
        'flex items-center rounded-lg border-2 px-5 py-4 shadow-sm',
        form.super_admin
          ? 'border-primary-400'
          : 'border-neutral-200 dark:border-neutral-500/30',
      ]"
    >
      <IFormSwitchField>
        <IFormSwitchLabel :text="$t('users::user.super_admin')" />

        <IFormSwitchDescription :text="$t('users::user.as_super_admin_info')" />

        <IFormSwitch
          v-model="form.super_admin"
          @change="handleSuperAdminChange"
        />
      </IFormSwitchField>
    </div>

    <div
      :class="[
        'mt-3 flex items-center rounded-lg border-2 px-5 py-4 shadow-sm',
        form.access_api
          ? 'border-primary-400'
          : 'border-neutral-200 dark:border-neutral-500/30',
      ]"
    >
      <IFormSwitchField>
        <IFormSwitchLabel :text="$t('users::user.enable_api')" />

        <IFormSwitchDescription :text="$t('users::user.allow_api_info')" />

        <IFormSwitch v-model="form.access_api" :disabled="form.super_admin" />
      </IFormSwitchField>
    </div>
  </IModal>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import { useForm } from '@/Core/composables/useForm'
import { useRoles } from '@/Core/composables/useRoles'

import { useTeams } from '../composables/useTeams'

const { t } = useI18n()
const router = useRouter()

const emailsRef = ref([])

const { rolesNames } = useRoles()
const { teamsByName: teams } = useTeams()

const { form } = useForm({
  emails: [''],
  access_api: false,
  super_admin: false,
  roles: [],
})

const totalEmails = computed(() => form.emails.length)

function addEmail() {
  form.emails.push('')

  nextTick(() => {
    emailsRef.value[totalEmails.value - 1].focus()
  })
}

function removeEmail(index) {
  form.emails.splice(index, 1)

  nextTick(() => {
    if (form.emails[totalEmails.value - 1] === '') {
      emailsRef.value[totalEmails.value - 1].focus()
    }
  })
}

function handleModalShown() {
  nextTick(() => {
    emailsRef.value[0].focus()
  })
}

function handleSuperAdminChange(val) {
  if (val) {
    form.access_api = true
  }
}

function invite() {
  form.post('/users/invite').then(() => {
    Innoclapps.success(t('users::user.invited'))
    router.back()
  })
}
</script>
