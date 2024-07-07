<template>
  <div
    :class="{
      'sticky top-0 bg-opacity-75 p-2 backdrop-blur-lg backdrop-filter dark:bg-neutral-500/80':
        !messageInfoIsFullyVisible,
    }"
  />

  <div class="sticky top-2 z-auto">
    <div
      :class="[
        !messageInfoIsFullyVisible
          ? 'border-t border-neutral-200 bg-neutral-50 dark:border-neutral-500/30 dark:bg-neutral-800'
          : 'rounded-t-lg bg-white dark:bg-neutral-900',
        'overflow-hidden shadow',
      ]"
    >
      <div
        class="flex flex-col border-b border-neutral-200 p-4 dark:border-neutral-800 sm:flex-row sm:items-center sm:px-6"
        :class="{ 'pointer-events-none blur': isLoading }"
      >
        <div class="mr-3 grow">
          <h5
            class="text-lg/6 font-semibold text-neutral-700 dark:text-white"
            v-text="subject"
          />

          <div class="mt-1.5 inline-flex items-center">
            <div class="mr-4 flex-none">
              <AssociationsPopover
                ref="associationsPopoverRef"
                v-model:associations-count="message.associations_count"
                class="inline-flex"
                placement="bottom-start"
                :resource-name="resourceName"
                :resource-id="message.id"
              />
            </div>

            <TagsSelectInput
              :simple="true"
              :type="TAGS_TYPE"
              :model-value="message.tags"
              :disabled="!message.authorizations?.update"
              @update:model-value="syncMessageTags"
            />
          </div>
        </div>

        <div class="mt-2 shrink-0 self-start sm:mt-0">
          <div v-if="componentReady" class="flex items-center space-x-2">
            <div v-show="!account.is_sync_stopped">
              <ActionSelector
                type="dropdown"
                :ids="message.id"
                :actions="message.actions"
                :action-request-params="actionRequestParams"
                :resource-name="resourceName"
                @run="handleActionExecuted"
              />
            </div>

            <IButton
              v-if="canReply"
              variant="secondary"
              icon="Reply"
              :disabled="account.is_sync_stopped"
              :text="$t('mailclient::inbox.reply')"
              @click="reply(true)"
            />
            <!-- TODO, find reply-all icon -->
            <IButton
              v-if="canReply && hasMoreReplyTo"
              variant="secondary"
              icon="Reply"
              :disabled="account.is_sync_stopped"
              :text="$t('mailclient::inbox.reply_all')"
              @click="replyAll()"
            />

            <IButton
              v-if="canReply"
              variant="secondary"
              icon="Share"
              :disabled="account.is_sync_stopped"
              :text="$t('mailclient::inbox.forward')"
              @click="forward(true)"
            />

            <IDropdownMinimal horizontal>
              <IDropdownItem
                icon="Users"
                :text="$t('contacts::contact.convert')"
                @click="showCreateContactForm"
              />

              <IDropdownItem
                icon="Banknotes"
                :text="$t('deals::deal.create')"
                @click="showCreateDealForm"
              />

              <IDropdownItem
                icon="Calendar"
                :text="$t('activities::activity.create')"
                @click="prepareActivityCreate"
              />
            </IDropdownMinimal>
          </div>
        </div>
      </div>
    </div>
  </div>

  <ICard class="-mt-1 mb-3" :overlay="isLoading">
    <ICardBody>
      <div id="messageInfo" class="flex pt-1">
        <div v-show="componentReady" class="mr-2">
          <IAvatar :src="message.avatar_url" />
        </div>

        <div :class="['grow', !componentReady ? 'blur' : '']">
          <MessageAddresses
            :label="$t('mailclient::inbox.from')"
            :addresses="message.from"
          />

          <MessageAddresses
            :label="$t('mailclient::inbox.reply_to')"
            :addresses="message.reply_to"
            :show-when-empty="false"
          />

          <MessageAddresses
            :label="$t('mailclient::inbox.to')"
            :addresses="message.to"
          />

          <MessageAddresses
            :label="$t('mailclient::inbox.cc')"
            :addresses="message.cc"
            :show-when-empty="false"
          />

          <MessageAddresses
            :label="$t('mailclient::inbox.bcc')"
            :addresses="message.bcc"
            :show-when-empty="false"
          />

          <div class="mt-2 inline-flex">
            <ITextDark class="mr-1 font-semibold">
              {{ $t('mailclient::inbox.date') }}:
            </ITextDark>

            <IText :text="localizedDateTime(message.date)" />
          </div>
        </div>

        <div
          class="inline-flex flex-1 flex-wrap justify-end space-y-1 self-start sm:flex-nowrap sm:items-center sm:space-x-2 sm:space-y-0"
        >
          <IBadge
            v-for="folder in message.folders"
            :key="folder.id"
            class="flex shrink-0"
            variant="info"
          >
            <span class="size-1 rounded-full bg-info-500" />
            {{ folder.display_name }}
          </IBadge>

          <IBadge v-if="message.opened_at" variant="success" icon="Eye">
            ({{ message.opens }})

            <span
              class="hidden sm:block"
              v-text="' - ' + localizedDateTime(message.opened_at)"
            />
          </IBadge>
        </div>
      </div>

      <div v-if="!isLoading" class="mt-6">
        <MessagePreview
          :visible-text="message.visible_text"
          :hidden-text="message.hidden_text"
        />
      </div>
    </ICardBody>
  </ICard>

  <div v-if="hasAttachments" class="mb-3 mt-5">
    <ITextDark
      class="mb-2 font-medium"
      as="dd"
      :text="$t('mailclient.mail.attachments')"
    />

    <MessageAttachments :attachments="message.media" />
  </div>

  <MessageReply
    v-if="canReply"
    :message="message"
    :visible="isReplying || isForwarding"
    :to-all="replyToAll"
    :forward="isForwarding"
    @hidden="replyModalHidden"
  />

  <CreateDealModal
    v-model:visible="dealIsBeingCreated"
    :associations="{ emails: [message.id] }"
    :contacts="relatedContacts"
    :name="message.subject"
    @created="
      (dealIsBeingCreated = false),
        $refs.associationsPopoverRef.retrieveAssociatedResources()
    "
  />

  <CreateContactModal
    v-model:visible="contactIsBeingCreated"
    :associations="{ emails: [message.id] }"
    v-bind="createContactBindings"
    @hidden="createContactBindings = {}"
    @created="
      (contactIsBeingCreated = false),
        $refs.associationsPopoverRef.retrieveAssociatedResources()
    "
  />

  <CreateActivityModal
    v-if="activityIsBeingCreated"
    :contacts="activityCreateContacts"
    :title="
      $t('activities::activity.title_via_create_message', {
        subject: message.subject,
      })
    "
    :message="message"
    @created="activityIsBeingCreated = false"
    @hidden="activityIsBeingCreated = false"
  />
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'

