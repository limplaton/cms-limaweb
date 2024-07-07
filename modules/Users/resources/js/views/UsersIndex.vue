<template>
  <div class="mb-5 flex items-center justify-between">
    <ITextDisplay :text="$t('users::user.users')" />

    <div class="flex items-center space-x-3">
      <IButton
        variant="secondary"
        :disabled="!componentReady"
        :to="{ name: 'invite-user' }"
        :text="$t('users::user.invite')"
      />

      <IButton
        variant="primary"
        icon="PlusSolid"
        :disabled="!componentReady"
        :to="{ name: 'create-user' }"
        :text="$t('users::user.create')"
      />
    </div>
  </div>

  <ResourceTable
    ref="tableRef"
    :resource-name="resourceName"
    :table-id="tableId"
    :with-customize-button="true"
    @loaded="componentReady = true"
    @action-executed="handleActionExecutedEvent"
  />

  <!-- Create, Edit -->
  <RouterView
    name="createEdit"
    @created="reloadTable"
    @updated="reloadTable"
    @hidden="$router.push({ name: 'users-index' })"
  />

  <RouterView name="invite" />
</template>

<script setup>
import { onUnmounted, ref } from 'vue'
import { useStore } from 'vuex'

import { useApp } from '@/Core/composables/useApp'
import { useTable } from '@/Core/composables/useTable'

import { useTeams } from '../composables/useTeams'

const resourceName = Innoclapps.resourceName('users')
const tableId = 'users'

const store = useStore()
const { resetStoreState } = useApp()
const { reloadTable } = useTable(tableId)
const { fetchTeams } = useTeams()

const componentReady = ref(false)
const tableRef = ref(null)

function handleActionExecutedEvent(action) {
  if (action.destroyable) {
    action.ids.forEach(id => store.commit('users/REMOVE', id))

    // refetch the actions so the deleted user is not in the transfer list.
    tableRef.value.refetchActions()
    fetchTeams()
  }
}

onUnmounted(() => {
  /**
   * We need to reset the state in case changes are performed
   * because of the local cached data for the users
   */
  resetStoreState()
})
</script>
