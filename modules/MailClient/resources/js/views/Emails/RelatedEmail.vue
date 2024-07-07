<template>
  <ICard
    v-observe-visibility="{
      callback: handleVisibilityChanged,
      once: true,
      throttle: 300,
      intersection: {
        threshold: 0.5,
      },
    }"
    :class="'email-' + email.id"
  >
    <ICardHeader>
      <div
        class="flex flex-1 grow flex-col space-y-1 lg:flex-row lg:space-x-3 lg:space-y-0"
      >
        <div class="flex grow flex-col items-start">
          <ICardHeading
            :class="[
              'text-base/6 sm:text-sm/6',
              !email.is_read
                ? 'rounded-md bg-neutral-50 px-1.5 font-bold dark:bg-neutral-500/10'
                : 'font-medium',
            ]"
          >
            <span
              v-once
              v-text="
                email.subject
                  ? email.subject
                  : '(' + $t('mailclient::inbox.no_subject') + ')'
              "
            />
          </ICardHeading>

          <div class="inline-flex items-center space-x-3">
            <AssociationsPopover
              :associations-count="email.associations_count"
              :initial-associateables="relatedResource"
              :resource-id="email.id"
              :resource-name="resourceName"
              :primary-record="relatedResource"
              :primary-resource-name="viaResource"
              @synced="synchronizeResource({ emails: $event })"
            />

            <TagsSelectInput
              class="!w-auto"
              :simple="true"
              :type="TAGS_TYPE"
              :model-value="email.tags"
              :disabled="!email.authorizations?.update"
              @update:model-value="syncMessageTags"
            />
          </div>
        </div>

        <div class="flex items-center space-x-1 self-start">
          <IButton
            v-once
            class="lg:mt-1.5"
            variant="secondary"
            icon="Reply"
            :text="$t('mailclient::inbox.reply')"
            small
            @click="reply(true)"
          />

          <IButton
            v-if="hasMoreReplyTo"
            class="lg:mt-1.5"
            variant="secondary"
            icon="Reply"
            :text="$t('mailclient::inbox.reply_all')"
            small
            @click="replyAll"
          />

          <IButton
            v-once
            class="lg:mt-1.5"
            variant="secondary"
            icon="Share"
            :text="$t('mailclient::inbox.forward')"
            small
            @click="forward(true)"
          />
        </div>
      </div>

      <ICardActions class="mt-1.5 self-start">
        <IDropdownMinimal small>
          <IDropdownItem
            icon="Eye"
            :text="$t('mailclient::mail.view')"
            :to="{
              name: 'inbox-message',
              params: {
                id: email.id,
                account_id: email.email_account_id,
                folder_id: email.folders[0].id,
              },
            }"
          />

          <IDropdownItem
            icon="Trash"
            :text="$t('core::app.delete')"
            @click="$confirm(destroy)"
          />
        </IDropdownMinimal>
      </ICardActions>

      <IBadge
        v-if="email.opened_at"
        v-once
        class="absolute -bottom-6 right-2"
        variant="success"
        icon="Eye"
      >
        ({{ email.opens }}) -
        {{ localizedDateTime(email.opened_at) }}
      </IBadge>
    </ICardHeader>

    <ICardBody>
      <MessageAddresses
        v-once
        :label="$t('mailclient::inbox.from')"
        :addresses="email.from"
      />

      <div class="flex">
        <MessageAddresses
          v-once
          :label="$t('mailclient::inbox.to')"
          :addresses="email.to"
        />

        <div class="-mt-0.5 ml-3">
          <IPopover placement="top">
            <IPopoverButton link>
              {{ $t('core::app.details') }}
              <span aria-hidden="true">&rarr;</span>
            </IPopoverButton>

            <IPopoverPanel class="w-72">
              <div class="flex flex-col px-3 py-2">
                <MessageAddresses
                  v-once
                  :label="$t('mailclient::inbox.from')"
                  :addresses="email.from"
                />

                <MessageAddresses
                  v-once
                  :label="$t('mailclient::inbox.to')"
                  :addresses="email.to"
                />

                <MessageAddresses
                  v-once
                  :label="$t('mailclient::inbox.reply_to')"
                  :addresses="email.reply_to"
                  :show-when-empty="false"
                />

                <MessageAddresses
                  v-once
                  :label="$t('mailclient::inbox.cc')"
                  :addresses="email.cc"
                  :show-when-empty="false"
                />

                <MessageAddresses
                  v-once
                  :label="$t('mailclient::inbox.bcc')"
                  :addresses="email.bcc"
                  :show-when-empty="false"
                />
              </div>
            </IPopoverPanel>
          </IPopover>
        </div>
      </div>

      <div v-once class="inline-flex space-x-1">
        <ITextDark class="font-semibold">
          {{ $t('mailclient::inbox.date') }}:
        </ITextDark>

        <IText :text="localizedDateTime(email.date)" />
      </div>

      <div v-once class="mail-text all-revert">
        <div
          class="font-sans text-base leading-[initial] dark:text-white sm:text-sm"
        >
          <TextCollapse
            v-if="email.visible_text"
            class="mt-3"
            :text="email.visible_text"
            :length="250"
            lightbox
          />

          <div class="clear-both"></div>

          <HiddenText :text="email.hidden_text" />
        </div>
      </div>

      <div
        v-if="email.media.length > 0"
        v-once
        class="-mx-6 mb-3 mt-4 border-t border-neutral-200 px-6 pt-4 dark:border-neutral-500/30"
      >
        <ITextDark
          class="mb-2 font-medium"
          as="dd"
          :text="$t('mailclient.mail.attachments')"
        />

        <MessageAttachments :attachments="email.media" />
      </div>
    </ICardBody>

    <MessageReply
      :message="email"
      :visible="isReplying || isForwarding"
      :forward="isForwarding"
      :resource-name="viaResource"
      :resource-id="viaResourceId"
      :related-resource="relatedResource"
      :to-all="replyToAll"
      @hidden="handleReplyModalHidden"
    />
  </ICard>
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import { ObserveVisibility } from 'vue-observe-visibility'
import { useStore } from 'vuex'

