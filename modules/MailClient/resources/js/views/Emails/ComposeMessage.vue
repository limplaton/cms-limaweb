<template>
  <IModal
    id="composeMessageModal"
    size="xl"
    :hide-footer="showTemplates"
    :visible="visible"
    :title="$t('mailclient::inbox.new_message')"
    static-backdrop
    @hidden="modalHidden"
    @shown="handleModalShown"
  >
    <div
      class="-mx-8 mb-4 border-y border-neutral-200 bg-neutral-50 px-8 py-2 dark:border-neutral-500/30 dark:bg-neutral-500/10"
    >
      <div class="flex">
        <div class="mr-4">
          <ILink
            v-show="!showTemplates"
            class="font-medium"
            :text="$t('mailclient.mail.templates.templates')"
            @click="showTemplates = true"
          />

          <ILink
            v-show="showTemplates"
            class="font-medium"
            :text="$t('mailclient.mail.compose')"
            @click="showTemplates = false"
          />
        </div>

        <div v-show="!showTemplates" class="font-medium">
          <AssociationsPopover
            v-model="form.associations"
            :primary-record-disabled="true"
            :primary-record="resourceName ? relatedResource : undefined"
            :primary-resource-name="resourceName"
            :initial-associateables="resourceName ? relatedResource : undefined"
            :associateables="customAssociationsValue"
            @change="
              parsePlaceholdersForMessage(), parsePlaceholdersForSubject()
            "
          >
            <template
              #after-record="{
                title,
                resource: associatedResourceName,
                record,
                isSelected,
                isSearching,
                selectedRecords,
              }"
            >
              <span
                v-if="
                  showWillUsePlaceholdersIconToAssociateResourceRecord(
                    record,
                    selectedRecords,
                    associatedResourceName,
                    isSelected,
                    isSearching
                  )
                "
                v-i-tooltip.top="
                  $t('mailclient::inbox.will_use_placeholders_from_record', {
                    resourceName: title,
                  })
                "
                class="ml-1.5 self-start"
              >
                <Icon
                  icon="CodeBracket"
                  class="size-5 text-neutral-500 dark:text-neutral-400"
                />
              </span>
            </template>
          </AssociationsPopover>
        </div>
      </div>
    </div>

    <div v-show="!showTemplates">
      <IOverlay :show="!componentReady">
        <IAlert variant="danger" :show="hasInvalidAddresses" dismissible>
          <IAlertBody>
            {{ $t('mailclient::mail.validation.invalid_recipients') }}
          </IAlertBody>
        </IAlert>

        <MailRecipient
          v-model="form.to"
          type="to"
          :form="form"
          :label="$t('mailclient::inbox.to')"
          @recipient-removed="handleToRecipientRemovedEvent"
          @recipient-selected="handleRecipientSelectedEvent"
        >
          <template #after>
            <div v-if="!wantsBcc || !wantsCc" class="ml-2 space-x-2">
              <ILink
                v-if="!wantsCc"
                :text="$t('mailclient.inbox.cc')"
                @click="setWantsCC"
              />

              <ILink
                v-if="!wantsBcc"
                :text="$t('mailclient.inbox.bcc')"
                @click="setWantsBCC"
              />
            </div>
          </template>
        </MailRecipient>

        <hr
          class="my-3 border-t border-neutral-200 dark:border-neutral-500/30"
        />

        <div v-show="wantsCc">
          <MailRecipient
            v-model="form.cc"
            type="cc"
            :form="form"
            :label="$t('mailclient::inbox.cc')"
            @recipient-removed="dissociateRemovedRecipients"
            @recipient-selected="associateSelectedRecipients"
          />

          <hr
            class="my-3 border-t border-neutral-200 dark:border-neutral-500/30"
          />
        </div>

        <div v-show="wantsBcc">
          <MailRecipient
            v-model="form.bcc"
            type="bcc"
            :form="form"
            :label="$t('mailclient::inbox.bcc')"
          />

          <hr
            class="my-3 border-t border-neutral-200 dark:border-neutral-500/30"
          />
        </div>

        <div class="flex items-center">
          <IFormLabel
            as="p"
            class="mr-1 min-w-14"
            :label="$t('mailclient::inbox.from')"
          />

          <IDropdown adaptive-width>
            <IDropdownButton basic>
              <span>
                <span class="mr-1 hidden sm:inline-block">
                  {{ selectedAccount.formatted_from_name_header }}
                </span>

                <span class="hidden sm:inline" v-text="'<'" />

                <span v-text="selectedAccount.display_email" />

                <span class="hidden sm:inline" v-text="'>'" />
              </span>
            </IDropdownButton>

            <IDropdownMenu>
              <IDropdownItem
                v-for="account in emailAccounts"
                :key="account.id"
                :text="account.display_email"
                :active="account.id === selectedAccount.id"
                @click="selectedAccount = account"
              />
            </IDropdownMenu>
          </IDropdown>
        </div>

        <hr
          class="my-3 border-t border-neutral-200 dark:border-neutral-500/30"
        />

        <div class="flex items-center">
          <div class="mr-1 min-w-14">
            <IFormLabel
              for="subject"
              :label="$t('mailclient::inbox.subject')"
            />
          </div>

          <div class="grow">
            <div class="relative">
              <IFormInput
                id="subject"
                ref="subjectRef"
                :class="{
                  '!border-danger-600':
                    !subjectPlaceholdersSyntaxIsValid ||
                    hasInvalidSubjectPlaceholders,
                  'border-dashed !border-neutral-400': subjectDragover,
                }"
                :model-value="showParsedSubject ? parsedSubject : subject"
                :disabled="showParsedSubject"
                @update:model-value="subject = $event"
                @dragover="
                  !showParsedSubject ? (subjectDragover = true) : undefined
                "
                @dragleave="subjectDragover = false"
                @drop="subjectDragover = false"
              />

              <ILink
                v-show="showParsedSubject"
                tabindex="-1"
                @click="showParsedSubject = false"
              >
                <Icon
                  icon="CodeBracket"
                  class="absolute bottom-0 right-4 top-0 m-auto size-5 text-neutral-500"
                />
              </ILink>

              <ILink
                v-if="
                  subjectContainsPlaceholders &&
                  !showParsedSubject &&
                  resourcesForPlaceholders.length > 0
                "
                tabindex="-1"
                plain
                @click="showParsedSubject = true"
              >
                <Icon
                  icon="ViewfinderCircle"
                  class="absolute bottom-0 right-4 top-0 m-auto size-5 text-neutral-500"
                />
              </ILink>
            </div>

            <IFormError :error="form.getError('subject')" />
          </div>
        </div>

        <hr
          class="my-3 border-t border-neutral-200 dark:border-neutral-500/30"
        />

        <MailEditor
          ref="editorRef"
          v-model="form.message"
          :placeholders="placeholders"
          :placeholders-parse-in-progress="messagePlaceholdersBeingParsed"
          :with-drop="true"
          @placeholder-inserted="parsePlaceholdersForMessage"
          @template-selected="handleTemplateSelected"
        />

        <div class="relative mt-3">
          <MediaUpload
            :action-url="`${$scriptConfig(
              'apiURL'
            )}/media/pending/${attachmentsDraftId}`"
            :select-file-text="$t('core::app.attach_files')"
            @file-uploaded="handleAttachmentUploaded"
          >
            <MediaItemsList
              class="mb-3"
              :items="attachments"
              :authorize-delete="true"
              @delete-requested="destroyPendingAttachment"
            />
          </MediaUpload>
        </div>
      </IOverlay>

      <IAlert
        v-if="showEmptyPlaceholdersMessage"
        variant="warning"
        class="mt-4"
      >
        <IAlertBody>
          {{ $t('mailclient::inbox.pre_send_empty_placeholders_found') }}
        </IAlertBody>
      </IAlert>
    </div>

    <template #modal-footer="{ cancel }">
      <div class="flex flex-col sm:flex-row sm:items-center">
        <div class="grow">
          <CreateFollowUpTask
            v-show="Boolean(resourceName)"
            v-model="form.task_date"
          />
        </div>

        <div
          class="mt-2 space-y-2 sm:mt-0 sm:flex sm:items-center sm:space-y-0"
        >
          <div
            :class="['flex items-center ', !schedule ? 'sm:mr-4' : 'sm:mr-1']"
          >
            <IFormCheckboxField class="shrink-0">
              <IFormCheckbox v-model:checked="schedule" />

              <IFormCheckboxLabel
                :text="!schedule ? $t('mailclient::schedule.send_later') : ''"
              />
            </IFormCheckboxField>

            <DatePicker
              v-if="schedule"
              v-model="form.scheduled_at"
              min-date="now"
              mode="dateTime"
              :required="true"
            >
              <template #default="{ inputValue, inputEvents }">
                <input
                  class="cursor-pointer rounded-md border-neutral-300 bg-transparent py-1 text-base font-medium text-neutral-700 ring-primary-600 focus:border-transparent focus:ring-primary-600 dark:border-neutral-500/30 dark:text-neutral-100 dark:ring-primary-500 dark:focus:ring-primary-500 sm:text-sm"
                  :value="inputValue"
                  :placeholder="$t('mailclient::schedule.choose_date')"
                  v-on="inputEvents"
                />
              </template>
            </DatePicker>
          </div>

          <IButton
            class="w-full sm:mr-1 sm:w-auto"
            :text="$t('core::app.cancel')"
            basic
            @click="cancel"
          />

          <IButton
            class="w-full sm:w-auto"
            :loading="sending"
            :variant="showEmptyPlaceholdersMessage ? 'danger' : 'primary'"
            :disabled="sendButtonIsDisabled"
            :text="
              showEmptyPlaceholdersMessage
                ? $t('core::app.confirm')
                : schedule
                  ? $t('mailclient::schedule.schedule')
                  : $t('mailclient::inbox.send')
            "
            @click="send(showEmptyPlaceholdersMessage)"
          />
        </div>
      </div>
    </template>

    <PredefinedMailTemplatesList
      v-if="showTemplates"
      @selected="handleTemplateSelected"
      @created="scrollToTop"
      @updated="scrollToTop"
      @will-create="scrollToTop"
      @will-edit="scrollToTop"
    />
  </IModal>
</template>

<script setup>
import { computed, inject, nextTick, ref, watch } from 'vue'

import AssociationsPopover from '@/Core/components/AssociationsPopover.vue'
import MediaItemsList from '@/Core/components/Media/MediaItemsList.vue'
import MediaUpload from '@/Core/components/Media/MediaUpload.vue'
import { randomString } from '@/Core/utils'

import CreateFollowUpTask from '@/Activities/components/CreateFollowUpTask.vue'

import MailEditor from '../../components/MailEditor.vue'
import { useEmailAccounts } from '../../composables/useEmailAccounts'
import { useMessageComposer } from '../../composables/useMessageComposer'
import { useSignature } from '../../composables/useSignature'
import PredefinedMailTemplatesList from '../Templates/PredefinedMailTemplatesList.vue'

import MailRecipient from './RecipientSelectorField.vue'

const props = defineProps({
  resourceName: String,
  resourceId: [String, Number], // Must be provided if resourceName is provided
  relatedResource: Object, // Must be provided if resourceName is provided
  visible: Boolean,
  defaultAccount: Object,
  to: { type: Array, default: () => [] },
  associations: { type: Array, default: () => [] },
})

const synchronizeResource = inject('synchronizeResource', null)

const {
  form,
  attachments,
  attachmentsDraftId,
  handleAttachmentUploaded,
  destroyPendingAttachment,
  customAssociationsValue,
  placeholders,
  resourcesForPlaceholders,
  subject,
  parsedSubject,
  subjectPlaceholdersSyntaxIsValid,
  hasInvalidSubjectPlaceholders,
  subjectContainsPlaceholders,
  parsePlaceholdersForMessage,
  parsePlaceholdersForSubject,
  showWillUsePlaceholdersIconToAssociateResourceRecord,
  sending,
  sendRequest,
  hasInvalidAddresses,
  associateSelectedRecipients,
  dissociateRemovedRecipients,
  associateMessageRecord,
  handleRecipientSelectedEvent,
  handleToRecipientRemovedEvent,
  setWantsCC,
  setWantsBCC,
  wantsBcc,
  wantsCc,
  hasEmptyPlaceholders,
  schedule,
  messagePlaceholdersBeingParsed,
} = useMessageComposer(
  props.resourceName,
  props.relatedResource,
  synchronizeResource
)

const { signature } = useSignature()
const { emailAccounts, fetchEmailAccounts } = useEmailAccounts()

const showEmptyPlaceholdersMessage = ref(false)

watch(hasEmptyPlaceholders, (newVal, oldVal) => {
  if (oldVal && !newVal) {
    showEmptyPlaceholdersMessage.value = false
  }
})

const editorRef = ref(null)
const subjectRef = ref(null)
const showParsedSubject = ref(false)

const selectedAccount = ref({})
const componentReady = ref(false)
const showTemplates = ref(false)
const subjectDragover = ref(false)

const sendButtonIsDisabled = computed(
  () =>
    form.to.length === 0 ||
    !subject.value ||
    sending.value ||
    (schedule.value && !form.scheduled_at)
)

watch(
  () => props.defaultAccount,
  newVal => {
    selectedAccount.value = newVal
  }
)

// In case the to is updated
// we need to update the form value too
// e.q. update contact email and click create email
// if we don't update the value the old email will be used
watch(
  () => props.to,
  newVal => {
    form.to = newVal
    associateSelectedRecipients(newVal)
  },
  { immediate: true }
)

function associateAdditionalAssociations(associations) {
  associations.forEach(record =>
    associateMessageRecord(record, record.resourceName)
  )
}

watch(
  () => props.associations,
  newVal => {
    associateAdditionalAssociations(newVal)
  },
  { immediate: true }
)

function handleTemplateSelected(template) {
  // Allow the sales agent to enter custom subject if needed
  if (!subject.value) {
    subject.value = template.subject
  }

  // Remove the blank line above the signature
  if (form.message && form.message.startsWith('<p></p>' + signature.value)) {
    form.message = form.message.replace('<p></p>', '')
  }

  form.message = template.body + form.message
  showTemplates.value = false
  parsePlaceholdersForMessage()
  parsePlaceholdersForSubject()

  scrollToTop()
  nextTick(() => editorRef.value.focus())
}

function scrollToTop() {
  document.querySelector('.dialog').scrollTo({ top: 0, behavior: 'instant' })
}

/**
 * Handle modal shown event
 * Each time the modal is shown we need to generate new draft id for the attachments
 */
function handleModalShown() {
  // If prevously there was to selected, use the same to as associations
  // e.q. open deal modal, close deal modal, open again, the form.to won't be associated
  if (form.to) {
    associateSelectedRecipients(form.to)
  }

  associateAdditionalAssociations(props.associations)

  attachmentsDraftId.value = randomString()
}

/**
 * Handle modal shown hidden
 *
 * Reset the state, we need to reset the form and the
 * attachments because when the modal is hidden each time
 * new attachmentsDraftId is generated
 *
 * @return {Void}
 */
function modalHidden() {
  form.reset()
  schedule.value = false

  // Add to again if there was TO recipients provided
  if (props.to) {
    form.to = props.to
  }

  subject.value = null
  parsedSubject.value = null
  attachments.value = []
  customAssociationsValue.value = {}
}

function send(skipEmptyPlaceholdersCheck = false) {
  if (skipEmptyPlaceholdersCheck === false && hasEmptyPlaceholders.value) {
    showEmptyPlaceholdersMessage.value = true

    return
  } else if (showEmptyPlaceholdersMessage.value) {
    showEmptyPlaceholdersMessage.value = false
  }

  sendRequest(`/inbox/emails/${selectedAccount.value.id}`).then(() =>
    Innoclapps.dialog().hide('composeMessageModal')
  )
}

function prepareComponent() {
  fetchEmailAccounts().then(accounts => {
    selectedAccount.value = props.defaultAccount || accounts[0]
    componentReady.value = true
  })
}

prepareComponent()

defineExpose({
  subjectRef,
})
</script>
