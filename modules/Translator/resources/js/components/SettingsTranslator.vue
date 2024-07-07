<template>
  <ICardHeader>
    <div class="flex items-center space-x-4">
      <ICardHeading :text="$t('translator::translator.translator')" />

      <div class="flex items-center space-x-2">
        <ITextDark class="font-medium" :text="$t('core::app.locale')" />

        <IDropdown>
          <IDropdownButton :text="selectedLocale" basic />

          <IDropdownMenu>
            <IDropdownItem
              v-for="locale in locales"
              :key="locale"
              :text="locale"
              :active="selectedLocale === locale"
              condensed
              @click="selectedLocale = locale"
            />
          </IDropdownMenu>
        </IDropdown>
      </div>
    </div>

    <ICardActions>
      <IButton
        v-dialog="'newLocaleModal'"
        variant="primary"
        icon="PlusSolid"
        :text="$t('translator::translator.new_locale')"
      />
    </ICardActions>
  </ICardHeader>

  <ICard>
    <ul class="divide-y divide-neutral-200 dark:divide-neutral-500/30">
      <li
        v-for="(groupTranslations, group) in translations.current.groups"
        v-show="!activeGroup || activeGroup === group"
        :key="group"
      >
        <div class="group hover:bg-neutral-100 dark:hover:bg-neutral-700/60">
          <div class="flex items-center">
            <div class="grow">
              <div class="px-6 py-2">
                <ILink
                  class="block font-medium group-hover:text-neutral-900"
                  :text="strTitle(group.replace('_', ' '))"
                  basic
                  @click="toggleGroup(group)"
                />
              </div>
            </div>

            <div class="ml-2 py-2 pr-6">
              <IButton
                variant="secondary"
                icon="ChevronDownSolid"
                small
                @click="toggleGroup(group)"
              />
            </div>
          </div>
        </div>

        <form
          v-show="activeGroup === group"
          novalidate="true"
          @submit.prevent="saveGroup(group)"
        >
          <div class="px-6">
            <ITable class="[--gutter:theme(spacing.6)]" bleed>
              <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
                <ITableRow>
                  <ITableHeader width="15%">Key</ITableHeader>

                  <ITableHeader width="30%">Source</ITableHeader>

                  <ITableHeader width="55%">
                    {{ selectedLocale }}
                  </ITableHeader>
                </ITableRow>
              </ITableHead>

              <ITableBody>
                <ITableRow
                  v-for="(translation, key) in groupTranslations"
                  :key="key"
                >
                  <ITableCell width="15%" class="whitespace-normal font-medium">
                    {{ key }}
                  </ITableCell>

                  <ITableCell width="30%" class="whitespace-normal">
                    {{ translations.source.groups[group][key] }}
                  </ITableCell>

                  <ITableCell width="55%">
                    <SettingsTranslatorTranslate
                      v-model="translations.current.groups[group][key]"
                      :source="translations.source.groups[group][key]"
                    />
                  </ITableCell>
                </ITableRow>
              </ITableBody>
            </ITable>
          </div>

          <ICardFooter class="-mt-px flex items-center justify-end space-x-2">
            <IButton
              variant="secondary"
              :disabled="groupIsBeingSaved"
              :text="$t('core::app.cancel')"
              @click="deactivateGroup(group, true)"
            />

            <IButton
              type="submit"
              variant="primary"
              :text="$t('core::app.save')"
              :disabled="groupIsBeingSaved"
              :loading="groupIsBeingSaved"
            />
          </ICardFooter>
        </form>
      </li>
    </ul>

    <template
      v-for="(namespaceGroupTranslations, namespace) in translations.current
        .namespaces"
      :key="namespace"
    >
      <p
        v-show="!activeGroup"
        class="border-y border-neutral-200 bg-neutral-100 px-6 py-3 font-semibold text-neutral-700 dark:border-neutral-500/30 dark:bg-neutral-700 dark:text-neutral-300"
      >
        {{ strTitle(namespace) }}
      </p>

      <ul class="divide-y divide-neutral-200 dark:divide-neutral-500/30">
        <li
          v-for="(groupTranslations, group) in translations.current.namespaces[
            namespace
          ]"
          v-show="
            !activeGroup ||
            (activeGroup === group && activeNamespace === namespace)
          "
          :key="group"
        >
          <div class="group hover:bg-neutral-100 dark:hover:bg-neutral-700/60">
            <div class="flex items-center">
              <div class="grow">
                <div class="px-6 py-2">
                  <ILink
                    class="block font-medium"
                    :text="strTitle(group.replace('_', ' '))"
                    basic
                    @click="toggleGroup(group, namespace)"
                  />
                </div>
              </div>

              <div class="ml-2 py-2 pr-6">
                <IButton
                  variant="secondary"
                  icon="ChevronDownSolid"
                  small
                  @click="toggleGroup(group, namespace)"
                />
              </div>
            </div>
          </div>

          <form
            v-show="activeGroup === group && activeNamespace === namespace"
            novalidate="true"
            @submit.prevent="saveGroup(group, namespace)"
          >
            <div class="px-6">
              <ITable class="[--gutter:theme(spacing.6)]" bleed>
                <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
                  <ITableRow>
                    <ITableHeader width="15%">Key</ITableHeader>

                    <ITableHeader width="30%">Source</ITableHeader>

                    <ITableHeader width="55%">
                      {{ selectedLocale }}
                    </ITableHeader>
                  </ITableRow>
                </ITableHead>

                <tbody>
                  <ITableRow
                    v-for="(translation, key) in groupTranslations"
                    :key="key"
                  >
                    <ITableCell
                      width="15%"
                      class="whitespace-normal font-medium"
                    >
                      {{ key }}
                    </ITableCell>

                    <ITableCell width="30%" class="whitespace-normal">
                      {{
                        translations.source.namespaces[namespace][group][key]
                      }}
                    </ITableCell>

                    <ITableCell width="55%">
                      <SettingsTranslatorTranslate
                        v-model="
                          translations.current.namespaces[namespace][group][key]
                        "
                        :source="
                          translations.source.namespaces[namespace][group][key]
                        "
                      />
                    </ITableCell>
                  </ITableRow>
                </tbody>
              </ITable>
            </div>

            <ICardFooter class="-mt-px flex items-center justify-end space-x-2">
              <IButton
                variant="secondary"
                :disabled="groupIsBeingSaved"
                :text="$t('core::app.cancel')"
                @click="deactivateGroup(group, true)"
              />

              <IButton
                type="submit"
                variant="primary"
                :text="$t('core::app.save')"
                :disabled="groupIsBeingSaved"
                :loading="groupIsBeingSaved"
              />
            </ICardFooter>
          </form>
        </li>
      </ul>
    </template>

    <IModal
      id="newLocaleModal"
      size="sm"
      :ok-text="$t('core::app.create')"
      :title="$t('translator::translator.create_new_locale')"
      form
      @submit="createLocale"
      @shown="() => $refs.inputNameRef.focus()"
    >
      <IFormGroup
        label-for="localeName"
        :label="$t('translator::translator.locale_name')"
        required
      >
        <IFormInput
          id="localeName"
          ref="inputNameRef"
          v-model="localeForm.name"
        />

        <IFormError :error="localeForm.getError('name')" />
      </IFormGroup>
    </IModal>
  </ICard>
