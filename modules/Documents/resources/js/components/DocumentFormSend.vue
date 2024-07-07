<template>
  <div v-show="visible" class="mx-auto max-w-3xl">
    <IAlert v-if="Boolean(form.originalData.send_at)" class="mb-5">
      <IAlertBody>
        {{
          $t('documents::document.send.is_scheduled', {
            date: localizedDateTime(form.originalData.send_at),
          })
        }}
      </IAlertBody>
    </IAlert>

    <div v-if="form.requires_signature" class="mb-6">
      <ITextDisplay
        :text="$t('documents::document.send.send_to_signers')"
        :class="[
          'mb-3',
          {
            hidden:
              filledSigners.length === 0 && document.status === 'accepted',
          },
        ]"
      />

      <IText
        v-show="filledSigners.length === 0 && document.status !== 'accepted'"
        class="-mt-3"
        :text="$t('documents::document.send.send_to_signers_empty')"
      />

      <IFormCheckboxField
        v-for="signer in filledSigners"
        :key="signer.email"
        class="gap-y-0"
      >
        <IFormCheckbox v-model:checked="signer.send_email" />

        <IFormCheckboxLabel>
          {{ signer.name + ' (' + signer.email + ')' }}
        </IFormCheckboxLabel>

        <IFormCheckboxDescription v-if="signer.sent_at" class="-mt-1">
          {{
            $t('documents::document.sent_at', {
              date: localizedDateTime(signer.sent_at),
            })
          }}
        </IFormCheckboxDescription>
      </IFormCheckboxField>
    </div>

    <div class="mb-3 inline-flex items-center">
      <ITextDisplay :text="$t('documents::document.recipients.recipients')" />

      <IText class="ml-1 mt-1">
        ({{ $t('documents::document.recipients.additional_recipients') }})
      </IText>
    </div>

    <ITableOuter>
      <ITable>
        <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
          <ITableRow>
            <ITableHeader class="w-0 !p-0" />

            <ITableHeader
              v-t="'documents::document.recipients.recipient_name'"
            />

            <ITableHeader
              v-t="'documents::document.recipients.recipient_email'"
            />

            <ITableHeader
              v-t="'documents::document.recipients.is_sent'"
              class="text-center"
            />

            <ITableHeader class="w-0 !p-0" />
          </ITableRow>
        </ITableHead>

        <ITableBody>
          <ITableRow v-if="form.recipients.length === 0">
            <ITableCell colspan="5" class="align-middle">
              <IText class="ml-2">
                {{ $t('documents::document.recipients.no_recipients') }}
              </IText>
            </ITableCell>
          </ITableRow>

          <ITableRow v-for="(recipient, index) in form.recipients" :key="index">
            <ITableCell
              class="border-r border-neutral-900/5 dark:border-white/5"
            >
              <span
                class="ml-1 inline-flex min-w-full items-center justify-center"
              >
                <IFormCheckbox v-model:checked="recipient.send_email" />
              </span>
            </ITableCell>

            <ITableCell class="align-top">
              <IFormInput
                ref="recipientNameInputRef"
                v-model="recipient.name"
                :placeholder="
                  $t('documents::document.recipients.enter_full_name')
                "
              />

              <IFormError
                :error="form.getError('recipients.' + index + '.name')"
              />
            </ITableCell>

            <ITableCell class="align-top">
              <IFormInput
                v-model="recipient.email"
                type="email"
                :placeholder="$t('documents::document.recipients.enter_email')"
                @keyup.enter="insertEmptyRecipient"
              />

              <IFormError
                :error="form.getError('recipients.' + index + '.email')"
              />
            </ITableCell>

            <ITableCell class="align-middle">
              <span class="mt-1.5 inline-flex min-w-full justify-center">
                <span
                  v-i-tooltip="
                    recipient.sent_at
                      ? localizedDateTime(recipient.sent_at)
                      : null
                  "
                  :class="[
                    'inline-block size-4 rounded-full',
                    recipient.sent_at ? 'bg-success-400' : 'bg-danger-400',
                  ]"
                />
              </span>
            </ITableCell>

            <ITableCell class="align-middle">
              <IButton
                class="mt-0.5"
                icon="XSolid"
                basic
                @click="removeRecipient(index)"
              />
            </ITableCell>
          </ITableRow>
        </ITableBody>
      </ITable>
    </ITableOuter>

    <ILink
      v-show="!emptyRecipientsExists"
      class="mt-3 inline-block font-medium"
      @click="insertEmptyRecipient"
    >
      &plus; {{ $t('documents::document.recipients.add') }}
    </ILink>

    <ITextDisplay
      class="mb-3 mt-6"
      :text="$t('documents::document.send.send')"
    />

    <IFormLabel
      v-show="!selectedBrand"
      as="p"
      class="mt-3"
      :text="$t('documents::document.send.select_brand')"
    />

    <div v-if="selectedBrand">
      <IFormGroup
        label-for="send_mail_account_id"
        :label="$t('documents::document.send.send_from_account')"
      >
        <ICustomSelect
          v-if="emailAccounts.length"
          input-id="send_mail_account_id"
          label="email"
          :model-value="selectedEmailAccount"
          :clearable="false"
          :options="emailAccounts"
          @update:model-value="form.send_mail_account_id = $event.id"
          @option-selected="setActiveEmailAccount(emailAccounts)"
        />

        <IText v-else class="mt-2 inline-flex items-center">
          <Icon
            icon="ExclamationTriangle"
            class="mr-1 size-5 text-warning-500"
          />
          {{ $t('documents::document.send.connect_an_email_account') }}
        </IText>
      </IFormGroup>

      <IFormGroup
        label-for="send_mail_subject"
        class="mt-4"
        :label="$t('documents::document.send.send_subject')"
      >
        <IFormInput id="send_mail_subject" v-model="form.send_mail_subject" />
      </IFormGroup>

      <IFormGroup
        label-for="send_mail_body"
        :label="$t('documents::document.send.send_body')"
      >
        <Editor
          id="send_mail_body"
          v-model="form.send_mail_body"
          :with-image="false"
          absolute-urls
        />
      </IFormGroup>

      <div class="mb-4">
        <div class="inline-block">
          <IFormCheckboxField>
            <IFormCheckbox
              v-model:checked="scheduleSend"
              :disabled="!document.id"
              @update:checked="!$event ? (form.send_at = null) : ''"
            />

            <IFormCheckboxLabel
              :text="$t('documents::document.send.send_later')"
            />

            <IFormCheckboxDescription v-if="!document.id">
              {{ $t('documents::document.send.save_to_schedule') }}
            </IFormCheckboxDescription>
          </IFormCheckboxField>
        </div>

        <DatePicker
          v-if="scheduleSend && document.id"
          v-model="form.send_at"
          class="mt-3"
          min-date="now"
          mode="dateTime"
          :placeholder="$t('documents::document.send.select_schedule_date')"
          :required="true"
        />
      </div>

      <span
        v-i-tooltip="
          document.authorizations && !document.authorizations.update
            ? $t('core::app.action_not_authorized')
            : ''
        "
        class="inline-block"
      >
        <IButton
          v-show="!scheduleSend"
          variant="primary"
          icon="Mail"
          :loading="sending"
          :text="$t('core::app.send')"
          :disabled="
            sending ||
            !isEligibleForSending ||
            (document.authorizations && !document.authorizations.update)
          "
          @click="$emit('sendRequested')"
        />

        <IButton
          v-show="scheduleSend"
          variant="primary"
          icon="Clock"
          :text="$t('documents::document.send.schedule')"
          :disabled="
            form.busy ||
            !isEligibleForSending ||
            !form.send_at ||
            (document.authorizations && !document.authorizations.update)
          "
          @click="$emit('saveRequested')"
        />
      </span>
    </div>
  </div>