import AssociationsPopover from '@/Core/components/AssociationsPopover.vue'
import TagsSelectInput from '@/Core/components/TagsSelectInput.vue'
import { useDates } from '@/Core/composables/useDates'
import { useResourceable } from '@/Core/composables/useResourceable'

import { useMessageTags } from '../../composables/useMessageTags'

import MessageAddresses from './MessageAddresses.vue'
import MessageAttachments from './MessageAttachments.vue'
import HiddenText from './MessageHiddenText.vue'
import MessageReply from './MessageReply.vue'

const props = defineProps({
  viaResource: { type: String, required: true },
  viaResourceId: { type: [String, Number], required: true },
  relatedResource: { type: Object, required: true },
  email: { type: Object, required: true },
})

const resourceName = Innoclapps.resourceName('emails')

const synchronizeResource = inject('synchronizeResource')
const decrementResourceCount = inject('decrementResourceCount')

const vObserveVisibility = ObserveVisibility

const store = useStore()
const { deleteResource } = useResourceable(resourceName)
const { localizedDateTime } = useDates()
const { TAGS_TYPE, syncTags } = useMessageTags()

const isReplying = ref(false)
const isForwarding = ref(false)
const replyToAll = ref(false)

const hasMoreReplyTo = computed(
  () => props.email.cc && props.email.cc.length > 0
)

function handleReplyModalHidden() {
  reply(false)
  forward(false)
}

function handleVisibilityChanged(isVisible) {
  if (isVisible && !props.email.is_read) {
    Innoclapps.request()
      .post(`/emails/${props.email.id}/read`)
      .then(({ data }) => {
        synchronizeResource({ emails: data })
        decrementResourceCount('unread_emails_for_user_count')
        store.dispatch('emailAccounts/decrementUnreadCountUI')
      })
  }
}

async function destroy() {
  await deleteResource(props.email.id)

  if (!props.email.is_read) {
    decrementResourceCount('unread_emails_for_user_count')
    store.dispatch('emailAccounts/decrementUnreadCountUI')
  }

  synchronizeResource({ emails: { id: props.email.id, _delete: true } })
}

function reply(state = true) {
  isReplying.value = state
  replyToAll.value = false
}

function forward(state = true) {
  isForwarding.value = state
}

function replyAll() {
  isReplying.value = true
  replyToAll.value = true
}

/**
 * Sync the message tags.
 */
async function syncMessageTags(tags) {
  let data = await syncTags(props.email.id, tags)
  synchronizeResource({ emails: { id: data.id, tags: { _reset: data.tags } } })
}
</script>
