<template>
  <div>
    <IOverlay :show="!componentReady">
      <IModal id="requiresFieldsModal" size="xs" hide-header>
        <ITextDisplay class="mb-2">
          {{ $t('webforms::form.fields_action_required') }}
        </ITextDisplay>

        <IText>
          {{ $t('webforms::form.required_fields_needed') }}
        </IText>

        <template #modal-footer="{ cancel }">
          <div class="text-right">
            <IButton
              variant="secondary"
              :text="$t('core::app.hide')"
              @click="cancel"
            />
          </div>
        </template>
      </IModal>

      <IModal
        id="nonOptionalFieldRequiredModal"
        size="xs"
        :ok-text="$t('core::app.continue')"
        :ok-disabled="
          hasContactEmailAddressField &&
          !acceptsRequiredFields.email &&
          hasContactPhoneField &&
          !acceptsRequiredFields.phones
        "
        hide-header
        @ok="acceptRequiredFields"
      >
        <ITextDisplay class="mb-2">
          {{ $t('webforms::form.fields_action_required') }}
        </ITextDisplay>

        <IText class="mb-3">
          {{ $t('webforms::form.must_requires_fields') }}
        </IText>

        <IFormCheckboxField
          v-show="!contactEmailFieldIsRequired && hasContactEmailAddressField"
        >
          <IFormCheckbox v-model:checked="acceptsRequiredFields.email" />

          <IFormCheckboxLabel :text="$t('contacts::fields.contacts.email')" />
        </IFormCheckboxField>

        <IFormCheckboxField
          v-show="!contactPhoneFieldIsRequired && hasContactPhoneField"
        >
          <IFormCheckbox v-model:checked="acceptsRequiredFields.phones" />

          <IFormCheckboxLabel :text="$t('contacts::fields.contacts.phone')" />
        </IFormCheckboxField>
      </IModal>

      <ICard as="form" novalidate="true" @submit.prevent="beforeUpdateChecks">
        <ICardHeader>
          <div class="flex w-full sm:mr-16">
            <IButton
              class="-ml-5 mr-1 shrink-0 sm:-ml-0 sm:mr-2"
              icon="ChevronLeftSolid"
              :to="{ name: 'web-forms-index' }"
              basic
            />

            <div class="w-full min-w-0">
              <input
                id="name"
                v-model="form.title"
                type="text"
                name="name"
                :class="[
                  'block w-full border-0 border-b-2 bg-neutral-50 text-base/6 font-medium focus:bg-neutral-100 focus:ring-0 dark:bg-neutral-500/10 dark:text-white dark:focus:bg-neutral-800 sm:text-sm/6',
                  (!form.title || form.getError('title')) && componentReady
                    ? 'border-danger-500 focus:border-danger-600'
                    : 'border-transparent focus:border-primary-500',
                ]"
              />
            </div>
          </div>

          <ICardActions class="w-full justify-end sm:w-auto">
            <div class="mr-3 flex sm:mr-6">
              <IActionMessage
                class="mr-2"
                :show="form.recentlySuccessful"
                :message="$t('core::app.saved')"
              />

              <IFormSwitchField>
                <IFormSwitchLabel :text="$t('webforms::form.active')" />

                <IFormSwitch
                  v-model="form.status"
                  value="active"
                  unchecked-value="inactive"
                  :disabled="addingNewSection || form.busy"
                />
              </IFormSwitchField>
            </div>

            <ILink
              class="mr-2"
              :text="$t('core::app.preview')"
              :href="form.public_url"
            />

            <IButton
              variant="primary"
              :loading="form.busy"
              :disabled="form.busy || addingNewSection"
              :text="$t('core::app.save')"
              @click="beforeUpdateChecks"
            />
          </ICardActions>
        </ICardHeader>

        <div
          class="overflow-auto sm:h-[calc(100vh-(var(--navbar-height)+220px))]"
        >
          <div class="m-auto max-w-full">
            <ITabGroup>
              <ITabList
                class="sticky top-0 z-10 bg-white dark:bg-neutral-900"
                centered
              >
                <ITab :title="$t('webforms::form.editor')" />

                <ITab :title="$t('webforms::form.submit_options')" />

                <ITab :title="$t('webforms::form.style.style')" />

                <ITab :title="$t('webforms::form.sections.embed.embed')" />
              </ITabList>

              <ITabPanels class="mt-4 sm:mt-0">
                <ITabPanel class="px-2 sm:px-0">
                  <div
                    v-for="(section, index) in form.sections"
                    :key="index + section.type + section.attribute"
                    class="m-auto max-w-sm"
                  >
                    <component
                      :is="sectionComponents[section.type]"
                      :form="form"
                      :companies-fields="companiesFields"
                      :contacts-fields="contactsFields"
                      :deals-fields="dealsFields"
                      :index="index"
                      :available-resources="availableResources"
                      :section="section"
                      @remove-section-requested="removeSection(index)"
                      @update-section-requested="
                        updateSectionRequestedEvent(index, $event)
                      "
                      @create-section-requested="createSection(index, $event)"
                    />

                    <div
                      v-if="totalSections - 1 != index"
                      class="group relative flex flex-col items-center justify-center"
                    >
                      <div v-show="!addingNewSection" class="absolute">
                        <IButton
                          class="transition-opacity delay-75 md:opacity-0 md:group-hover:opacity-100"
                          icon="PlusSolid"
                          variant="primary"
                          soft
                          @click="newSection(index)"
                        />
                      </div>

                      <svg height="56" width="360">
                        <line
                          x1="180"
                          y1="0"
                          x2="180"
                          y2="56"
                          class="stroke-current stroke-1 text-neutral-900 dark:text-neutral-700"
                        />
                        Sorry, your browser does not support inline SVG.
                      </svg>
                    </div>
                  </div>
                </ITabPanel>

                <ITabPanel>
                  <ICardBody>
                    <ITextDisplay
                      class="mb-3"
                      :text="$t('webforms::form.success_page.success_page')"
                    />

                    <IFormGroup>
                      <IFormLabel as="p" class="mb-1">
                        {{
                          $t('webforms::form.success_page.success_page_info')
                        }}
                      </IFormLabel>

                      <IFormRadioField>
                        <IFormRadio
                          v-model="form.submit_data.action"
                          value="message"
                          name="submit-action"
                        />

                        <IFormRadioLabel
                          :text="
                            $t('webforms::form.success_page.thank_you_message')
                          "
                        />
                      </IFormRadioField>

                      <IFormRadioField>
                        <IFormRadio
                          v-model="form.submit_data.action"
                          value="redirect"
                          name="submit-action"
                        />

                        <IFormRadioLabel
                          :text="$t('webforms::form.success_page.redirect')"
                        />
                      </IFormRadioField>

                      <IFormError
                        :error="form.getError('submit_data.action')"
                      />
                    </IFormGroup>

                    <div class="mb-3">
                      <div v-show="form.submit_data.action === 'message'">
                        <IFormGroup
                          label-for="success_title"
                          :label="$t('webforms::form.success_page.title')"
                          required
                        >
                          <IFormInput
                            v-model="form.submit_data.success_title"
                            :placeholder="
                              $t(
                                'webforms::form.success_page.title_placeholder'
                              )
                            "
                          />

                          <IFormError
                            :error="form.getError('submit_data.success_title')"
                          />
                        </IFormGroup>

                        <IFormGroup
                          :label="$t('webforms::form.success_page.message')"
                          optional
                        >
                          <Editor
                            v-model="form.submit_data.success_message"
                            :with-image="false"
                            absolute-urls
                            minimal
                          />
                        </IFormGroup>
                      </div>

                      <div v-show="form.submit_data.action === 'redirect'">
                        <IFormGroup
                          label-for="success_redirect_url"
                          :label="
                            $t('webforms::form.success_page.redirect_url')
                          "
                          required
                        >
                          <IFormInput
                            v-model="form.submit_data.success_redirect_url"
                            type="url"
                            :placeholder="
                              $t(
                                'webforms::form.success_page.redirect_url_placeholder'
                              )
                            "
                          />

                          <IFormError
                            :error="
                              form.getError('submit_data.success_redirect_url')
                            "
                          />
                        </IFormGroup>
                      </div>
                    </div>

                    <ITextDisplay
                      class="mb-3 mt-8"
                      :text="
                        $t(
                          'webforms::form.saving_preferences.saving_preferences'
                        )
                      "
                    />

                    <IFormGroup
                      label-for="title_prefix"
                      :label="
                        $t(
                          'webforms::form.saving_preferences.deal_title_prefix'
                        )
                      "
                      :description="
                        $t(
                          'webforms::form.saving_preferences.deal_title_prefix_info'
                        )
                      "
                      optional
                    >
                      <IFormInput
                        id="title_prefix"
                        v-model="form.title_prefix"
                      />
                    </IFormGroup>

                    <IFormGroup
                      label-for="pipeline_id"
                      :label="$t('deals::fields.deals.pipeline.name')"
                      required
                    >
                      <ICustomSelect
                        v-model="pipeline"
                        label="name"
                        input-id="pipeline_id"
                        :options="pipelines"
                        :clearable="false"
                        @update:model-value="stage = $event.stages[0]"
                      />

                      <IFormError
                        :error="form.getError('submit_data.pipeline_id')"
                      />
                    </IFormGroup>

                    <IFormGroup
                      label-for="stage_id"
                      :label="$t('deals::fields.deals.stage.name')"
                      required
                    >
                      <ICustomSelect
                        v-model="stage"
                        label="name"
                        input-id="stage_id"
                        :options="pipeline ? pipeline.stages : []"
                        :clearable="false"
                      />

                      <IFormError
                        :error="form.getError('submit_data.stage_id')"
                      />
                    </IFormGroup>

                    <IFormGroup
                      label-for="user_id"
                      :label="$t('deals::fields.deals.user.name')"
                      required
                    >
                      <ICustomSelect
                        v-model="form.user_id"
                        label="name"
                        input-id="user_id"
                        :options="users"
                        :clearable="false"
                        :reduce="user => user.id"
                      />

                      <IFormError :error="form.getError('user_id')" />
                    </IFormGroup>

                    <IFormGroup
                      label-for="notifications"
                      :label="$t('webforms::form.notifications')"
                    >
                      <div
                        v-for="(email, index) in form.notifications"
                        :key="index"
                        class="mb-3"
                      >
                        <div class="flex space-x-2">
                          <div
                            class="relative flex grow text-neutral-500 focus-within:text-neutral-600 dark:text-neutral-300 dark:focus-within:text-neutral-100"
                          >
                            <div
                              class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                              <Icon icon="Mail" class="size-5" />
                            </div>

                            <IFormInput
                              v-model="form.notifications[index]"
                              class="pl-10 sm:pl-11"
                              type="email"
                              :placeholder="
                                $t(
                                  'webforms::form.notification_email_placeholder'
                                )
                              "
                            />
                          </div>

                          <IButton
                            icon="Trash"
                            basic
                            @click="removeNotification(index)"
                          />
                        </div>

                        <IFormError
                          :error="form.getError('notifications.' + index)"
                        />
                      </div>

                      <ILink
                        v-show="
                          !emptyNotificationsEmails || totalNotifications === 0
                        "
                        class="font-medium"
                        :text="$t('webforms::form.new_notification')"
                        @click="addNewNotification"
                      />
                    </IFormGroup>
                  </ICardBody>
                </ITabPanel>

                <ITabPanel>
                  <ICardBody>
                    <ITextDisplay
                      class="mb-3"
                      :text="$t('webforms::form.style.style')"
                    />

                    <IFormGroup
                      class="mt-3 w-full sm:w-[373px]"
                      label-for="locale"
                      :label="$t('core::app.locale')"
                    >
                      <ICustomSelect
                        v-model="form.locale"
                        input-id="locale"
                        :clearable="false"
                        :options="locales"
                      >
                      </ICustomSelect>

                      <IFormError :error="form.getError('locale')" />
                    </IFormGroup>

                    <IFormGroup
                      :label="$t('webforms::form.style.primary_color')"
                    >
                      <IColorSwatch
                        v-model="form.styles.primary_color"
                        :swatches="swatches"
                      />

                      <IFormError
                        :error="form.getError('styles.primary_color')"
                      />
                    </IFormGroup>

                    <IFormGroup
                      :label="$t('webforms::form.style.background_color')"
                    >
                      <IColorSwatch
                        v-model="form.styles.background_color"
                        :swatches="swatches"
                      />

                      <IFormError
                        :error="form.getError('styles.background_color')"
                      />
                    </IFormGroup>

                    <IFormLabel
                      as="p"
                      class="mb-1 mt-4"
                      :label="$t('webforms::form.style.logo')"
                    />

                    <WebFormsEditLogoType
                      v-model="form.styles.logo"
                      :background-color="form.styles.background_color"
                      :primary-color="form.styles.primary_color"
                    />
                  </ICardBody>
                </ITabPanel>

                <ITabPanel>
                  <ICardBody>
                    <WebFormsEditEmbed :form="form" />
                  </ICardBody>
                </ITabPanel>
              </ITabPanels>
            </ITabGroup>
          </div>
        </div>
      </ICard>
    </IOverlay>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'
