<template>
  <IModal
    id="replyMessageModal"
    size="xl"
    :hide-footer="showTemplates"
    :visible="visible"
    :title="
      $t(
        'mailclient::inbox.' +
          (forward ? 'forward_message' : 'reply_to_message'),
        {
          subject: message.subject,
        }
      )
    "
    static-backdrop
    @hidden="handleModalHidden"
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
            resource-name="emails"
            :resource-id="message.id"
            :associations-count="message.associations_count"
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
                v-i-tooltip.top="
                  $t('mailclient::inbox.will_use_placeholders_from_record', {
                    resourceName: title,
                  })
                "
                class="ml-1.5 self-start"
              >
                <Icon
                  v-if="
                    showWillUsePlaceholdersIconToAssociateResourceRecord(
                      record,
                      selectedRecords,
                      associatedResourceName,
                      isSelected,
                      isSearching
                    )
                  "
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
      <IAlert variant="danger" :show="hasInvalidAddresses" dismissible>
        <IAlertBody>
          {{ $t('mailclient::mail.validation.invalid_recipients') }}
        </IAlertBody>
      </IAlert>

      <MailRecipient
        ref="toRef"
        v-model="form.to"
        type="to"
        :form="form"
        :label="$t('mailclient::inbox.to')"
        @recipient-removed="handleToRecipientRemovedEvent"
        @recipient-selected="handleRecipientSelectedEvent"
      >
        <template #after>
          <div class="ml-2 space-x-2">
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

      <hr class="my-3 border-t border-neutral-200 dark:border-neutral-500/30" />

      <div v-if="wantsCc">
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

      <div v-if="wantsBcc">
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
        <div class="mr-1 min-w-14">
          <IFormLabel for="subject" :label="$t('mailclient::inbox.subject')" />
        </div>

        <div class="grow">
          <div class="relative">
            <IFormInput
              id="subject"
              :class="{
                'border-danger-600':
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

      <hr class="my-3 border-t border-neutral-200 dark:border-neutral-500/30" />

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
            :class="{
              'border-b border-neutral-200 dark:border-neutral-500/30':
                attachmentsBeingForwarded.length > 0 && attachments.length > 0,
            }"
            :items="attachmentsBeingForwarded"
            :authorize-delete="true"
            @delete-requested="removeAttachmentBeingForwarded"
          />

          <MediaItemsList
            class="mb-3"
            :items="attachments"
            :authorize-delete="true"
            @delete-requested="destroyPendingAttachment"
          />
        </MediaUpload>
      </div>

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
            :variant="showEmptyPlaceholdersMessage ? 'danger' : 'primary'"
            :loading="sending"
            :disabled="sendButtonIsDisabled"
            :text="
              showEmptyPlaceholdersMessage
                ? $t('core::app.confirm')
                : schedule
                  ? $t('mailclient::schedule.schedule')
                  : !forward
                    ? $t('mailclient::inbox.reply')
                    : $t('mailclient::inbox.forward')
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
import { computed, inject, nextTick, ref, toRaw, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTimeoutFn } from '@vueuse/core'
import findIndex from 'lodash/findIndex'

import AssociationsPopover from '@/Core/components/AssociationsPopover.vue'
import MediaItemsList from '@/Core/components/Media/MediaItemsList.vue'
import MediaUpload from '@/Core/components/Media/MediaUpload.vue'
import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { randomString } from '@/Core/utils'

import CreateFollowUpTask from '@/Activities/components/CreateFollowUpTask.vue'

import MailEditor from '../../components/MailEditor.vue'
import { useMessageComposer } from '../../composables/useMessageComposer'
import { useSignature } from '../../composables/useSignature'
import PredefinedMailTemplatesList from '../Templates/PredefinedMailTemplatesList.vue'

import MailRecipient from './RecipientSelectorField.vue'

