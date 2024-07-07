<template>
  <IModal
    id="calendarConnectNewAccountModal"
    size="sm"
    :title="$t('core::oauth.connect_new_account')"
    :sub-title="$t('activities::calendar.choose_oauth_account')"
    hide-footer
  >
    <div class="flex justify-center space-x-2 py-3">
      <template v-for="integration in integrations" :key="integration.id">
        <button
          v-if="integration.configured"
          type="button"
          :class="[
            'text-base text-neutral-800 dark:text-neutral-200 sm:text-sm',
            connectClasses,
          ]"
          @click="connectOAuthAccount(integration.id)"
        >
          <component :is="integration.icon" class="mb-2" />
          {{ integration.title }}
        </button>

        <IPopover v-else placement="top">
          <IPopoverButton
            as="button"
            :class="[
              'text-base text-neutral-800 dark:text-neutral-200 sm:text-sm',
              connectClasses,
            ]"
          >
            <component :is="integration.icon" class="mb-2" />
            {{ integration.title }}
          </IPopoverButton>

          <IPopoverPanel class="max-w-xs sm:max-w-sm">
            <IPopoverBody>
              <IText class="whitespace-pre-wrap">
                {{ $t(integration.popoverContentLangKey) }}

                <ILink
                  v-if="$gate.isSuperAdmin()"
                  class="mt-2 block text-right"
                  :text="$t('core::settings.go_to_settings')"
                  :to="{ name: integration.settingsRouteName }"
                />
              </IText>
            </IPopoverBody>
          </IPopoverPanel>
        </IPopover>
      </template>
    </div>
  </IModal>
</template>

<script setup>
import GoogleIcon from '@/Core/components/Icons/GoogleIcon.vue'
import OutlookIcon from '@/Core/components/Icons/OutlookIcon.vue'
import { useApp } from '@/Core/composables/useApp'

const { isGoogleApiConfigured, isMicrosoftGraphConfigured, scriptConfig } =
  useApp()

const connectClasses =
  'flex flex-col items-center space-y-1 rounded-lg border border-neutral-200 px-5 py-3 shadow-sm hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800'

const integrations = [
  {
    id: 'google',
    title: 'Google Calendar',
    icon: GoogleIcon,
    settingsRouteName: 'settings-integrations-google',
    popoverContentLangKey: 'activities::calendar.missing_google_integration',
    configured: isGoogleApiConfigured(),
  },
  {
    id: 'microsoft',

    title: 'Outlook Calendar',
    icon: OutlookIcon,
    settingsRouteName: 'settings-integrations-microsoft',
    popoverContentLangKey: 'activities::calendar.missing_outlook_integration',
    configured: isMicrosoftGraphConfigured(),
  },
]

function connectOAuthAccount(provider) {
  window.location.href = `${scriptConfig(
    'url'
  )}/calendar/sync/${provider}/connect`
}
</script>
