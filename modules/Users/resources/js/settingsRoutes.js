
import { translate } from '@/Core/i18n'
import RolesCreate from '@/Core/views/Roles/RolesCreate.vue'
import RolesEdit from '@/Core/views/Roles/RolesEdit.vue'
import RolesIndex from '@/Core/views/Roles/RolesIndex.vue'

import SettingsManageUsers from './components/SettingsManageUsers.vue'
import UsersCreate from './views/UsersCreate.vue'
import UsersEdit from './views/UsersEdit.vue'
import UsersInvite from './views/UsersInvite.vue'
import UsersManageTeams from './views/UsersManageTeams.vue'

export default [
  {
    path: 'users',
    component: SettingsManageUsers,
    name: 'users-index',
    meta: {
      title: translate('users::user.users'),
      superAdmin: true,
    },
    children: [
      {
        path: 'create',
        name: 'create-user',
        components: {
          createEdit: UsersCreate,
        },
        meta: { title: translate('users::user.create') },
      },
      {
        path: ':id/edit',
        name: 'edit-user',
        components: {
          createEdit: UsersEdit,
        },
        meta: { title: translate('users::user.edit') },
      },
      {
        path: 'invite',
        name: 'invite-user',
        components: {
          invite: UsersInvite,
        },
        meta: { title: translate('users::user.invite') },
      },
      {
        path: 'roles',
        name: 'role-index',
        components: {
          roles: RolesIndex,
        },
        meta: {
          title: translate('core::role.roles'),
        },
        children: [
          {
            path: 'create',
            name: 'create-role',
            component: RolesCreate,
            meta: { title: translate('core::role.create') },
          },
          {
            path: ':id/edit',
            name: 'edit-role',
            component: RolesEdit,
            meta: { title: translate('core::role.edit') },
          },
        ],
      },
      {
        path: 'teams',
        name: 'manage-teams',
        components: {
          teams: UsersManageTeams,
        },
        meta: {
          title: translate('users::team.teams'),
        },
      },
    ],
  },
]