const props = defineProps({
  resourceName: String,
  resourceId: [String, Number], // Needs to be provided if resourceName is provided
  relatedResource: Object, // Needs to be provided if resourceName is provided
  visible: Boolean,
  toAll: Boolean,
  forward: Boolean,
  message: { type: Object, required: true },
})

const synchronizeResource = inject('synchronizeResource', null)

const cleanSubjectSearch = [
  // Re
  'RE:',
  'SV:',
  'Antw:',
  'VS:',
  'RE:',
  'REF:',
  'ΑΠ:',
  'ΣΧΕΤ:',
  'Vá:',
  'R:',
  'RIF:',
  'BLS:',
  'RES:',
  'Odp:',
  'YNT:',
  'ATB:',
  // FW
  'FW:',
  'FWD:',
  'Doorst:',
  'VL:',
  'TR:',
  'WG:',
  'ΠΡΘ:',
  'Továbbítás:',
  'I:',
  'FS:',
  'TRS:',
  'VB:',
  'RV:',
  'ENC:',
  'PD:',
  'İLT:',
  'YML:',
]

const { t } = useI18n()
const { DateTime } = useDates()
const { addSignature } = useSignature()
const { scriptConfig } = useApp()

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
  hasEmptyPlaceholders,
  sending,
  sendRequest,
  hasInvalidAddresses,
  associateSelectedRecipients,
  dissociateRemovedRecipients,
  handleRecipientSelectedEvent,
  handleToRecipientRemovedEvent,
  setWantsCC,
  setWantsBCC,
  wantsBcc,
  wantsCc,
  schedule,
  messagePlaceholdersBeingParsed,
} = useMessageComposer(
  props.resourceName,
  props.relatedResource,
  synchronizeResource
)

const showEmptyPlaceholdersMessage = ref(false)
const subjectDragover = ref(false)

watch(hasEmptyPlaceholders, (newVal, oldVal) => {
  if (oldVal && !newVal) {
    showEmptyPlaceholdersMessage.value = false
  }
})

const toRef = ref(null)
const editorRef = ref(null)

const showTemplates = ref(false)
const attachmentsBeingForwarded = ref([])
const showParsedSubject = ref(false)

const sendButtonIsDisabled = computed(
  () =>
    form.to.length === 0 ||
    sending.value ||
    (schedule.value && !form.scheduled_at)
)

const hasCC = computed(() => props.message.cc && props.message.cc.length > 0)

const hasReplyTo = computed(
  () => props.message.reply_to && props.message.reply_to.length > 0
)

function scrollToTop() {
  document.querySelector('.dialog').scrollTo({ top: 0, behavior: 'instant' })
}

function removeAttachmentBeingForwarded(media) {
  const index = findIndex(attachmentsBeingForwarded.value, ['id', media.id])
  attachmentsBeingForwarded.value.splice(index, 1)
}

function handleTemplateSelected(template) {
  form.message = template.body + form.message
  showTemplates.value = false
  parsePlaceholdersForMessage()
  parsePlaceholdersForSubject()
  scrollToTop()
  nextTick(() => editorRef.value.focus())
}

function handleModalShown() {
  retrieveAssociationsAndSetInForm()

  attachmentsDraftId.value = randomString()
  let cleanSubject = cleanupSubject(props.message.subject)

  cleanSubject = props.forward
    ? handleForwarding(cleanSubject)
    : handleReply(cleanSubject)

  form.message = addSignature(form.message)
  subject.value = cleanSubject
}

function handleForwarding(cleanSubject) {
  attachmentsBeingForwarded.value = structuredClone(toRaw(props.message.media))

  resetRecipients()
  form.message = createForwardedMessageContent()

  if (cleanSubject) {
    cleanSubject = scriptConfig('mail.forward_prefix') + cleanSubject
  }

  toRef.value.focus()

  return cleanSubject
}

