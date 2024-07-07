<template>
  <MainLayout>
    <div class="m-auto max-w-7xl">
      <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="space-y-1 md:col-span-1">
          <ITextDisplay :text="$t('core::app.avatar')" />

          <IText :text="$t('users::profile.avatar_info')" />
        </div>

        <div class="mt-2 md:col-span-2">
          <ICard>
            <ICardBody>
              <CropsAndUploadsImage
                name="avatar"
                :upload-url="`${$scriptConfig('apiURL')}/users/${
                  currentUser.id
                }/avatar`"
                :image="currentUser.uploaded_avatar_url"
                :cropper-options="{ aspectRatio: 1 / 1 }"
                :choose-text="
                  currentUser.uploaded_avatar_url
                    ? $t('core::app.change')
                    : $t('core::app.upload_avatar')
                "
                @cleared="clearAvatar"
                @success="handleAvatarUploaded"
              />
            </ICardBody>
          </ICard>
        </div>
      </div>

      <div class="hidden sm:block" aria-hidden="true">
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-500/30" />
        </div>
      </div>

      <div class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="space-y-1 md:col-span-1">
            <ITextDisplay :text="$t('users::profile.profile')" />

            <IText :text="$t('users::profile.profile_info')" />
          </div>

          <div class="mt-5 md:col-span-2 md:mt-0">
            <ICard as="form" @submit.prevent="update">
              <ICardBody>
                <IFormGroup label-for="name" :label="$t('users::user.name')">
                  <IFormInput id="name" v-model="form.name" name="name" />

                  <IFormError :error="form.getError('name')" />
                </IFormGroup>

                <IFormGroup label-for="email" :label="$t('users::user.email')">
                  <IFormInput
                    id="email"
                    v-model="form.email"
                    name="email"
                    type="email"
                  >
                  </IFormInput>

                  <IFormError :error="form.getError('email')" />
                </IFormGroup>

                <IFormGroup
                  label-for="mail_signature"
                  :label="$t('mailclient::mail.signature')"
                  :description="
                    currentUser.mail_signature
                      ? $t('mailclient::mail.signature_info')
                      : ''
                  "
                >
                  <MailEditor
                    v-model="form.mail_signature"
                    :placeholder="$t('mailclient::mail.signature_info')"
                  />

                  <IFormError :error="form.getError('mail_signature')" />
                </IFormGroup>
              </ICardBody>

              <ICardFooter class="text-right">
                <IButton
                  type="submit"
                  variant="primary"
                  :disabled="form.busy"
                  :text="$t('users::profile.update')"
                  @click="update"
                />
              </ICardFooter>
            </ICard>
          </div>
        </div>
      </div>

      <div class="hidden sm:block" aria-hidden="true">
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-500/30" />
        </div>
      </div>

      <div class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="space-y-1 md:col-span-1">
            <ITextDisplay :text="$t('users::user.localization')" />

            <IText :text="$t('users::profile.localization_info')" />
          </div>

          <div class="mt-5 md:col-span-2 md:mt-0">
            <ICard>
              <ICardBody>
                <LocalizationInputs
                  :form="form"
                  @update:time-format="form.time_format = $event"
                  @update:date-format="form.date_format = $event"
                  @update:locale="form.locale = $event"
                  @update:timezone="form.timezone = $event"
                />
              </ICardBody>

              <ICardFooter class="text-right">
                <IButton
                  variant="primary"
                  :disabled="form.busy"
                  :text="$t('core::app.save')"
                  @click="update"
                />
              </ICardFooter>
            </ICard>
          </div>
        </div>
      </div>

      <div class="hidden sm:block" aria-hidden="true">
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-500/30" />
        </div>
      </div>

      <div class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="space-y-1 md:col-span-1">
            <ITextDisplay :text="$t('core::notifications.notifications')" />

            <IText :text="$t('users::profile.notifications_info')" />
          </div>

          <div class="mt-5 md:col-span-2 md:mt-0">
            <ICard id="notifications">
              <NotificationSettings
                v-model="form.notifications_settings"
                class="-mt-px"
              />

              <ICardFooter class="text-right">
                <IButton
                  variant="primary"
                  :disabled="form.busy"
                  :text="$t('core::app.save')"
                  @click="update"
                />
              </ICardFooter>
            </ICard>
          </div>
        </div>
      </div>

      <div class="hidden sm:block" aria-hidden="true">
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-500/30" />
        </div>
      </div>

      <div class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="space-y-1 md:col-span-1">
            <ITextDisplay :text="$t('auth.password')" />

            <IText :text="$t('users::profile.password_info')" />
          </div>

          <div class="mt-5 md:col-span-2 md:mt-0">
            <ICard as="form" @submit.prevent="updatePassword">
              <ICardBody>
                <IFormGroup
                  label-for="old_password"
                  :label="$t('auth.current_password')"
                >
                  <IFormInput
                    id="old_password"
                    v-model="formPassword.old_password"
                    name="old_password"
                    type="password"
                    autocomplete="current-password"
                  >
                  </IFormInput>

                  <IFormError :error="formPassword.getError('old_password')" />
                </IFormGroup>

                <IFormGroup>
                  <template #label>
                    <div class="flex">
                      <IFormLabel
                        class="mb-1 grow"
                        for="password"
                        :label="$t('auth.new_password')"
                      />

                      <ILink
                        :text="$t('core::app.password_generator.heading')"
                        @click="showGeneratePassword = !showGeneratePassword"
                      />
                    </div>
                  </template>

                  <IFormInput
                    id="password"
                    v-model="formPassword.password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                  >
                  </IFormInput>

                  <IFormError :error="formPassword.getError('password')" />
                </IFormGroup>

                <IFormGroup
                  label-for="password_confirmation"
                  :label="$t('auth.confirm_password')"
                >
                  <IFormInput
                    id="password_confirmation"
                    v-model="formPassword.password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                  >
                  </IFormInput>

                  <IFormError
                    :error="formPassword.getError('password_confirmation')"
                  />
                </IFormGroup>

                <PasswordGenerator v-show="showGeneratePassword" />
              </ICardBody>

              <ICardFooter class="text-right">
                <IButton
                  type="submit"
                  variant="primary"
                  :disabled="formPassword.busy"
                  :text="$t('auth.change_password')"
                />
              </ICardFooter>
            </ICard>
          </div>
        </div>
      </div>

      <div
        v-if="managedTeams.length > 0"
        class="hidden sm:block"
        aria-hidden="true"
      >
        <div class="py-5">
          <div class="border-t border-neutral-200 dark:border-neutral-500/30" />
        </div>
      </div>

      <div v-if="managedTeams.length > 0" class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
          <div class="space-y-1 md:col-span-1">
            <ITextDisplay
              :text="$t('users::team.your_teams', managedTeams.length)"
            />

            <IText :text="$t('users::team.managing_teams')" />
          </div>

          <div class="mt-5 md:col-span-2 md:mt-0">
            <ICard>
              <ICardBody>
                <ul
                  role="list"
                  class="space-y-4 divide-y divide-neutral-200 dark:divide-neutral-500/30"
                >
                  <li
                    v-for="team in managedTeams"
                    :key="team.id"
                    class="pt-4 first:pt-0"
                  >
                    <ITextDark class="truncate font-medium" :text="team.name" />

                    <IText class="my-2" :text="$t('users::team.members')" />

                    <div
                      v-for="member in team.members"
                      :key="'info-' + member.email"
                      class="mb-1 flex items-center space-x-1.5 last:mb-0"
                    >
                      <IAvatar :alt="member.name" :src="member.avatar_url" />

                      <IText :text="member.name" />
                    </div>
                  </li>
                </ul>
              </ICardBody>
            </ICard>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, ref, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStore } from 'vuex'
import reduce from 'lodash/reduce'

import LocalizationInputs from '@/Core/components/LocalizationInputs.vue'
import PasswordGenerator from '@/Core/components/PasswordGenerator.vue'
import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

import MailEditor from '@/MailClient/components/MailEditor.vue'

import NotificationSettings from '../components/UserNotificationSettings.vue'
import { useTeams } from '../composables/useTeams'

const { t } = useI18n()
const store = useStore()
const { currentUser, resetStoreState } = useApp()

const userPlain = unref(currentUser)

const { form } = useForm()
const { form: formPassword } = useForm({}, { resetOnSuccess: true })

const { teams } = useTeams()

const showGeneratePassword = ref(false)

const managedTeams = computed(() =>
  teams.value.filter(team => team.manager.id === currentUser.value.id)
)

let originalLocale = null

function handleAvatarUploaded(updatedUser) {
  store.commit('users/UPDATE', {
    id: updatedUser.id,
    item: updatedUser,
  })

  userPlain.avatar = updatedUser.avatar
  userPlain.avatar_url = updatedUser.avatar_url
  // Update form avatar with new value
  // to prevent using the old value if the user saves the profile
  form.avatar = userPlain.avatar
}

function clearAvatar() {
  if (!userPlain.avatar) {
    return
  }

  Innoclapps.request()
    .delete(`/users/${userPlain.id}/avatar`)
    .then(({ data }) => {
      form.avatar = data.avatar
      store.commit('users/UPDATE', { id: userPlain.id, item: data })
    })
}

function update() {
  form.put('/profile').then(user => {
    store.commit('users/UPDATE', { id: user.id, item: user })

    Innoclapps.success(t('users::profile.updated'))

    if (originalLocale !== form.locale) {
      window.location.reload()
    } else {
      resetStoreState()
    }
  })
}

function updatePassword() {
  formPassword.put('/profile/password').then(() => {
    Innoclapps.success(t('users::profile.password_updated'))
  })
}

function prepareComponent() {
  originalLocale = userPlain.locale

  form.set({
    name: userPlain.name,
    email: userPlain.email,
    mail_signature: userPlain.mail_signature,
    date_format: userPlain.date_format,
    time_format: userPlain.time_format,
    timezone: userPlain.timezone,
    locale: userPlain.locale,
    notifications_settings: reduce(
      userPlain.notifications.settings,
      (obj, val) => {
        obj[val.key] = val.availability

        return obj
      },
      {}
    ),
  })

  formPassword.set({
    old_password: null,
    password: null,
    password_confirmation: null,
  })
}

prepareComponent()
</script>
