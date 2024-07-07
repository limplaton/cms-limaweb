<template>
  <IAlert v-if="isSyncDisabled" class="mb-4" variant="warning">
    <IAlertBody>
      <p v-text="account.sync_state_comment" />

      <ILink
        variant="warning"
        class="font-medium"
        :to="{ name: 'email-accounts-index' }"
      >
        {{ $t('mailclient::mail.account.manage') }}
        <span aria-hidden="true">&rarr;</span>
      </ILink>
    </IAlertBody>
  </IAlert>

  <IAlert
    v-if="!hasPrimaryAccount && totalAccounts > 1"
    class="mb-4"
    variant="warning"
  >
    <IAlertBody>
      <p v-t="'mailclient::mail.account.missing_primary_account'" />

      <ILink
        variant="warning"
        class="font-medium"
        :to="{ name: 'email-accounts-index' }"
      >
        {{ $t('mailclient::mail.account.manage') }}
        <span aria-hidden="true">&rarr;</span>
      </ILink>
    </IAlertBody>
  </IAlert>

  <IAlert
    v-if="!account.sent_folder_id || !account.sent_folder"
    class="mb-4"
    variant="warning"
  >
    <IAlertBody>
      <p v-t="'mailclient::mail.account.missing_sent_folder'" />

      <ILink
        variant="warning"
        class="font-medium"
        :to="{ name: 'edit-email-account', params: { id: account.id } }"
      >
        {{ $t('mailclient::mail.account.edit') }}
        <span aria-hidden="true">&rarr;</span>
      </ILink>
    </IAlertBody>
  </IAlert>

  <IAlert
    v-if="!account.trash_folder_id || !account.trash_folder"
    class="mb-4"
    variant="warning"
  >
    <IAlertBody>
      <p v-t="'mailclient::mail.account.missing_trash_folder'" />

      <ILink
        variant="warning"
        class="font-medium"
        :to="{ name: 'edit-email-account', params: { id: account.id } }"
      >
        {{ $t('mailclient::mail.account.edit') }}
        <span aria-hidden="true">&rarr;</span>
      </ILink>
    </IAlertBody>
  </IAlert>
</template>

<script setup>
defineProps({
  account: { type: Object, required: true },
  totalAccounts: { required: true, type: Number },
  hasPrimaryAccount: { required: true, type: Boolean },
  isSyncDisabled: { required: true, type: Boolean },
})
</script>