function handleReply(cleanSubject) {
  attachmentsBeingForwarded.value = []
  setRecipients()
  form.message = createQuoteOfPreviousMessage()

  useTimeoutFn(() => editorRef.value.focus(), 1200)

  if (cleanSubject) {
    cleanSubject = scriptConfig('mail.reply_prefix') + cleanSubject
  }

  return cleanSubject
}

function resetRecipients() {
  ;['cc', 'bcc', 'to'].forEach(key => form.set(key, key === 'to' ? [] : null))
}

function createForwardedMessageContent() {
  let forwardedContent =
    "<br /><div class='_attr'>" +
    t('mailclient::inbox.forwarded_message_placeholder', {
      from: `${
        props.message.from.name ? props.message.from.name + ' ' : ''
      }&lt;${props.message.from.address}&gt;`,
      date: DateTime.fromISO(props.message.date).toFormat(
        'EEE, LLL d, yyyy, h:mm a'
      ),
      subject: props.message.subject,
      to: props.message.to
        .reduce((carry, to) => {
          carry.push((to.name ? to.name + ' ' : '') + `&lt;${to.address}&gt;`)

          return carry
        }, [])
        .join(', '),
      pre: '----------',
      after: '---------',
    }) +
    '</div><br />'

  return prependToMessageText(props.message.editor_text, forwardedContent)
}

/**
 * Create quote from the message
 *
 * @return {String}
 */
function createQuoteOfPreviousMessage() {
  let body = props.message.editor_text

  // Maybe the message was empty?
  if (!body) return ''

  let from = `&lt;${props.message.from.address}&gt;`

  if (props.message.from.name) {
    from = props.message.from.name + ' ' + from
  }

  let wroteText = `On ${DateTime.fromISO(props.message.date).toFormat(
    'EEE, LLL d, yyyy, h:mm a'
  )} ${from} wrote:`

  if (isFullHtmlDocument(body)) {
    let bodyContentRegex = /(<body[^>]*>)([\s\S]*?)(<\/body>)/i

    let messageInBlockquote = body.replace(
      bodyContentRegex,
      function (fullMatch, openingBodyTag, bodyContent, closingBodyTag) {
        return (
          openingBodyTag +
          '<blockquote>' +
          bodyContent +
          '</blockquote>' +
          closingBodyTag
        )
      }
    )

    return prependToMessageText(
      messageInBlockquote,
      `<br /><div class="_attr">${wroteText}</div>`
    )
  } else {
    return `<br /><div class="_attr">${wroteText}</div><blockquote style="all: unset;" class="_quote">${body}</blockquote>`
  }
}

/**
 * Check if the given message content is full HTML document.
 * @param {String} content
 */
function isFullHtmlDocument(content) {
  let hasHtmlTag = content.includes('<html')
  let hasBodyTag = content.includes('<body')

  return hasHtmlTag && hasBodyTag
}

/**
 * Prepend text to the given message content.
 * @param {String} content
 * @param {String} prependText
 */
function prependToMessageText(content, prependText) {
  var bodyRegex = /<body[^>]*>/i

  if (isFullHtmlDocument(content)) {
    content = content.replace(bodyRegex, function (match) {
      return match + prependText
    })
  } else {
    content = prependText + content
  }

  return content
}

/**
 * Handle modal "hidden" event
 *
 * Reset the state, we need to reset the form and the
 * attachments because when the modal is hidden each time
 * new attachmentsDraftId is generated
 *
 * @return {Void}
 */
function handleModalHidden() {
  form.reset()
  subject.value = null
  schedule.value = false
  parsedSubject.value = null
  attachments.value = []
  customAssociationsValue.value = {}
}

/**
 * Send the message
 *
 * @return {Void}
 */
