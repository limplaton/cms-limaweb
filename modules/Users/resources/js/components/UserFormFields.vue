<template>
  <ITabGroup>
    <ITabList fill>
      <ITab :title="$t('users::user.user')" />

      <ITab :title="$t('auth.password')" />

      <ITab :title="$t('users::user.localization')" />

      <ITab :title="$t('core::notifications.notifications')" />

      <ITab :title="$t('core::app.advanced')" />
    </ITabList>

    <ITabPanels>
      <ITabPanel>
        <IFormGroup label-for="name" :label="$t('users::user.name')" required>
          <IFormInput
            id="name"
            ref="name"
            type="text"
            autocomplete="off"
            :model-value="name"
            @update:model-value="$emit('update:name', $event)"
          >
          </IFormInput>

          <IFormError :error="form.getError('name')" />
        </IFormGroup>

        <IFormGroup label-for="email" :label="$t('users::user.email')" required>
          <IFormInput
            id="email"
            name="email"
            type="email"
            autocomplete="off"
            :model-value="email"
            @update:model-value="$emit('update:email', $event)"
          >
          </IFormInput>

          <IFormError :error="form.getError('email')" />
        </IFormGroup>

        <IFormGroup label-for="roles" :label="$t('core::role.roles')">
          <ICustomSelect
            input-id="roles"
            :model-value="roles"
            :placeholder="$t('users::user.roles')"
            :options="rolesNames"
            :multiple="true"
            @update:model-value="$emit('update:roles', $event)"
          />
        </IFormGroup>
      </ITabPanel>

      <ITabPanel>
        <IFormGroup
          label-for="password"
          :label="$t('auth.password')"
          :required="!inEditMode"
        >
          <IFormInput
            id="password"
            name="password"
            type="password"
            autocomplete="new-password"
            :model-value="password"
            @update:model-value="$emit('update:password', $event)"
          >
          </IFormInput>

          <IFormError :error="form.getError('password')" />
        </IFormGroup>

        <IFormGroup
          label-for="password_confirmation"
          :label="$t('auth.confirm_password')"
          :required="!inEditMode || Boolean(password)"
        >
          <IFormInput
            id="password_confirmation"
            name="password_confirmation"
            autocomplete="new-password"
            type="password"
            :model-value="passwordConfirmation"
            @update:model-value="$emit('update:passwordConfirmation', $event)"
          >
          </IFormInput>

          <IFormError :error="form.getError('password_confirmation')" />
        </IFormGroup>

        <PasswordGenerator />
      </ITabPanel>

      <ITabPanel>
        <LocalizationInputs
          :form="form"
          :time-format="timeFormat"
          :date-format="dateFormat"
          :locale="locale"
          :timezone="timezone"
          @update:time-format="$emit('update:timeFormat', $event)"
          @update:date-format="$emit('update:dateFormat', $event)"
          @update:locale="$emit('update:locale', $event)"
          @update:timezone="$emit('update:timezone', $event)"
        />
      </ITabPanel>
    </ITabPanels>

    <ITabPanel>
      <NotificationSettings
        class="overflow-hidden rounded-lg"
        :model-value="notificationsSettings"
        bordered
        @update:model-value="$emit('update:notificationsSettings', $event)"
      />
    </ITabPanel>

    <ITabPanel>
      <div
        :class="[
          'flex items-center rounded-lg border-2 px-5 py-4 shadow-sm',
          form.super_admin
            ? 'border-primary-400'
            : 'border-neutral-200 dark:border-neutral-500/30',
        ]"
      >
        <IFormSwitchField>
          <IFormSwitchLabel :text="$t('users::user.super_admin')" />

          <IFormSwitchDescription
            :text="$t('users::user.as_super_admin_info')"
          />

          <IFormSwitch
            :model-value="superAdmin"
            :disabled="currentUserIsSuperAdmin"
            @update:model-value="$emit('update:superAdmin', $event)"
            @change="handleSuperAdminChange"
          />
        </IFormSwitchField>
      </div>

      <div
        :class="[
          'mt-3 flex items-center rounded-lg border-2 px-5 py-4 shadow-sm',
          form.access_api
            ? 'border-primary-400'
            : 'border-neutral-200 dark:border-neutral-500/30',
        ]"
      >
        <IFormSwitchField>
          <IFormSwitchLabel :text="$t('users::user.enable_api')" />

          <IFormSwitchDescription :text="$t('users::user.allow_api_info')" />

          <IFormSwitch
            :model-value="accessApi"
            :disabled="currentUserIsSuperAdmin || form.super_admin"
            @update:model-value="$emit('update:accessApi', $event)"
          />
        </IFormSwitchField>
      </div>
    </ITabPanel>
  </ITabGroup>
</template>

<script setup>
import { computed } from 'vue'

import LocalizationInputs from '@/Core/components/LocalizationInputs.vue'
import PasswordGenerator from '@/Core/components/PasswordGenerator.vue'
import { useApp } from '@/Core/composables/useApp'
import { useRoles } from '@/Core/composables/useRoles'

import NotificationSettings from '../components/UserNotificationSettings.vue'

const props = defineProps({
  inEditMode: Boolean,
  form: { required: true, type: Object },
  name: { required: true, type: String },
  email: { required: true, type: String },
  roles: { required: true, type: Array },
  password: { required: true, type: String },
  passwordConfirmation: { required: true, type: String },
  timezone: { required: true, type: String },
  locale: { required: true, type: String },
  dateFormat: { required: true, type: String },
  timeFormat: { required: true, type: String },
  notificationsSettings: { required: true, type: Object },
  superAdmin: { required: true, type: Boolean },
  accessApi: { required: true, type: Boolean },
})

const emit = defineEmits([
  'update:name',
  'update:email',
  'update:roles',
  'update:password',
  'update:passwordConfirmation',
  'update:timezone',
  'update:locale',
  'update:dateFormat',
  'update:timeFormat',
  'update:notificationsSettings',
  'update:superAdmin',
  'update:accessApi',
])

const { currentUser } = useApp()
const { rolesNames } = useRoles()

/**
 * Check whether the current logged in user is super admin
 * Checks the actual id, as if the user can access this component,
 * means that is admin as this component is intended only for admins
 */
const currentUserIsSuperAdmin = computed(
  () => currentUser.value.id === props.form.id
)

function handleSuperAdminChange(val) {
  if (val) {
    emit('update:accessApi', true)
  }
}
</script>
