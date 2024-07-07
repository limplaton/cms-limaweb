<template>
  <ITabGroup v-model="activeTabIndex">
    <ITabList>
      <ITab @activated="handleTabActivated">
        <Icon icon="User" />
        {{ $t('users::user.users') }}
      </ITab>

      <ITab @activated="handleTabActivated">
        <Icon icon="ShieldExclamation" />
        {{ $t('core::role.roles') }}
      </ITab>

      <ITab @activated="handleTabActivated">
        <Icon icon="UserGroup" />
        {{ $t('users::team.teams') }}
      </ITab>
    </ITabList>

    <ITabPanels>
      <!-- Make users tab lazy as ManageTeams is clearing the table settings in modifications -->
      <ITabPanel lazy>
        <UsersIndex />
      </ITabPanel>

      <ITabPanel>
        <RouterView name="roles" />
      </ITabPanel>

      <ITabPanel>
        <RouterView name="teams" />
      </ITabPanel>
    </ITabPanels>
  </ITabGroup>
</template>

<script setup>
import { ref } from 'vue'
import { onBeforeRouteUpdate, useRoute, useRouter } from 'vue-router'

import UsersIndex from '../views/UsersIndex.vue'

const route = useRoute()
const router = useRouter()
const activeTabIndex = ref(0)

function handleTabActivated() {
  if (
    activeTabIndex.value === 0 &&
    !['create-user', 'edit-user', 'invite-user'].includes(route.name)
  ) {
    router.push({ name: 'users-index' })
  } else if (
    activeTabIndex.value === 1 &&
    !['create-role', 'edit-role'].includes(route.name)
  ) {
    router.push({ name: 'role-index' })
  } else if (activeTabIndex.value === 2) {
    router.push({ name: 'manage-teams' })
  }
}

onBeforeRouteUpdate((to, from, next) => {
  // When clicking directly on the settings menu Users item
  if (to.name === 'users-index') {
    activeTabIndex.value = 0
  }

  next()
})

// Direct access support
if (['role-index', 'create-role', 'edit-role'].includes(route.name)) {
  activeTabIndex.value = 1
} else if (route.name === 'manage-teams') {
  activeTabIndex.value = 2
}
</script>