import ActionSelector from '@/Core/components/Actions/ActionSelector.vue'
import AssociationsPopover from '@/Core/components/AssociationsPopover.vue'
import TagsSelectInput from '@/Core/components/TagsSelectInput.vue'
import { useDates } from '@/Core/composables/useDates'
import { useLoader } from '@/Core/composables/useLoader'
import { usePageTitle } from '@/Core/composables/usePageTitle'

import { useMessageTags } from '../../composables/useMessageTags'
import MessageAddresses from '../Emails/MessageAddresses.vue'
import MessageAttachments from '../Emails/MessageAttachments.vue'
import MessagePreview from '../Emails/MessagePreview.vue'
import MessageReply from '../Emails/MessageReply.vue'

const props = defineProps({
  account: { required: true, type: Object },
})

const resourceName = Innoclapps.resourceName('emails')

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const store = useStore()

const pageTitle = usePageTitle()
const { localizedDateTime } = useDates()
const { setLoading, isLoading } = useLoader()
const { TAGS_TYPE, syncTags } = useMessageTags()

const associationsPopoverRef = ref(null)

const message = ref({
  _placeholder: true,
  avatar_url: '',
  from: { name: '', address: '' },
  to: [],
  cc: [],
  bcc: [],
})

const dealIsBeingCreated = ref(false)
const contactIsBeingCreated = ref(false)
const activityIsBeingCreated = ref(false)
const isReplying = ref(false)
const isForwarding = ref(false)
const replyToAll = ref(false)
const messageInfoIsFullyVisible = ref(true)
const relatedContacts = ref([])
const activityCreateContacts = shallowRef([])
const createContactBindings = ref({})

let scrollObserver = null

/**
 * Checks whether the component is ready based if the message
 * data has keys, if don't means that it's not yet fetched
 */
const componentReady = computed(() => message.value._placeholder !== true)

const hasMoreReplyTo = computed(
  () => message.value.cc && message.value.cc.length > 0
)

/**
 * Provides the actions request query string
 */
const actionRequestParams = computed(() => ({
  folder_id: route.params.folder_id,
  account_id: route.params.account_id,
}))

/**
 * Check whether a reply can be performed to this message
 */
const canReply = computed(() => componentReady.value && !message.value.is_draft)

/**
 * Get the message subject
 */
const subject = computed(() => {
  if (!message.value.subject) {
    return t('mailclient::inbox.no_subject')
  }

  return message.value.subject
})

/**
 * Check whether the message has attachments
 */
const hasAttachments = computed(
  () => componentReady.value && message.value.media.length > 0
)

/**
 * Get the message route
 */
const messageRoute = computed(
  () => `/inbox/emails/folders/${route.params.folder_id}/${route.params.id}`
)

/**
 * Get the total unread messages for all accounts
 */
