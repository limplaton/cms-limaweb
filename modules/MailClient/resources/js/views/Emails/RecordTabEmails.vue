<template>
  <ITab v-memo="[resource.unread_emails_for_user_count]">
    <Icon icon="Mail" />

    {{ $t('mailclient::mail.emails') }}

    <IBadge
      v-show="resource.unread_emails_for_user_count"
      variant="info"
      :text="resource.unread_emails_for_user_count"
      pill
    />
  </ITab>
</template>

<script setup>
defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
})
</script>
