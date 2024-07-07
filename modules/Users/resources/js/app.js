
import { useNotification } from '@/Core/composables/useBroadcast'
import { emitGlobal } from '@/Core/composables/useGlobalEventListener'
import { GatePlugin } from '@/Core/gate'
import { translate } from '@/Core/i18n'

import UserInvitationAcceptForm from './components/UserInvitationAcceptForm.vue'
import UsersStore from './store/Users'
import UsersPersonalAccessTokens from './views/UsersPersonalAccessTokens.vue'
import UsersProfile from './views/UsersProfile.vue'
import settingsRoutes from './settingsRoutes'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router, store) {
    const users = this.scriptConfig('users') || []
    const currentUserId = this.scriptConfig('user_id')

    app.component('UserInvitationAcceptForm', UserInvitationAcceptForm)

    store.registerModule('users', UsersStore)

    store.commit('users/SET', users)

    app.use(GatePlugin, store.getters['users/current'])

    router.beforeEach((to, from, next) => {
      const onlySuperAdminRoute = to.matched.find(
        match => match.meta.superAdmin
      )

      if (
        onlySuperAdminRoute &&
        onlySuperAdminRoute.meta.superAdmin === true &&
        !store.getters['users/current'].super_admin
      ) {
        next({ path: '/403' })
      } else {
        next()
      }
    })

    if (currentUserId) {
      useNotification(currentUserId, notification => {
        this.request(`/notifications/${notification.id}`).then(({ data }) =>
          emitGlobal('new-notification', data)
        )
      })
    }

    settingsRoutes.forEach(route => router.addRoute('settings', route))

    router.addRoute({
      path: '/profile',
      name: 'profile',
      component: UsersProfile,
      meta: {
        title: translate('users::profile.profile'),
      },
    })

    router.addRoute({
      path: '/personal-access-tokens',
      name: 'personal-access-tokens',
      component: UsersPersonalAccessTokens,
      meta: {
        title: translate('core::api.personal_access_tokens'),
      },
    })
  })
}
