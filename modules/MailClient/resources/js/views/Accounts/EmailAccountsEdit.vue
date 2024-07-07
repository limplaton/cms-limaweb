<template>
  <ISlideover
    :title="$t('mailclient::mail.account.edit')"
    visible
    static-backdrop
    form
    @hidden="$router.back"
    @submit="update"
  >
    <IAlert v-if="isSyncDisabled" class="mb-4" variant="warning">
      <IAlertBody>
        {{ account.sync_state_comment }}
      </IAlertBody>
    </IAlert>

    <EmailAccountsFormFields
      :form="form"
      :test-connection-form="testConnectionForm"
      :type="account.type || ''"
      :account="account"
    />

    <template #modal-ok>
      <IButton
        v-if="isImapAccount && requirements.imap"
        variant="primary"
        :loading="testConnectionForm.busy"
        :disabled="testConnectionForm.busy"
        :text="$t('mailclient::mail.account.test_connection')"
        @click="performImapConnectionTest"
      />

      <IButton
        type="submit"
        variant="primary"
        :loading="form.busy"
        :text="$t('core::app.save')"
        :disabled="!isImapConnectionSuccessful || form.busy"
      />
    </template>
  </ISlideover>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

import { useTestImapConnection } from '../../composables/useTestImapConnection'

import EmailAccountsFormFields from './EmailAccountsFormFields.vue'

const route = useRoute()
const router = useRouter()
const store = useStore()
const { t } = useI18n()
const { scriptConfig } = useApp()
const { form } = useForm()

const {
  isSuccessful: isImapConnectionSuccessful,
  testConnectionForm,
  testConnection,
} = useTestImapConnection(true)

const requirements = scriptConfig('requirements')

const account = ref({})

const isImapAccount = computed(() => form.connection_type === 'Imap')
const emailAccountId = computed(() => route.params.id)

const isSyncDisabled = computed(
  () => account.value.is_sync_stopped || account.value.is_sync_disabled
)

function update() {
  form.put(`/mail/accounts/${route.params.id}`).then(updatedAccount => {
    store.commit('emailAccounts/UPDATE', {
      id: updatedAccount.id,
      item: updatedAccount,
    })
    Innoclapps.success(t('mailclient::mail.account.updated'))
    router.back()
  })
}

function performImapConnectionTest() {
  testConnection(form).then(data => {
    form.requires_auth = false
    form.folders = data.folders
  })
}

function prepareComponent(id) {
  if (!id) return

  Innoclapps.request(`/mail/accounts/${id}`).then(({ data: emailAccount }) => {
    form.set(emailAccount)
    form.folders = emailAccount.folders_tree
    account.value = emailAccount
  })
}

watch(emailAccountId, prepareComponent, { immediate: true })
</script>
