<template>
  <MainLayout>
    <div class="mx-auto max-w-5xl">
      <ICardHeader>
        <ICardHeading :text="$t('core::notifications.notifications')" />

        <ICardActions>
          <IButton
            v-show="total > 0"
            variant="secondary"
            :loading="requestInProgress"
            :disabled="!hasUnreadNotifications"
            :text="$t('core::notifications.mark_all_as_read')"
            small
            @click="markAllRead"
          />
        </ICardActions>
      </ICardHeader>

      <ICard>
        <ul>
          <li
            v-for="(notification, index) in notifications"
            :key="notification.id"
            class="border-b border-neutral-200 dark:border-neutral-500/30"
          >
            <ILink
              class="block hover:bg-neutral-50 dark:hover:bg-neutral-700/60"
              :to="notification.data.path"
              plain
            >
              <div class="flex items-center px-4 py-4 sm:px-6">
                <div
                  class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between"
                >
                  <div class="truncate">
                    <ITextDark
                      class="truncate font-medium"
                      :text="localizeNotification(notification)"
                    />

                    <IText :text="localizedDateTime(notification.created_at)" />
                  </div>
                </div>

                <IButton
                  class="ml-5 shrink-0 self-start"
                  icon="Trash"
                  basic
                  @click.prevent.stop="destroy(index)"
                />
              </div>
            </ILink>
          </li>
        </ul>

        <InfinityLoader load-when-mounted @handle="loadHandler" />

        <ICardBody
          v-show="total === 0 && atLeastOnePageLoaded"
          class="text-center"
        >
          <Icon icon="EmojiSad" class="mx-auto size-12 text-neutral-400" />

          <IText
            class="mt-2"
            :text="$t('core::notifications.no_notifications')"
          />
        </ICardBody>

        <IText
          v-show="noMoreResults && total > 0"
          class="pb-3 text-center"
          :text="$t('core::notifications.no_more_notifications')"
        />
      </ICard>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'

import InfinityLoader from '@/Core/components/InfinityLoader.vue'
import { useDates } from '@/Core/composables/useDates'
import { useGlobalEventListener } from '@/Core/composables/useGlobalEventListener'
import { useNotifications } from '@/Core/composables/useNotifications'

const {
  localizeNotification,
  decrementTotalUnreadNotifications,
  markAllNotificationsRead,
  hasUnreadNotifications,
  fetchNotifications,
} = useNotifications()

const { localizedDateTime } = useDates()

const notifications = ref([])
const noMoreResults = ref(false)
const nextPage = ref(1)
const requestInProgress = ref(false)

const total = computed(() => notifications.value.length)
const atLeastOnePageLoaded = computed(() => nextPage.value > 1)

function markAllRead() {
  requestInProgress.value = true

  markAllNotificationsRead().finally(() => {
    requestInProgress.value = false

    notifications.value.forEach(n => {
      n.read_at = new Date().toISOString()
    })
  })
}

async function destroy(index) {
  await Innoclapps.confirm()

  const notification = notifications.value[index]
  await Innoclapps.request().delete(`/notifications/${notification.id}`)

  if (!notification.read_at && hasUnreadNotifications.value) {
    decrementTotalUnreadNotifications()
  }

  notifications.value.splice(index, 1)
}

function addNotifications(newNotifications) {
  // Filter out notifications that already exist
  const uniqueNotifications = newNotifications.filter(
    newNotification =>
      !notifications.value.some(
        existingNotification => existingNotification.id === newNotification.id
      )
  )

  // Add the unique notifications to the existing array
  notifications.value.push(...uniqueNotifications)
}

async function loadHandler($state) {
  const { data: pagination } = await fetchNotifications({
    params: {
      page: nextPage.value,
    },
  })

  addNotifications(pagination.data)

  await nextTick()

  if (pagination.total === total.value) {
    noMoreResults.value = true
    $state.complete()
  }

  nextPage.value += 1
  $state.loaded()
}

/**
 * Useful when the user is located at this view when a new notification is broadcasted.
 */
useGlobalEventListener('new-notification', notification =>
  notifications.value.unshift(notification)
)
</script>