import get from 'lodash/get'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'
import { usePageTitle } from '@/Core/composables/usePageTitle'
import { isBlank } from '@/Core/utils'

import { usePipelines } from '@/Deals/composables/usePipelines'

import FieldSection from '../components/EditorSections/FieldSection.vue'
import FileSection from '../components/EditorSections/FileSection.vue'
import IntroductionSection from '../components/EditorSections/IntroductionSection.vue'
import MessageSection from '../components/EditorSections/MessageSection.vue'
import NewSection from '../components/EditorSections/NewSection.vue'
import SubmitButtonSection from '../components/EditorSections/SubmitButtonSection.vue'
import { useWebForms } from '../composables/useWebForms'

import WebFormsEditEmbed from './WebFormsEditEmbed.vue'
import WebFormsEditLogoType from './WebFormsEditLogoType.vue'

const sectionComponents = {
  'field-section': FieldSection,
  'introduction-section': IntroductionSection,
  'submit-button-section': SubmitButtonSection,
  'file-section': FileSection,
  'new-section': NewSection,
  'message-section': MessageSection,
}

const route = useRoute()
const { t } = useI18n()
const { scriptConfig, users, currentUser, locales } = useApp()
const { fetchWebForm, setWebForm, findWebForm } = useWebForms()
const { orderedPipelines: pipelines } = usePipelines()
usePageTitle(computed(() => findWebForm(route.params.id)?.title))

