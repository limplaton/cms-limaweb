<template>
  <MainLayout :overlay="componentReady">
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />

      <NavbarItems>
        <IDropdownMinimal
          :placement="
            !(activeAccount.is_initial_sync_performed && !isSyncDisabled)
              ? 'bottom-end'
              : 'bottom'
          "
          horizontal
        >
          <IDropdownItem
            v-if="activeAccount.authorizations.update"
            :to="{
              name: `edit-email-account`,
              params: { id: activeAccount.id },
            }"
            :text="$t('mailclient::mail.account.edit')"
          />

          <IDropdownItem
            :to="{ name: 'email-accounts-index' }"
            :text="$t('mailclient::mail.account.manage')"
          />
        </IDropdownMinimal>

        <div
          v-show="
            countScheduledEmails > 0 ||
            (activeAccount.is_initial_sync_performed && !isSyncDisabled)
          "
          class="flex items-center space-x-2"
        >
          <div v-if="countScheduledEmails > 0" class="relative">
            <IButton
              v-i-tooltip.left="$t('mailclient::schedule.scheduled_emails')"
              variant="secondary"
              icon="Clock"
              :to="{ name: 'scheduled-emails-index' }"
              pill
            />

            <span
              v-if="countScheduledEmails > 0"
              v-i-tooltip.bottom="countScheduledEmails"
              class="absolute -right-1 -top-px z-10"
            >
              <span class="relative flex size-3">
                <span
                  class="absolute inline-flex h-full w-full rounded-full bg-info-400 opacity-75"
                />

                <span
                  class="relative inline-flex size-3 rounded-full bg-info-500"
                />
              </span>
            </span>
          </div>

          <IButton
            v-if="activeAccount.is_initial_sync_performed && !isSyncDisabled"
            v-i-tooltip.left="$t('mailclient::inbox.synchronize')"
            variant="secondary"
            icon="Refresh"
            :disabled="emailAccountBeingSynced"
            :loading="emailAccountBeingSynced"
            pill
            @click="manuallySyncCurrentAccount"
          />
        </div>
      </NavbarItems>
    </template>

    <div class="mx-auto max-w-7xl">
      <div
        v-if="!componentReady && hasAccounts"
        class="grid grid-cols-12 gap-6"
      >
        <div class="col-span-12 lg:col-span-3">
          <IDropdown adaptive-width>
            <IDropdownButton
              class="mb-1.5 w-full"
              icon="InboxStack"
              :loading="emailAccountBeingSynced"
              basic
            >
              <span
                class="max-w-[15rem] truncate"
                v-text="activeAccount.display_email"
              />
            </IDropdownButton>

            <IDropdownMenu>
              <IDropdownItem
                v-for="account in emailAccounts"
                :key="account.id"
                :text="account.display_email"
                :active="account.id === activeAccount.id"
                @click="handleAccountSelected(account)"
              />
            </IDropdownMenu>
          </IDropdown>
        </div>
      </div>

      <div
        v-if="!componentReady && hasAccounts"
        class="grid grid-cols-12 gap-6"
      >
        <div class="col-span-12 lg:col-span-3">
          <div class="sm:sticky sm:top-2">
            <IButton
              variant="primary"
              class="mb-3"
              :disabled="!activeAccount.can_send_email"
              block
              @click="compose(true)"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="mr-2 fill-white"
                height="16"
                width="16"
                viewBox="0 0 512 512"
              >
                <path
                  d="M410.3 231l11.3-11.3-33.9-33.9-62.1-62.1L291.7 89.8l-11.3 11.3-22.6 22.6L58.6 322.9c-10.4 10.4-18 23.3-22.2 37.4L1 480.7c-2.5 8.4-.2 17.5 6.1 23.7s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L387.7 253.7 410.3 231zM160 399.4l-9.1 22.7c-4 3.1-8.5 5.4-13.3 6.9L59.4 452l23-78.1c1.4-4.9 3.8-9.4 6.9-13.3l22.7-9.1v32c0 8.8 7.2 16 16 16h32zM362.7 18.7L348.3 33.2 325.7 55.8 314.3 67.1l33.9 33.9 62.1 62.1 33.9 33.9 11.3-11.3 22.6-22.6 14.5-14.5c25-25 25-65.5 0-90.5L453.3 18.7c-25-25-65.5-25-90.5 0zm-47.4 168l-144 144c-6.2 6.2-16.4 6.2-22.6 0s-6.2-16.4 0-22.6l144-144c6.2-6.2 16.4-6.2 22.6 0s6.2 16.4 0 22.6z"
                />
              </svg>
              {{ $t('mailclient::mail.compose') }}
            </IButton>

            <FoldersMenu
              :folders="activeAccount && activeAccount.active_folders_tree"
            />

            <ul
              v-if="availableTags.length"
              :class="[
                'mt-8 flex flex-1 flex-col gap-y-0.5',
                !hasActiveFolders ? 'hidden' : '',
              ]"
            >
              <li
                class="mb-2 text-base font-semibold leading-6 text-neutral-700 dark:text-neutral-300 sm:text-sm"
              >
                {{ $t('core::tags.tags') }} ({{ availableTags.length }})
              </li>

              <li v-for="tag in availableTags" :key="tag.id">
                <ILink
                  class="flex items-center space-x-2 rounded-lg px-3 py-2 text-base/6 font-medium text-neutral-700 hover:bg-neutral-200/50 focus:outline-none dark:text-neutral-100 dark:hover:dark:bg-neutral-700 sm:text-sm/6"
                  :class="
                    $route.query.tag == tag.name
                      ? 'bg-neutral-200/50 dark:bg-neutral-700'
                      : ''
                  "
                  :to="{
                    query: {
                      tag: tag.name,
                    },
                  }"
                  plain
                >
                  <Icon
                    icon="Tag"
                    class="size-4"
                    :style="{ color: tag.swatch_color }"
                  />

                  <span class="grow" v-text="tag.name" />

                  <span class="-my-1">
                    <IButton
                      v-if="$route.query.tag == tag.name"
                      icon="XSolid"
                      basic
                      small
                      @click.prevent.stop="
                        $router.replace({
                          query: {
                            ...$route.query,
                            tag: undefined,
                          },
                        })
                      "
                    />
                  </span>
                </ILink>
              </li>
            </ul>
          </div>
        </div>

        <div class="col-span-12 lg:col-span-9">
          <InboxMessagesWarnings
            :account="activeAccount"
            :total-accounts="emailAccounts.length"
            :has-primary-account="hasPrimaryEmailAccount"
            :is-sync-disabled="isSyncDisabled"
          />

          <IAlert v-if="!activeAccount.is_initial_sync_performed" class="mb-4">
            <IAlertBody>
              {{ $t('mailclient::mail.initial_sync_info') }}
            </IAlertBody>
          </IAlert>

          <RouterView name="message" :account="activeAccount" />

          <RouterView
            v-if="hasActiveFolders"
            ref="messages"
            name="messages"
            :account="activeAccount"
          />

          <div v-else class="h-60">
            <div class="mx-auto mt-5 block max-w-2xl text-center">
              <Icon icon="Folder" class="mx-auto size-12 text-neutral-400" />

              <IText
                class="mt-4"
                :text="$t('mailclient.mail.account.no_active_folders')"
              />

              <div class="mt-6 space-x-6">
                <ILink
                  v-if="activeAccount.authorizations.update"
                  class="font-medium"
                  :to="{
                    name: 'edit-email-account',
                    params: { id: activeAccount.id },
                  }"
                >
                  {{ $t('mailclient::mail.account.activate_folders') }}
                  <span aria-hidden="true">&rarr;</span>
                </ILink>

                <ILink
                  class="font-medium"
                  :to="{ name: 'email-accounts-index' }"
                >
                  {{ $t('mailclient::mail.account.manage') }}
                  <span aria-hidden="true">&rarr;</span>
                </ILink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ComposeMessage
      :visible="isComposing"
      :default-account="activeAccount"
      @hidden="compose(false)"
    />
  </MainLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { onBeforeRouteUpdate, useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'

import {
  emitGlobal,
  useGlobalEventListener,
} from '@/Core/composables/useGlobalEventListener'

import { useEmailAccounts } from '../../composables/useEmailAccounts'
import { useMessageTags } from '../../composables/useMessageTags'
import ComposeMessage from '../Emails/ComposeMessage.vue'

import FoldersMenu from './InboxMessagesFoldersMenu.vue'
import InboxMessagesWarnings from './InboxMessagesWarnings.vue'

const route = useRoute()
const router = useRouter()
const store = useStore()

const componentReady = ref(true)
const isComposing = ref(false)
const countScheduledEmails = ref(0)
const activeAccountId = ref(null)
const { availableTags } = useMessageTags()

const {
  emailAccounts,
  hasPrimaryEmailAccount,
  emailAccountBeingSynced,
  fetchEmailAccounts,
  syncEmailAccount,
} = useEmailAccounts()

watch(
  () => route.query.compose,
  newVal => {
    newVal && compose()
  }
)

// When viewing a message and click on a tag located in the left menu
// it should navigate away from the message and show the table filtered by the selected tag.
watch(
  () => route.query.tag,
  newVal => {
    if (newVal && route.params.id) {
      router.push({
        name: 'inbox-messages',
        account_id: route.params.accountId,
        folderId: route.params.folderId,
        query: {
          ...route.query,
          tag: newVal,
        },
      })
    }
  }
)

/**
 * When navigating e.q. from message and directly clicking
 * on the MENU item INBOX, we need to trigger the initAccounts methods
 * as the accounts are not loaded nor redirecting to the messages route
 */
onBeforeRouteUpdate((to, from, next) => {
  if (to.name === 'inbox') {
    redirectToAccountMessages(activeAccount.value, to.query)
  } else {
    next()
  }
})

const activeAccount = computed(
  () =>
    emailAccounts.value.find(account => account.id === activeAccountId.value) ||
    {}
)

const hasAccounts = computed(() => emailAccounts.value.length > 0)

const hasActiveFolders = computed(
  () => activeAccount.value.active_folders.length > 0
)

const isSyncDisabled = computed(
  () =>
    activeAccount.value.is_sync_stopped || activeAccount.value.is_sync_disabled
)

function updateAccountInStore(data) {
  store.commit('emailAccounts/UPDATE', data)
}

function compose(boolean = true) {
  isComposing.value = boolean

  if (boolean == false) {
    let query = Object.assign({}, route.query)
    delete query.compose
    router.replace({ query })
  }
}

function handleAccountSelected(account) {
  activeAccountId.value = account.id
  redirectToAccountMessages(account)
}

function handleActionExecutedEvent(action) {
  // Makes sure to update the account after an action is executed
  // This will be update data like the folders unread count
  if (Object.hasOwn(action.response, 'account')) {
    store.commit('emailAccounts/UPDATE', {
      id: action.response.account.id,
      item: action.response.account,
    })
  }

  // Update global unread messages count
  if (Object.hasOwn(action.response, 'unread_count')) {
    store.dispatch(
      'emailAccounts/updateUnreadCountUI',
      action.response.unread_count
    )
  }
}

function redirectToAccountMessages(forAccount, query = {}) {
  let folderId = forAccount.active_folders[0]
    ? forAccount.active_folders[0].id
    : null

  // When account does not have active folders
  if (!folderId) {
    return
  }

  router.replace({
    name: 'inbox-messages',
    params: {
      account_id: forAccount.id,
      // Sets the first syncable folder as default
      folder_id: folderId,
    },
    query: { ...route.query, ...query },
  })
}

async function retrieveCountOfScheduledEmails() {
  const { data: scheduledData } = await Innoclapps.request(
    '/emails/scheduled/count'
  )

  countScheduledEmails.value = scheduledData.count
}

function handleSyncFinishedEvent() {
  initAccounts(true)
}

async function initAccounts(skipCache = false) {
  await fetchEmailAccounts({
    force: skipCache,
  })

  if (!hasAccounts.value) {
    router.replace({
      name: 'email-accounts-index',
    })

    return
  }

  retrieveCountOfScheduledEmails()

  // Check if the account is configured when handleSyncFinishedEvent method calls this function
  if (route.params.account_id && route.params.folder_id) {
    activeAccountId.value = parseInt(route.params.account_id)
  } else if (Object.keys(activeAccount.value).length === 0) {
    activeAccountId.value = emailAccounts.value[0].id
  }

  // When accessing the INBOX route without any params
  // Redirect to the messages
  if (route.name === 'inbox' && hasActiveFolders.value) {
    redirectToAccountMessages(activeAccount.value)
  }
}

function manuallySyncCurrentAccount() {
  syncEmailAccount(activeAccount.value.id).then(data => {
    // Update the account in store in case of folder changes
    updateAccountInStore({ id: data.id, item: data })

    emitGlobal('user-synchronized-email-account', data)
  })
}

initAccounts()
  .then(() => route.query.compose && compose())
  .finally(() => (componentReady.value = false))

useGlobalEventListener('email-scheduled', retrieveCountOfScheduledEmails)
useGlobalEventListener('action-executed', handleActionExecutedEvent)
useGlobalEventListener('email-accounts-sync-finished', handleSyncFinishedEvent)
</script>
