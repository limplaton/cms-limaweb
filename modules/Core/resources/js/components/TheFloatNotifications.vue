<template>
  <NotificationGroup group="app">
    <div
      class="notifications pointer-events-none fixed inset-0 flex items-start justify-end px-8 py-6"
    >
      <div class="w-full max-w-sm">
        <Notification
          v-slot="{ notifications, close }"
          enter="ease-out duration-300 transition"
          enter-from="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
          enter-to="translate-y-0 opacity-100 sm:translate-x-0"
          leave="transition ease-in duration-100"
          leave-from="opacity-100"
          leave-to="opacity-0"
          move="transition duration-500"
          move-delay="delay-300"
        >
          <div
            v-for="(notification, index) in notifications"
            :key="index"
            class="notification pointer-events-auto relative mb-2 w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-neutral-800 dark:ring-neutral-500/40"
          >
            <div class="p-4">
              <div class="flex items-center">
                <div class="shrink-0">
                  <Icon
                    v-if="notification.type === 'success'"
                    icon="CheckCircle"
                    class="size-6 text-success-400"
                  />

                  <Icon
                    v-if="notification.type === 'info'"
                    icon="InformationCircle"
                    class="size-6 text-info-400"
                  />

                  <Icon
                    v-if="notification.type === 'error'"
                    icon="XCircleSolid"
                    class="size-6 text-danger-400"
                  />
                </div>

                <div class="ml-3 flex w-0 flex-1 justify-between">
                  <p
                    class="w-0 flex-1 text-base/6 font-medium text-neutral-800 dark:text-white sm:text-sm/6"
                    v-text="notification.text"
                  />

                  <ILink
                    v-if="notification.action"
                    class="ml-3 shrink-0"
                    :text="notification.action.text"
                    @click="handleActionClick(notification, close)"
                  />
                </div>

                <div class="shrink-0">
                  <IButton
                    class="ml-4"
                    icon="XSolid"
                    basic
                    small
                    @click="closeNotification(notification, close)"
                  />
                </div>
              </div>
            </div>
          </div>
        </Notification>
      </div>
    </div>
  </NotificationGroup>

  <NotificationGroup group="static" position="bottom">
    <div
      class="notifications pointer-events-none fixed inset-0 flex items-end justify-end px-8 py-6"
    >
      <div class="w-full max-w-sm">
        <Notification
          v-slot="{ notifications, close }"
          enter="ease-out duration-300 transition"
          enter-from="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
          enter-to="translate-y-0 opacity-100 sm:translate-x-0"
          leave="transition ease-in duration-100"
          leave-from="opacity-100"
          leave-to="opacity-0"
          move="transition duration-500"
          move-delay="delay-300"
        >
          <div
            v-for="(notification, index) in notifications"
            :key="index"
            class="notification pointer-events-auto relative mb-2 w-full max-w-sm overflow-hidden rounded-lg bg-neutral-900/90 ring-1 ring-neutral-700/70"
          >
            <div class="p-5">
              <div class="mb-3 text-sm text-neutral-100">
                {{ localizedDateTime(new Date()) }}
              </div>

              <div class="flex items-center space-x-2">
                <div class="shrink-0 self-start">
                  <icon icon="InformationCircle" class="size-6 text-info-400" />
                </div>

                <p
                  class="w-0 flex-1 text-sm font-medium text-neutral-100"
                  v-text="notification.text"
                />
              </div>

              <div class="mt-4 flex items-center space-x-1.5">
                <IButton
                  v-if="notification.action"
                  variant="primary"
                  :text="notification.action.text"
                  soft
                  block
                  @click="handleActionClick(notification, close)"
                />

                <IButton
                  variant="secondary"
                  :text="$t('core::notifications.dismiss')"
                  block
                  @click="closeNotification(notification, close)"
                />
              </div>
            </div>
          </div>
        </Notification>
      </div>
    </div>
  </NotificationGroup>
</template>

<script setup>
import { useDates } from '../composables/useDates'

const { localizedDateTime } = useDates()

function closeNotification(notification, close) {
  close(notification.id)

  if (typeof notification.closed === 'function') {
    notification.closed()
  }
}

function handleActionClick(notification, close) {
  if (notification.action.onClick() === true) {
    closeNotification(notification, close)
  }
}
</script>