const totalUnreadMessages = computed(
  () => store.getters.getSidebarMenuItem('inbox').badge
)

/**
 * Prepare data for activity create
 */
async function prepareActivityCreate() {
  let { data: contacts } = await Innoclapps.request('contacts/search', {
    params: {
      q: message.value.from.address,
      search_fields: 'email:=',
    },
  })

  activityCreateContacts.value = contacts
  activityIsBeingCreated.value = true
}

/**
 * Sync the message tags.
 */
async function syncMessageTags(tags) {
  let data = await syncTags(message.value.id, tags)
  message.value.tags = data.tags
}

/**
 * Initiate show create deal form
 */
async function showCreateDealForm() {
  relatedContacts.value = await searchRelatedContacts()
  dealIsBeingCreated.value = true
}

/**
 * Initiate show create contact form
 */
function showCreateContactForm() {
  contactIsBeingCreated.value = true
  createContactBindings.value['email'] = message.value.from.address

  if (message.value.from.name) {
    createContactBindings.value['first-name'] =
      message.value.from.name.split(' ')[0]

    createContactBindings.value['last-name'] =
      message.value.from.name.split(' ')[1] || null
  }
}

/**
 * Search the related message contacts
 */
async function searchRelatedContacts() {
  let { data: contacts } = await Innoclapps.request('contacts/search', {
    params: {
      q: message.value.from.address,
      search_fields: 'email:=',
    },
  })

  return contacts
}

/**
 * Handle the reply modal hidden event
 *
 * @return {Void}
 */
function replyModalHidden() {
  reply(false)
  forward(false)
}

/**
 * Handle action executed event
 *
 * @param  {Object} action
 *
 * @return {Void}
 */
function handleActionExecuted(action) {
  // After a move action is executed we need to change the route
  // to the actual new folder, to prevent showing error message e.q. MessageNotFound
  // when executing move action again as the old folder will be passed to the params request
  if (action.uriKey === 'email-account-message-move') {
    replaceMessageRouteFolder(action.response.moved_to_folder_id)
    getMessageSilently()
  } else if (action.uriKey === 'email-account-message-delete') {
    // Message parmanently deleted, navigate to inbox
    if (
      parseInt(route.params.folder_id) ===
      parseInt(props.account.trash_folder.id)
    ) {
      router.replace({ name: 'inbox' })
    } else {
      replaceMessageRouteFolder(props.account.trash_folder.id)
      getMessageSilently()
    }
  } else {
    getMessageSilently()
  }
}

/**
 * Replace the current route folder id
 *
 * @param  {Number} folderId
 *
 * @return {Void}
 */
function replaceMessageRouteFolder(folderId) {
  router.replace({
    name: 'inbox-message',
    params: {
      account_id: props.account.id,
      folder_id: folderId,
      id: message.value.id,
    },
  })
}

/**
 * Change reply data state
 *
 * @param  {Boolean} state
 *
 * @return {Void}
 */
function reply(state = true) {
  isReplying.value = state
  replyToAll.value = false
}

/**
 * Change forward data state
 *
 * @param  {Boolean} state
 *
 * @return {Void}
 */
function forward(state = true) {
  isForwarding.value = state
}

/**
 * Initialize reply all to mesage
 *
 * @return {Void}
 */
function replyAll() {
  isReplying.value = true
  replyToAll.value = true
}

/**
 * Get the message without loading indicator
 */
function getMessageSilently() {
  return getMessage(true)
}

/**
 * Get the message from storage
 *
 * @return {Void}
 */
async function getMessage(silent = false) {
  setLoading(!silent)

  let { data } = await Innoclapps.request(messageRoute.value, {
    // Pass params for the actions
    params: {
      silent: silent,
      account_id: props.account.id,
      folder_id: route.params.folder_id,
    },
  })

  message.value = data

  // Update the active folder so unread/read keys
  // can be updated too for the folders menu
  store.commit('emailAccounts/UPDATE', {
    id: props.account.id,
    item: {
      ...props.account,
      active_folders_tree: data.account_active_folders_tree,
    },
  })

  if (data.was_unread === true) {
    store.dispatch(
      'emailAccounts/updateUnreadCountUI',
      totalUnreadMessages.value === 0 ? 0 : totalUnreadMessages.value - 1
    )
  }

  pageTitle.value = subject.value
  setLoading(false)
}

getMessage()

onMounted(() => {
  scrollObserver = new IntersectionObserver(
    entries => {
      messageInfoIsFullyVisible.value = entries[0].isIntersecting
    },
    {
      root: document.getElementById('main'),
      threshold: 1,
    }
  )
  scrollObserver.observe(document.getElementById('messageInfo'))
})

onBeforeUnmount(() => {
  if (scrollObserver) {
    scrollObserver.unobserve(document.getElementById('messageInfo'))
    scrollObserver = null
  }
})
</script>