function send(skipEmptyPlaceholdersCheck = false) {
  if (skipEmptyPlaceholdersCheck === false && hasEmptyPlaceholders.value) {
    showEmptyPlaceholdersMessage.value = true

    return
  } else if (showEmptyPlaceholdersMessage.value) {
    showEmptyPlaceholdersMessage.value = false
  }

  if (!props.forward) {
    sendRequest(`/emails/${props.message.id}/reply`).then(() =>
      Innoclapps.dialog().hide('replyMessageModal')
    )
  } else {
    form.fill(
      'forward_attachments',
      attachmentsBeingForwarded.value.map(attach => attach.id)
    )

    sendRequest(`/emails/${props.message.id}/forward`).then(() =>
      Innoclapps.dialog().hide('replyMessageModal')
    )
  }
}

/**
 * Set reply to all
 */
function setReplyToAll() {
  if (isReplyingToOwnMessage()) {
    setRecipientsViaToHeader()
  } else if (!setRecipientsViaReplyToHeader()) {
    setRecipientsViaFromHeader()
  }

  if (hasCC.value) {
    const ccRecipients = getUniqueCCRecipientsForReplyToAll()

    if (ccRecipients.length > 0) {
      form.set('cc', ccRecipients)
    }
  }
}

function getUniqueCCRecipientsForReplyToAll() {
  let ccRecipients = props.message.cc

  // filter duplicates that exists in reply_to
  if (hasReplyTo.value) {
    ccRecipients = ccRecipients.filter(
      recipient =>
        !props.message.reply_to.some(
          replyTo => replyTo.address === recipient.address
        )
    )
  }

  return ccRecipients.map(recipient => ({
    address: recipient.address,
    name: recipient.name,
  }))
}

/**
 * Set the toa via reply to header
 */
function setRecipientsViaReplyToHeader() {
  if (hasReplyTo.value) {
    const recipients = props.message.reply_to.map(recipient => ({
      address: recipient.address,
      name: recipient.name,
    }))

    form.set('to', recipients)

    return true
  }

  return false
}

function setRecipientsViaToHeader() {
  form.set(
    'to',
    props.message.to.map(to => ({
      address: to.address,
      name: to.name,
    }))
  )
}

/**
 * Set the toa via the from header
 */
function setRecipientsViaFromHeader() {
  if (props.message.from) {
    // Maybe draft with no from header?
    form.set('to', [
      {
        address: props.message.from.address,
        name: props.message.from.name,
      },
    ])
  }
}

function setRecipients() {
  if (props.toAll) {
    setReplyToAll()

    return
  }

  // Replying to message sent via account e.q. via sent folder,
  // in this case, we will set the TO header via the actual recipient
  // e.q. sent a message, go to inbox, hit reply, it will use the original recipient
  // email not the account email, replying to a message sent via account, e.g., via sent folder.
  if (isReplyingToOwnMessage()) {
    setRecipientsViaToHeader()
  } else if (!setRecipientsViaReplyToHeader()) {
    setRecipientsViaFromHeader()
  }

  // Reset the CC in case of previous replyToAll clicked
  form.set('cc', null)
}

function isReplyingToOwnMessage() {
  const { message } = props

  return (
    (message.reply_to.length === 1 &&
      message.reply_to[0].address === message.email_account_email) ||
    (!hasReplyTo.value && message.from.address === message.email_account_email)
  )
}

function cleanupSubject(subject) {
  if (subject === null) {
    return subject
  }

  const regexSearch = [
    ...cleanSubjectSearch,
    scriptConfig('mail.reply_prefix'),
    scriptConfig('mail.forward_prefix'),
  ]

  return subject.replace(new RegExp(regexSearch.join('|'), 'gmi'), '').trim()
}

// This function ensures that form associations from the message being replied are filled before sending.
function retrieveAssociationsAndSetInForm() {
  Innoclapps.request(`/associations/emails/${props.message.id}`, {
    perPage: 100,
  }).then(({ data }) => {
    let associations = {}

    for (let resourceName in data) {
      associations[resourceName] = data[resourceName].map(record => record.id)
    }

    form.set('associations', associations)
  })
}
</script>
