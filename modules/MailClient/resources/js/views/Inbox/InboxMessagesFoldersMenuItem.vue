<template>
  <IVerticalNavigationCollapsible fixed>
    <IVerticalNavigationItem :to="folderRoute">
      {{ folder.display_name }}

      <IBadge v-if="folder.unread_count" :text="folder.unread_count" pill />
    </IVerticalNavigationItem>

    <template v-if="hasChildren">
      <InboxMessagesFoldersMenuItem
        v-for="child in folder.children"
        :key="child.id"
        :folder="child"
      />
    </template>
  </IVerticalNavigationCollapsible>
</template>

<script></script>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

defineOptions({
  name: 'InboxMessagesFoldersMenuItem',
})

const props = defineProps({
  folder: { required: true, type: Object },
})

const route = useRoute()

const folderRoute = computed(() => {
  // When the user first access the INBOX menu without any params
  // the account may be undefined till the inbox.vue redirects to the
  // messages using the default account
  // in this case, while all these actions are executed just return null
  // because it's throwing warning missing account params for name route 'inbox-messages'
  if (!route.params.account_id) {
    return null
  }

  return {
    name: 'inbox-messages',
    params: {
      account_id: route.params.account_id,
      folder_id: props.folder.id,
    },
  }
})

const hasChildren = computed(
  () => props.folder.children && props.folder.children.length > 0
)
</script>
