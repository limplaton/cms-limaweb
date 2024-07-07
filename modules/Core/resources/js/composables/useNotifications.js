
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStore } from 'vuex'

import { useApp } from './useApp'

export function useNotifications() {
  const { currentUser } = useApp()
  const { t } = useI18n()
  const store = useStore()
  const notificationsBeingLoaded = ref(false)

  const totalUnreadNotifications = computed(
    () => currentUser.value.notifications.unread_count
  )

  const hasUnreadNotifications = computed(
    () => totalUnreadNotifications.value > 0
  )

  /**
   * Increment the total unread notifications for the current user.
   */
  function incrementTotalUnreadNotifications() {
    store.commit('users/INCREMENT_TOTAL_UNREAD_NOTIFICATIONS')
  }

  /**
   * Decrement the total unread notifications for the current user.
   */
  function decrementTotalUnreadNotifications() {
    store.commit('users/DECREMENT_TOTAL_UNREAD_NOTIFICATIONS')
  }

  /**
   * Fetch the current user notifications.
   *
   * @param {Object} config
   * @returns Promise
   */
  async function fetchNotifications(config) {
    notificationsBeingLoaded.value = true

    try {
      return await Innoclapps.request('/notifications', config)
    } finally {
      notificationsBeingLoaded.value = false
    }
  }

  /**
   * Localize the given notification.
   * @param {Object} notification
   * @returns {string}
   */
  function localizeNotification(notification) {
    if (notification.data.lang) {
      return t(notification.data.lang.key, notification.data.lang.attrs)
    }

    return notification.data.message
  }

  /**
   * Mark all notifications are read for the current user.
   */
  async function markAllNotificationsRead() {
    if (currentUser.value.notifications.unread_count > 0) {
      await Innoclapps.request().put('/notifications')

      store.commit('users/SET_TOTAL_UNREAD_NOTIFICATIONS', 0)
    }
  }

  /**
   * Mark notification as read the current user.
   */
  async function markNotificationAsRead(id) {
    await Innoclapps.request().put(`/notifications/${id}`)
    decrementTotalUnreadNotifications()
  }

  return {
    totalUnreadNotifications,
    hasUnreadNotifications,

    notificationsBeingLoaded,
    fetchNotifications,

    incrementTotalUnreadNotifications,
    decrementTotalUnreadNotifications,

    localizeNotification,
    markAllNotificationsRead,
    markNotificationAsRead,
  }
}
