<template>
  <MainLayout>
    <template #actions>
      <NavbarSeparator v-show="emailAccounts.length" class="hidden lg:block" />

      <IButton
        v-show="emailAccounts.length"
        variant="secondary"
        icon="Inbox"
        :to="{ name: 'inbox' }"
        :text="$t('mailclient::inbox.inbox')"
      />
    </template>

    <div v-show="emailAccounts.length" class="mx-auto max-w-7xl">
      <ICardHeader>
        <ICardHeading :text="$t('mailclient::mail.account.accounts')" />

        <ICardActions class="mt-2 w-full sm:mt-0 sm:w-auto">
          <div
            class="flex w-full flex-col space-x-0 space-y-1 sm:flex-row sm:space-x-2 sm:space-y-0"
          >
            <span
              v-i-tooltip="
                $t(
                  $gate.isRegularUser()
                    ? 'users::user.not_authorized'
                    : 'mailclient::mail.account.create_shared_info'
                )
              "
              class="grid sm:inline"
            >
              <IButton
                variant="secondary"
                icon="Share"
                :disabled="$gate.isRegularUser()"
                :text="$t('mailclient::mail.account.connect_shared')"
                @click="createShared"
              />
            </span>

            <IButton
              icon="User"
              variant="secondary"
              :text="$t('mailclient::mail.account.connect_personal')"
              @click="createPersonal"
            />
          </div>
        </ICardActions>
      </ICardHeader>

      <ICard :overlay="emailAccountsBeingLoaded">
        <TransitionGroup
          class="divide-y divide-neutral-200 dark:divide-neutral-500/30"
          name="flip-list"
          tag="ul"
        >
          <li v-for="account in emailAccounts" :key="account.id">
            <IAlert
              v-if="account.is_sync_stopped || account.requires_auth"
              variant="warning"
            >
              <IAlertBody>
                <p
                  v-if="account.requires_auth"
                  v-t="'core::oauth.requires_authentication'"
                />

                <p
                  v-if="account.is_sync_stopped"
                  v-text="account.sync_state_comment"
                />
              </IAlertBody>

              <IAlertActions v-if="account.requires_auth">
                <IButton
                  variant="warning"
                  :text="$t('core::oauth.re_authenticate')"
                  ghost
                  @click="reAuthenticate(account)"
                />
              </IAlertActions>
            </IAlert>

            <div class="flex items-center px-4 py-4 sm:px-6">
              <div
                class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between"
              >
                <div
                  :class="[
                    'truncate',
                    {
                      'opacity-70':
                        account.is_sync_stopped || account.is_sync_disabled,
                    },
                  ]"
                >
                  <div class="flex text-base/6 sm:text-sm/6">
                    <component
                      :is="account.authorizations.update ? 'a' : 'p'"
                      :class="[
                        'truncate font-medium text-primary-600 dark:text-primary-400',
                        account.authorizations.update ? 'cursor-pointer' : '',
                      ]"
                      @click="
                        account.authorizations.update
                          ? redirectToAccountEdit(account)
                          : ''
                      "
                    >
                      {{ account.email }}
                      {{
                        account.alias_email ? `(${account.alias_email})` : ''
                      }}
                    </component>
                  </div>

                  <div class="mt-2 flex">
                    <div
                      class="flex items-center space-x-1 text-base/6 sm:text-sm/6"
                    >
                      <GoogleIcon
                        v-if="account.connection_type === 'Gmail'"
                        class="mr-3 size-5 shrink-0"
                      />

                      <OutlookIcon
                        v-else-if="account.connection_type === 'Outlook'"
                        class="mr-3 size-5 shrink-0"
                      />

                      <IBadge
                        v-if="account.authorizations.update"
                        variant="info"
                        :text="account.connection_type"
                      />

                      <IBadge
                        :variant="account.is_personal ? 'neutral' : 'info'"
                        :text="$t('mailclient::mail.account.' + account.type)"
                      />

                      <IBadge
                        v-if="account.is_primary || emailAccounts.length === 1"
                        variant="primary"
                        :text="$t('mailclient::mail.account.is_primary')"
                      />
                    </div>
                  </div>
                </div>

                <div class="mt-4 shrink-0 sm:ml-5 sm:mt-0">
                  <div class="flex space-x-6">
                    <IFormSwitchField v-if="emailAccounts.length > 1">
                      <IFormSwitchLabel
                        :text="$t('mailclient::mail.account.is_primary')"
                      />

                      <IFormSwitch
                        :disabled="account.is_sync_stopped"
                        :model-value="account.is_primary"
                        @change="togglePrimaryState($event, account)"
                      />
                    </IFormSwitchField>

                    <IFormSwitchField
                      v-show="
                        !account.is_sync_stopped &&
                        account.authorizations.update
                      "
                    >
                      <IFormSwitchLabel
                        :text="$t('mailclient::mail.disable_sync')"
                      />

                      <IFormSwitch
                        :model-value="account.is_sync_disabled"
                        @change="toggleDisabledSyncState(account)"
                      />
                    </IFormSwitchField>
                  </div>
                </div>
              </div>

              <IDropdownMinimal
                v-if="
                  account.authorizations.update || account.authorizations.delete
                "
                class="ml-5 shrink-0 self-start sm:self-auto"
              >
                <IDropdownItem
                  v-if="account.authorizations.update"
                  :text="$t('core::app.edit')"
                  @click="redirectToAccountEdit(account)"
                />

                <IDropdownItem
                  v-if="account.authorizations.delete"
                  :text="$t('core::app.delete')"
                  @click="destroy(account.id)"
                />
              </IDropdownMinimal>
            </div>
          </li>
        </TransitionGroup>
      </ICard>
    </div>

    <IOverlay
      v-if="!emailAccounts.length"
      class="m-auto max-w-5xl"
      :show="emailAccountsBeingLoaded"
    >
      <div
        v-show="!emailAccountsBeingLoaded"
        class="mx-auto max-w-2xl p-4 sm:p-6"
      >
        <ITextDisplay
          :text="$t('mailclient.mail.account.no_accounts_configured')"
        />

        <IText
          :text="$t('mailclient.mail.account.no_accounts_configured_info')"
        />

        <ul
          role="list"
          class="mt-6 grid grid-cols-1 gap-6 border-y border-neutral-200 py-6 dark:border-neutral-500/30 lg:grid-cols-2"
        >
          <li
            v-for="(item, itemIdx) in emptyStateItems"
            :key="itemIdx"
            class="flow-root"
          >
            <div
              class="relative -m-2 flex items-center space-x-4 rounded-xl p-2"
            >
              <div
                class="flex h-10 w-12 shrink-0 items-center justify-center rounded-lg border border-primary-200 bg-primary-50 dark:border-primary-400/30 dark:bg-primary-400/10"
              >
                <Icon
                  class="size-6 text-primary-600 dark:text-primary-400"
                  :icon="item.icon"
                />
              </div>

              <div>
                <IText class="mt-1" :text="item.description" />
              </div>
            </div>
          </li>
        </ul>

        <div class="mt-6 space-y-1 sm:space-x-2 sm:text-right">
          <span
            v-i-tooltip="
              $t(
                $gate.isRegularUser()
                  ? 'users::user.not_authorized'
                  : 'mailclient::mail.account.create_shared_info'
              )
            "
            class="inline-block w-full sm:w-auto"
          >
            <IButton
              class="sm:w-justify-around w-full justify-center sm:w-auto"
              variant="primary"
              icon="Share"
              :disabled="$gate.isRegularUser()"
              :text="$t('mailclient::mail.account.connect_shared')"
              @click="createShared"
            />
          </span>

          <IButton
            class="sm:w-justify-around w-full justify-center sm:w-auto"
            variant="primary"
            icon="User"
            :text="$t('mailclient::mail.account.connect_personal')"
            @click="createPersonal"
          />
        </div>
      </div>
    </IOverlay>

    <RouterView />
  </MainLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'