</template>

<script setup>
import { ref, shallowRef, triggerRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { onBeforeRouteLeave, useRoute } from 'vue-router'
import isEqual from 'lodash/isEqual'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

import SettingsTranslatorTranslate from './SettingsTranslatorTranslate.vue'

const route = useRoute()
const { t } = useI18n()
const { appUrl, scriptConfig, locales } = useApp()

const { form: localeForm } = useForm({ name: null })

let originalTranslations = {}

const selectedLocale = ref(route.query.locale || scriptConfig('locale'))

// Active locale groups translation
const translations = shallowRef({
  source: {
    groups: {},
    namespaces: {},
  },
  current: {
    groups: {},
    namespaces: {},
  },
})

const activeGroup = ref(null)
const activeNamespace = ref(null)
const groupIsBeingSaved = ref(false)

watch(selectedLocale, getTranslations)

onBeforeRouteLeave((to, from, next) => {
  const unsaved = getUnsavedTranslationGroups()

  if (unsaved.length > 0) {
    Innoclapps.confirm({
      message: t('translator::translator.changes_not_saved'),
      title: 'Are you sure you want to leave this page?',
      confirmText: t('core::app.discard_changes'),
    })
      .then(() => next())
      .catch(() => next(false))
  } else {
    next()
  }
})

function getUnsavedTranslationGroups() {
  let groups = []
  let originalGroups = {}
  let currentGroups = {}

  if (activeNamespace.value) {
    groups = Object.keys(originalTranslations.namespaces[activeNamespace.value])
    originalGroups = originalTranslations.namespaces[activeNamespace.value]
    currentGroups = translations.value.current.namespaces[activeNamespace.value]
  } else {
    groups = Object.keys(originalTranslations.groups)
    originalGroups = originalTranslations.groups
    currentGroups = translations.value.current.groups
  }

  let unsaved = []

  groups.forEach(group => {
    if (!isEqual(originalGroups[group], currentGroups[group])) {
      unsaved.push(group)
    }
  })

  return unsaved
}

function saveGroup(group, namespace = null) {
  groupIsBeingSaved.value = true

  let payload = null

  if (namespace) {
    payload = translations.value.current.namespaces[namespace][group]
  } else {
    payload = translations.value.current.groups[group]
  }

  Innoclapps.request()
    .put(`/translation/${selectedLocale.value}/${group}`, {
      translations: payload,
      namespace: namespace,
    })
    .then(() => {
      window.location.href = `${appUrl}/settings/translator?locale=${selectedLocale.value}`
    })
    .finally(() => setTimeout(() => (groupIsBeingSaved.value = false), 1000))
}

function getTranslations(locale) {
  Innoclapps.request(`/translation/${locale}`).then(({ data }) => {
    originalTranslations = structuredClone(data.current)
    translations.value = data
  })
}

function createLocale() {
  localeForm.post('/translation').then(data => {
    locales.value.push(data.locale)
    selectedLocale.value = data.locale
    Innoclapps.dialog().hide('newLocaleModal')
  })
}

function toggleGroup(group, namespace = null) {
  if (activeGroup.value) {
    deactivateGroup(group)

    return
  }

  activateGroup(group, namespace)
}

function activateGroup(group, namespace = null) {
  activeGroup.value = group
  activeNamespace.value = namespace
}

function deactivateGroup(group, skipConfirmation = false) {
  const unsaved = getUnsavedTranslationGroups()
  let namespace = activeNamespace.value
  const groupIsModified = unsaved.indexOf(group) > -1

  if (skipConfirmation || !groupIsModified) {
    activeGroup.value = null
    activeNamespace.value = null

    // Replace only when group group modified
    if (groupIsModified) {
      replaceOriginalTranslations(group, namespace)
    }

    return
  }

  Innoclapps.confirm({
    message: t('translator::translator.changes_not_saved'),
    title: t('translator::translator.group_has_unsaved_translations'),
    confirmText: t('core::app.discard_changes'),
  }).then(() => {
    activeNamespace.value = null
    activeGroup.value = null
    replaceOriginalTranslations(group, namespace)
  })
}

function replaceOriginalTranslations(group, namespace = null) {
  if (namespace) {
    translations.value.current.namespaces[namespace][group] = structuredClone(
      originalTranslations.namespaces[namespace][group]
    )
    triggerRef(translations)

    return
  }

  translations.value.current.groups[group] = structuredClone(
    originalTranslations.groups[group]
  )
  triggerRef(translations)
}

function strTitle(str) {
  str = str.toLowerCase().split(' ')

  for (var i = 0; i < str.length; i++) {
    str[i] = str[i].charAt(0).toUpperCase() + str[i].slice(1)
  }

  return str.join(' ')
}

getTranslations(selectedLocale.value)
</script>