const swatches = scriptConfig('favourite_colors')

const acceptsRequiredFields = ref({
  email: true,
  phones: true,
})

const contactsFields = ref([])
const companiesFields = ref([])
const dealsFields = ref([])
const pipeline = ref(null)
const stage = ref(null)
const componentReady = ref(false)
const addingNewSection = ref(false)

const { form } = useForm({
  notifications: [],
  sections: [],
  styles: [],
  submit_data: [],
})

const availableResources = [
  {
    id: 'contacts',
    label: t('contacts::contact.contact'),
  },
  {
    id: 'companies',
    label: t('contacts::company.company'),
  },
  {
    id: 'deals',
    label: t('deals::deal.deal'),
  },
]

const totalNotifications = computed(() => form.notifications.length)

const emptyNotificationsEmails = computed(
  () => form.notifications.filter(isBlank).length > 0
)

const totalSections = computed(() => form.sections.length)

const hasContactEmailAddressField = computed(
  () =>
    find(form.sections, {
      resourceName: Innoclapps.resourceName('contacts'),
      attribute: 'email',
    }) !== undefined
)

const hasContactPhoneField = computed(
  () =>
    find(form.sections, {
      resourceName: Innoclapps.resourceName('contacts'),
      attribute: 'phones',
    }) !== undefined
)

