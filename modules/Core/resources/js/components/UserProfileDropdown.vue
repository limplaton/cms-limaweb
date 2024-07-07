<template>
  <!-- Profile dropdown -->
  <div class="relative mt-4 inline-block px-3 text-left">
    <IDropdown v-slot="{ hide }" placement="bottom-start">
      <IDropdownButton
        as="button"
        class="group mt-3 w-full rounded-md bg-neutral-200 px-3.5 py-2 text-left text-base font-medium text-neutral-700 ring-1 ring-neutral-200 hover:bg-neutral-300 focus:bg-neutral-300 focus:outline-none dark:bg-neutral-800 dark:ring-neutral-500/30 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700 sm:text-sm"
        no-caret
      >
        <span class="flex w-full items-center justify-between">
          <span class="flex min-w-0 items-center justify-between space-x-3">
            <IAvatar
              size="md"
              :src="currentUser.avatar_url"
              :title="currentUser.name"
            />

            <span class="flex min-w-0 flex-1 flex-col">
              <span
                class="truncate text-base font-medium text-neutral-800 dark:text-white sm:text-sm"
              >
                {{ currentUser.name }}
              </span>

              <span
                class="truncate text-base text-neutral-600 dark:text-neutral-300 sm:text-sm"
              >
                {{ currentUser.email }}
              </span>
            </span>
          </span>

          <Icon
            icon="Selector"
            class="size-5 shrink-0 text-neutral-500 group-hover:text-neutral-600 dark:text-neutral-400 dark:group-hover:text-neutral-300"
          />
        </span>
      </IDropdownButton>

      <IDropdownMenu class="sm:max-w-[200px]">
        <template v-if="currentUser.teams.length > 0">
          <div class="px-3 py-2">
            <ITextBlockDark class="inline-flex items-center">
              <Icon icon="Users" class="mr-1 size-5" />

              {{ $t('users::team.your_teams', currentUser.teams.length) }}
            </ITextBlockDark>

            <ITextDark
              v-for="team in currentUser.teams"
              :key="team.id"
              class="flex font-medium"
            >
              <span
                :class="[
                  'truncate',
                  team.user_id === currentUser.id
                    ? 'text-primary-600 dark:text-primary-400'
                    : '',
                ]"
                v-text="team.name"
              />
            </ITextDark>
          </div>

          <IDropdownSeparator />
        </template>

        <IDropdownItem
          icon="User"
          :to="{ name: 'profile' }"
          :text="$t('users::profile.profile')"
          @click="hide"
        />

        <IDropdownItem
          icon="Calendar"
          :to="{ name: 'calendar-sync' }"
          :text="$t('activities::calendar.calendar_sync')"
          @click="hide"
        />

        <IDropdownItem
          icon="RocketLaunch"
          class="truncate"
          :to="{ name: 'oauth-accounts' }"
          :text="$t('core::oauth.connected_accounts')"
          @click="hide"
        />

        <IDropdownItem
          v-if="currentUser.access_api"
          icon="Globe"
          :to="{ name: 'personal-access-tokens' }"
          :text="$t('core::api.access_tokens')"
          @click="hide"
        />

        <IDropdownSeparator />

        <IDropdownItem
          icon="ArrowLeftStartOnRectangle"
          href="#"
          :text="$t('auth.logout')"
          @click="logout(), hide()"
        />
      </IDropdownMenu>
    </IDropdown>
  </div>
</template>

<script setup>
import { useApp } from '../composables/useApp'

const { currentUser, logout } = useApp()
</script>
