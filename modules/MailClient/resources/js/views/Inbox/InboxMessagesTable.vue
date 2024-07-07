<template>
  <component
    :is="
      tableId === 'messages-incoming'
        ? InboxMessagesTableIncoming
        : InboxMessagesTableOutgoing
    "
    ref="tableComponentRef"
    :table-id="tableId"
    :account-id="account.id"
    :is-sync-stopped="account.is_sync_stopped"
    :data-request-query-string="tableDataRequestQueryString"
    :action-request-params="actionRequestParams"
  />
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import find from 'lodash/find'

import { useApp } from '@/Core/composables/useApp'
import { useGlobalEventListener } from '@/Core/composables/useGlobalEventListener'
import { usePageTitle } from '@/Core/composables/usePageTitle'
import { useTable } from '@/Core/composables/useTable'

import InboxMessagesTableIncoming from './InboxMessagesTableIncoming.vue'
import InboxMessagesTableOutgoing from './InboxMessagesTableOutgoing.vue'

const props = defineProps({
  account: { type: Object, required: true },
})

const route = useRoute()
const pageTitle = usePageTitle()
const { scriptConfig } = useApp()

const folderId = ref(null)
const accountId = ref(null)
const folder = ref({})
const tableComponentRef = ref(null)

const actionRequestParams = computed(() => ({
  folder_id: folderId.value,
  account_id: accountId.value,
}))

const tableDataRequestQueryString = computed(() => ({
  account_id: accountId.value,
  folder_id: folderId.value,
  folder_type: folderType.value,
  tag: route.query.tag,
}))

const folderType = computed(() => {
  if (isOutgoingFolderType.value) {
    return 'outgoing'
  } else if (isIncomingFolderType.value) {
    return 'incoming'
  }

  return scriptConfig('mail.folders.other')
})

const tableId = computed(() => {
  return folderType.value === 'incoming' ||
    folderType.value === scriptConfig('mail.folders.other')
    ? 'messages-incoming'
    : 'messages-outgoing'
})

/**
 * Checks whether the current folder of type outgoing
 * The computed also checks whether this folder is child in outgoing folder
 */
const isOutgoingFolderType = computed(() => {
  let currentFolderIsOutgoing =
    scriptConfig('mail.folders.outgoing').indexOf(folder.value.type) > -1

  if (currentFolderIsOutgoing) {
    return true
  }

  // Look more deeply to see if this is a child of the sent folder
  return isFolderChildIn('outgoing')
})

/**
 * Checks whether the current folder of type incoming
 * The computed also checks whether this folder is child in incoming folder
 */
const isIncomingFolderType = computed(() => {
  let currentFolderIsIncoming =
    scriptConfig('mail.folders.incoming').indexOf(folder.value.type) > -1

  if (currentFolderIsIncoming) {
    return true
  }

  // Look more deeply to see if this is a child of the sent folder
  return isFolderChildIn('incoming')
})

// Move below computed
// ReferenceError: Cannot access 'tableId' before initialization
const { reloadTable } = useTable(tableId)

/**
 * Check hierarchically whether the current folder
 * is a deep child of the the sent folder
 *
 * @param  {String}  The key name, to use for the check incoming or outgoing
 * @param  {Object|null}  hierarchicalFolder
 *
 * @return {Boolean}
 */
function isFolderChildIn(key, hierarchicalFolder) {
  let folderBeingChecked = hierarchicalFolder || folder.value

  if (!folderBeingChecked.parent_id) {
    return false
  }

  let parent = find(props.account.folders, [
    'id',
    parseInt(folderBeingChecked.parent_id),
  ])

  if (scriptConfig(`mail.folders.${key}`).indexOf(parent.type) > -1) {
    return true
  } else if (parent.parent_id) {
    return isFolderChildIn(key, parent)
  }

  return false
}

/**
 * When the user is viewing directly e.q. the sent folder
 * after the message is sent, we need to reload the folder.
 */
function reloadOutgoingFolderTable() {
  if (isOutgoingFolderType.value) {
    reloadTable()
  }
}

watch(
  () => route.params,
  (newVal, oldVal) => {
    const samePageNavigation =
      newVal.account_id && newVal.folder_id && !newVal.id // we will check if there is a message id, if yes, then it's another page

    accountId.value = newVal.account_id
    folderId.value = newVal.folder_id

    if (!oldVal || (oldVal && oldVal.folder_id != newVal.folder_id)) {
      folder.value = find(
        props.account.folders,
        folder => parseInt(folder.id) === parseInt(newVal.folder_id)
      )
    }

    if (samePageNavigation) {
      nextTick(
        () =>
          (pageTitle.value = `${folder.value.display_name} - ${props.account.display_email}`)
      )
    }

    // We need to refetch the table settings
    // when an account has been changed because of the MOVE TO
    // action is using the request params to compose the field options
    if (
      samePageNavigation &&
      oldVal &&
      parseInt(newVal.account_id) !== parseInt(oldVal.account_id)
    ) {
      // Reset the table page, as the user may be at page 200 to different account
      // and then change the account from the dropdown which does not have this 200 page.
      tableComponentRef.value.tableRef.setPage(1)

      nextTick(() => {
        tableComponentRef.value.tableRef.refetchActions()
      })
    }
  },
  { immediate: true }
)

useGlobalEventListener('user-synchronized-email-account', reloadTable)
useGlobalEventListener('email-accounts-sync-finished', reloadTable)
useGlobalEventListener('email-sent', reloadOutgoingFolderTable)
</script>

<style>
.sync-stopped-by-system table input[type='checkbox'] {
  pointer-events: none;
  opacity: 0.5;
}
</style>