const contactEmailFieldIsRequired = computed(() => {
  if (!hasContactEmailAddressField.value) {
    return false
  }

  return (
    find(form.sections, {
      resourceName: Innoclapps.resourceName('contacts'),
      attribute: 'email',
    }).isRequired === true
  )
})

const contactPhoneFieldIsRequired = computed(() => {
  if (!hasContactPhoneField.value) {
    return false
  }

  return (
    find(form.sections, {
      resourceName: Innoclapps.resourceName('contacts'),
      attribute: 'phones',
    }).isRequired === true
  )
})

const requiresFields = computed(
  () => !hasContactEmailAddressField.value && !hasContactPhoneField.value
)

const requiresNonOptionalFields = computed(
  () => !contactEmailFieldIsRequired.value && !contactPhoneFieldIsRequired.value
)

function updateSectionRequestedEvent(index, data) {
  updateSection(index, data, false)

  if (requiresFields.value || requiresNonOptionalFields.value) {
    beforeUpdateChecks()
  } else {
    update()
  }
}

function removeCreateSection() {
  const newSectionIndex = findIndex(form.sections, {
    type: 'new-section',
  })

  if (newSectionIndex !== -1) {
    removeSection(newSectionIndex)
  }
}

function newSection(index) {
  addingNewSection.value = true

  form.sections.splice(index + 1, 0, {
    type: 'new-section',
    label: t('webforms::form.sections.new'),
  })
}

async function removeSection(index) {
  if (form.sections[index].type === 'new-section') {
    addingNewSection.value = false
    form.sections.splice(index, 1)
  } else {
    await Innoclapps.confirm()
    form.sections.splice(index, 1)
    updateSilentlyIfPossible()
  }
}