</template>

<!-- eslint-disable vue/no-mutating-props -->
<script setup>
import { computed, inject, nextTick, ref, watch } from 'vue'
import find from 'lodash/find'

import { useDates } from '@/Core/composables/useDates'
import { isBlank } from '@/Core/utils'

import { useEmailAccounts } from '@/MailClient/composables/useEmailAccounts'

import propsDefinition from './formSectionProps'

const props = defineProps({
  ...propsDefinition,
  sending: Boolean,
})

defineEmits(['sendRequested', 'saveRequested'])

const { localizedDateTime } = useDates()
const { emailAccounts, fetchEmailAccounts } = useEmailAccounts()

const brands = inject('brands')

const recipientNameInputRef = ref(null)
const selectedEmailAccount = ref(null)
const scheduleSend = ref(Boolean(props.form.send_at))

const selectedBrand = computed(() => {
  if (!props.form.brand_id) {
    return null
  }

  return find(brands.value, ['id', parseInt(props.form.brand_id)])
})

const filledSigners = computed(() =>
  props.form.signers.filter(
    signer => !isBlank(signer.name) && !isBlank(signer.email)
  )
)

const filledAndEnabledRecipients = computed(() =>
  props.form.recipients.filter(
    recipient =>
      recipient.send_email &&
      !isBlank(recipient.name) &&
      !isBlank(recipient.email)
  )
)

const filledAndEnabledSignersRecipients = computed(() =>
  props.form.signers.filter(
    signer =>
      signer.send_email && !isBlank(signer.name) && !isBlank(signer.email)
  )
)

const emptyRecipientsExists = computed(
  () =>
    props.form.recipients.filter(
      recipient => isBlank(recipient.name) || isBlank(recipient.email)
    ).length > 0
)

const isEligibleForSending = computed(
  () =>
    !(
      !props.form.send_mail_body ||
      !props.form.send_mail_subject ||
      !props.form.send_mail_account_id ||
      (filledAndEnabledRecipients.value.length === 0 &&
        filledAndEnabledSignersRecipients.value.length === 0)
    )
)

function removeRecipient(index) {
  props.form.recipients.splice(index, 1)
}

function insertEmptyRecipient() {
  props.form.recipients.push({ name: '', email: '', send_email: true })

  nextTick(() => {
    recipientNameInputRef.value[props.form.recipients.length - 1].focus()
  })
}

function setActiveEmailAccount(accounts) {
  if (props.form.send_mail_account_id) {
    selectedEmailAccount.value = find(accounts, [
      'id',
      parseInt(props.form.send_mail_account_id),
    ])
  } else {
    selectedEmailAccount.value = accounts.length ? accounts[0] : null

    if (selectedEmailAccount.value) {
      props.form.send_mail_account_id = selectedEmailAccount.value.id
    }
  }
}

watch(
  selectedBrand,
  (newVal, oldVal) => {
    if (
      (newVal && isBlank(props.form.send_mail_body)) ||
      (oldVal &&
        props.form.send_mail_body ===
          oldVal.config.document.mail_message[props.form.locale])
    ) {
      props.form.send_mail_body =
        newVal.config.document.mail_message[props.form.locale]
    }

    if (
      (newVal && isBlank(props.form.send_mail_subject)) ||
      (oldVal &&
        oldVal.config.document.mail_subject[props.form.locale] ===
          props.form.send_mail_subject)
    ) {
      props.form.send_mail_subject =
        newVal.config.document.mail_subject[props.form.locale]
    }
  },
  { immediate: true }
)

fetchEmailAccounts().then(setActiveEmailAccount)
</script>