import GoogleIcon from '@/Core/components/Icons/GoogleIcon.vue'
import OutlookIcon from '@/Core/components/Icons/OutlookIcon.vue'

import { useEmailAccounts } from '../../composables/useEmailAccounts'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const store = useStore()

const {
  emailAccounts,
  emailAccountsBeingLoaded,
  latestEmailAccount,
  fetchEmailAccounts,
  createOAuthConnectUrl,
} = useEmailAccounts()

const emptyStateItems = [
  {
    description: t('mailclient::mail.account.featured.sync'),
    icon: 'Refresh',
  },
  {
    description: t('mailclient::mail.account.featured.save_time'),
    icon: 'DocumentAdd',
  },
  {
    description: t('mailclient::mail.account.featured.placeholders'),
    icon: 'CodeBracket',
  },
  {
    description: t('mailclient::mail.account.featured.signature'),
    icon: 'Pencil',
  },
  {
    description: t('mailclient::mail.account.featured.associations', {
      resources:
        t('contacts::contact.contacts') +
        ', ' +
        t('contacts::company.companies'),
      resource: t('deals::deal.deals'),
    }),
    icon: 'Mail',
  },
  {
    description: t('mailclient::mail.account.featured.types'),
    icon: 'CheckCircle',
  },
]

async function destroy(id) {
  await Innoclapps.confirm(t('mailclient::mail.account.delete_warning'))
  await Innoclapps.request().delete(`/mail/accounts/${id}`)

  store.commit('emailAccounts/REMOVE', id)
  Innoclapps.success(t('mailclient::mail.account.deleted'))
}

function createShared() {
  Innoclapps.confirm({
    message: t('mailclient::mail.account.create_shared_confirmation_message'),
    title: false,
    icon: 'QuestionMarkCircle',
    iconWrapperColorClass: 'bg-info-100',
    iconColorClass: 'text-info-400',
    html: true,
    confirmText: t('core::app.continue'),
    confirmVariant: 'info',
  }).then(() =>
    router.push({
      name: 'create-email-account',
      query: {
        type: 'shared',
      },
    })
  )
}

function createPersonal() {
  router.push({
    name: 'create-email-account',
    query: {
      type: 'personal',
    },
  })
}

function redirectToAccountEdit(account) {
  router.push({
    name: 'edit-email-account',
    params: { id: account.id },
  })
}

function togglePrimaryState(isPrimary, account) {
  if (isPrimary) {
    store.dispatch('emailAccounts/setPrimary', account.id)
  } else {
    store.dispatch('emailAccounts/removePrimary')
  }
}

function toggleDisabledSyncState(account) {
  let action = account.is_sync_disabled ? 'enable' : 'disable'
  store.dispatch(`emailAccounts/${action}Sync`, account.id)
}

function reAuthenticate(account) {
  if (account.connection_type === 'Imap') {
    redirectToAccountEdit(account)
  } else {
    window.location.href =
      createOAuthConnectUrl(account.connection_type, account.type) +
      '?re_auth=1'
  }
}

fetchEmailAccounts().finally(() => {
  if (route.query.viaOAuth) {
    redirectToAccountEdit(latestEmailAccount.value)
  }
})
</script>
