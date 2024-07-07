
import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import filter from 'lodash/filter'
import orderBy from 'lodash/orderBy'

import { useApp } from '@/Core/composables/useApp'

export function useEmailAccounts() {
  const store = useStore()
  const { scriptConfig } = useApp()

  const emailAccountBeingSynced = ref(false)
  const emailAccountsBeingLoaded = ref(false)

  const emailAccounts = computed(() =>
    orderBy(
      store.state.emailAccounts.collection,
      ['is_primary', 'email'],
      ['desc', 'asc']
    )
  )

  const sharedEmailAccounts = computed(() =>
    filter(store.state.emailAccounts.collection, ['type', 'shared'])
  )

  const personalEmailAccounts = computed(() =>
    filter(store.state.emailAccounts.collection, ['type', 'personal'])
  )

  const latestEmailAccount = computed(
    () =>
      orderBy(emailAccounts.value, account => new Date(account.created_at), [
        'desc',
      ])[0]
  )

  const hasPrimaryEmailAccount = computed(
    () =>
      filter(emailAccounts.value, { is_primary: true, is_sync_stopped: false })
        .length > 0
  )

  async function syncEmailAccount(accountId) {
    emailAccountBeingSynced.value = true

    let { data } = await Innoclapps.request(
      `/mail/accounts/${accountId}/sync`
    ).finally(() => (emailAccountBeingSynced.value = false))

    return data
  }

  async function fetchEmailAccounts() {
    emailAccountsBeingLoaded.value = true

    try {
      return await store.dispatch('emailAccounts/fetch')
    } finally {
      emailAccountsBeingLoaded.value = false
    }
  }

  function createOAuthConnectUrl(connection_type, type) {
    if (connection_type == 'Gmail') {
      return `${scriptConfig('url')}/mail/accounts/${type}/google/connect`
    } else if (connection_type == 'Outlook') {
      return `${scriptConfig('url')}/mail/accounts/${type}/microsoft/connect`
    }
  }

  return {
    emailAccountsBeingLoaded,
    emailAccountBeingSynced,

    emailAccounts,
    sharedEmailAccounts,
    personalEmailAccounts,
    latestEmailAccount,
    hasPrimaryEmailAccount,

    fetchEmailAccounts,
    syncEmailAccount,
    createOAuthConnectUrl,
  }
}
