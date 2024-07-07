<template>
  <ILink
    :title="subject"
    :to="{
      name: 'inbox-message',
      params: {
        account_id: accountId,
        folder_id: folderId,
        id: messageId,
      },
    }"
    :text="subject ? subject : '(' + $t('mailclient::inbox.no_subject') + ')'"
    basic
  />
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'

defineProps(['subject', 'messageId', 'accountId'])

const route = useRoute()

const folderId = ref(route.params.folder_id)
</script>