function updateSection(index, data, forceUpdate = true) {
  form.sections[index] = Object.assign({}, form.sections[index], data)

  if (forceUpdate) {
    update(true)
  }
}

function createSection(fromIndex, data) {
  form.sections.splice(fromIndex + 1, 0, data)
  updateSilentlyIfPossible()
  removeCreateSection()
}

/**
 * Update the form if possible
 *
 * The function will check if the required fields criteria is met
 * If yes, will silently perform update, used when user is creating, updating and removed section
 * So the form is automatically saved with click on SAVE on the section button
 */
function updateSilentlyIfPossible() {
  if (!requiresFields.value && !requiresNonOptionalFields.value) {
    update(true)
  }
}

function setDefaultSectionsIfNeeded() {
  if (totalSections.value === 0) {
    form.sections.push({
      type: 'introduction-section',
      message: '',
      title: '',
    })

    form.sections.push({
      type: 'submit-button-section',
      text: t('webforms::form.sections.submit.default_text'),
    })
  }
}

function removeNotification(index) {
  form.notifications.splice(index, 1)
}

function addNewNotification() {
  form.notifications.push('')

  if (form.notifications.length === 1) {
    form.notifications[0] = currentUser.value.email
  }
}

function beforeUpdateChecks() {
  if (requiresFields.value) {
    Innoclapps.dialog().show('requiresFieldsModal')

    return
  } else if (requiresNonOptionalFields.value) {
    Innoclapps.dialog().show('nonOptionalFieldRequiredModal')

    return
  }

  update()
}

function acceptRequiredFields() {
  if (hasContactEmailAddressField.value && acceptsRequiredFields.value.email) {
    updateSection(
      findIndex(form.sections, {
        resourceName: Innoclapps.resourceName('contacts'),
        attribute: 'email',
      }),
      {
        isRequired: true,
      },
      false
    )
  }

  if (hasContactPhoneField.value && acceptsRequiredFields.value.phones) {
    updateSection(
      findIndex(form.sections, {
        resourceName: Innoclapps.resourceName('contacts'),
        attribute: 'phones',
      }),
      {
        isRequired: true,
      },
      false
    )
  }

  update()

  Innoclapps.dialog().hide('nonOptionalFieldRequiredModal')
}

function update(silent = false) {
  form.submit_data.pipeline_id = pipeline.value ? pipeline.value.id : null
  form.submit_data.stage_id = stage.value ? stage.value.id : null

  removeCreateSection()

  form
    .put(`/forms/${route.params.id}`)
    .then(webForm => {
      setWebForm(webForm.id, webForm)

      if (!silent) {
        Innoclapps.success(t('webforms::form.updated'))
      }
    })
    .catch(e => {
      if (e.isValidationError()) {
        Innoclapps.error(
          t('core::app.form_validation_failed_with_sections'),
          3000
        )
      }

      return Promise.reject(e)
    })
}

function isReadOnly(field) {
  return field.readonly || get(field, 'attributes.readonly')
}

function filterFields(fields, excludedAttributes) {
  return fields.filter(
    field =>
      field.showOnCreation === true &&
      (excludedAttributes.indexOf(field.attribute) === -1 || isReadOnly(field))
  )
}

async function getResourcesFields() {
  let { data } = await Innoclapps.request(
    '/fields/settings/bulk/create?intent=create',
    {
      params: {
        groups: ['contacts', 'companies', 'deals'],
      },
    }
  )

  contactsFields.value = filterFields(data.contacts, [
    'user_id',
    'source_id',
    'tags',
    'deals',
    'companies',
  ])

  dealsFields.value = filterFields(data.deals, [
    'user_id',
    'pipeline_id',
    'stage_id',
    'tags',
    'contacts',
    'companies',
  ])

  companiesFields.value = filterFields(data.companies, [
    'user_id',
    'parent_company_id',
    'source_id',
    'tags',
    'contacts',
    'deals',
  ])
}

function prepareComponent() {
  // We will get the fields from settings as these
  // are the fields the user is allowed to interact and use them in forms

  getResourcesFields().finally(() => {
    fetchWebForm(route.params.id).then(webForm => {
      form.clear().set(webForm)

      pipeline.value = pipelines.value.filter(
        pipeline => pipeline.id == webForm.submit_data.pipeline_id
      )[0]

      stage.value = pipeline.value.stages.filter(
        stage => stage.id == webForm.submit_data.stage_id
      )[0]

      setDefaultSectionsIfNeeded()
      componentReady.value = true
    })
  })
}

prepareComponent()
</script>
