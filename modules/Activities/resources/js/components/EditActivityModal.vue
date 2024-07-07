<template>
  <ISlideover
    id="editActivityModal"
    :ok-disabled="form.busy || !resource.authorizations?.update"
    :ok-text="$t('core::app.save')"
    :hide-footer="activeTabIndex === 1"
    :title="resource.title"
    :sub-title="modalSubTitle"
    visible
    form
    static-backdrop
    @submit="updateUsing(form)"
  >
    <template #title="{ title }">
      <div v-if="isReady" class="flex items-center">
        <ActivityStateChange
          class="mr-1 mt-0.5 self-start"
          tooltip-placement="bottom"
          :is-completed="resource.is_completed"
          :disabled="!resource.authorizations.update"
          :activity-id="computedId"
          @changed="synchronizeResource($event, true)"
        />

        <span>
          {{ title }}
        </span>
      </div>
    </template>

    <div v-if="isReady" class="absolute -top-3 right-5 sm:top-2">
      <ActionSelector
        type="dropdown"
        :ids="actionId"
        :actions="actions"
        :resource-name="resourceName"
        @run="$emit('actionExecuted', $event)"
      />
    </div>

    <FieldsPlaceholder v-if="!isReady" />

    <div v-else class="mt-10 sm:mt-0">
      <ITabGroup v-model="activeTabIndex">
        <ITabList>
          <ITab :title="$t('activities::activity.activity')" />

          <ITab
            :title="
              $t('comments::comment.comments') +
              ' (' +
              resource.comments_count +
              ')'
            "
            @activated.once="loadComments"
          />
        </ITabList>

        <ITabPanels>
          <ITabPanel>
            <FormFields
              :fields="fields"
              :form="form"
              :resource-id="computedId"
              :resource-name="resourceName"
              @update-field-value="form.fill($event.attribute, $event.value)"
              @set-initial-value="form.set($event.attribute, $event.value)"
            >
              <template #after-deals-field>
                <ILink
                  class="-mt-1 block text-right"
                  @click="dealBeingCreated = true"
                >
                  &plus; {{ $t('deals::deal.create') }}
                </ILink>
              </template>

              <template #after-companies-field>
                <ILink
                  class="-mt-1 block text-right"
                  @click="companyBeingCreated = true"
                >
                  &plus; {{ $t('contacts::company.create') }}
                </ILink>
              </template>

              <template #after-contacts-field>
                <ILink
                  class="-mt-1 block text-right"
                  @click="contactBeingCreated = true"
                >
                  &plus; {{ $t('contacts::contact.create') }}
                </ILink>
              </template>
            </FormFields>

            <div class="mt-5">
              <ITextDisplay>
                {{ $t('core::app.attachments') }}

                <IText
                  v-if="resource.media.length > 0"
                  class="ml-0.5 inline"
                  :text="'(' + resource.media.length + ')'"
                />
              </ITextDisplay>

              <ResourceRecordMediaList
                :resource-name="resourceName"
                :resource-id="computedId"
                :media="resource.media"
                :authorize-delete="resource.authorizations.update"
                @deleted="
                  synchronizeResource({
                    media: { id: $event.id, _delete: true },
                  })
                "
                @uploaded="synchronizeResource({ media: [$event] })"
              />
            </div>
          </ITabPanel>

          <ITabPanel lazy>
            <div class="my-3 text-right">
              <CommentsAdd
                commentable-type="activities"
                :commentable-id="computedId"
                @created="
                  incrementResourceCount('comments_count'),
                    synchronizeResourceSilently({
                      comments: [$event],
                    })
                "
              />
            </div>

            <IOverlay :show="!commentsAreLoaded">
              <CommentsList
                v-if="commentsAreLoaded"
                commentable-type="activities"
                :comments="resource.comments || []"
                :commentable-id="computedId"
                :auto-focus-if-required="true"
                @updated="
                  synchronizeResourceSilently({
                    comments: $event,
                  })
                "
                @deleted="
                  decrementResourceCount('comments_count'),
                    synchronizeResourceSilently({
                      comments: { id: $event, _delete: true },
                    })
                "
              />
            </IOverlay>
          </ITabPanel>
        </ITabPanels>
      </ITabGroup>
    </div>

    <CreateDealModal
      v-model:visible="dealBeingCreated"
      :overlay="false"
      @created="
        handleAssociateableAdded('deals', $event.deal),
          (dealBeingCreated = false)
      "
    />

    <CreateContactModal
      v-model:visible="contactBeingCreated"
      :overlay="false"
      @created="
        handleAssociateableAdded('contacts', $event.contact),
          (contactBeingCreated = false)
      "
      @restored="
        handleAssociateableAdded('contacts', $event),
          (contactBeingCreated = false)
      "
    />

    <CreateCompanyModal
      v-model:visible="companyBeingCreated"
      :overlay="false"
      @created="
        handleAssociateableAdded('companies', $event.company),
          (companyBeingCreated = false)
      "
      @restored="
        handleAssociateableAdded('companies', $event),
          (companyBeingCreated = false)
      "
    />
  </ISlideover>
</template>

<script setup>
import { computed, ref } from 'vue'
import { inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'

import ActionSelector from '@/Core/components/Actions/ActionSelector.vue'
import ResourceRecordMediaList from '@/Core/components/Media/ResourceRecordMediaList.vue'
import { useDates } from '@/Core/composables/useDates'
import { useForm } from '@/Core/composables/useForm'

import { useComments } from '@/Comments/composables/useComments'

import ActivityStateChange from './ActivityStateChange.vue'

const props = defineProps({
  resource: { required: true, type: Object },
  fields: { required: true, type: Array },
  isReady: { required: true, type: Boolean },
  updateUsing: { required: true, type: Function },
})

defineEmits(['actionExecuted'])

const resourceName = Innoclapps.resourceName('activities')

const { t } = useI18n()
const route = useRoute()

const { form } = useForm()
const { localizedDateTime } = useDates()

const dealBeingCreated = ref(false)
const contactBeingCreated = ref(false)
const companyBeingCreated = ref(false)
const activeTabIndex = ref(route.query.comment_id ? 1 : 0)

const computedId = computed(() => props.resource.id)
const actionId = computed(() => computedId.value || [])
const actions = computed(() => props.resource.actions || [])

const modalSubTitle = computed(() => {
  if (!props.isReady) return ''

  return `${t('core::app.created_at')}: ${localizedDateTime(
    props.resource.created_at
  )} - ${props.resource.creator.name}`
})

const synchronizeResourceSilently = inject('synchronizeResourceSilently')
const synchronizeResource = inject('synchronizeResource')
const hydrateFields = inject('hydrateFields')
const incrementResourceCount = inject('incrementResourceCount')
const decrementResourceCount = inject('decrementResourceCount')

const { getAllComments, commentsAreLoaded } = useComments(
  computedId,
  resourceName
)

async function loadComments() {
  synchronizeResourceSilently({ comments: await getAllComments() })
  commentsAreLoaded.value = true
}

function handleAssociateableAdded(attribute, record) {
  synchronizeResource({ [attribute]: record })
  hydrateFields({ [attribute]: record })
  form[attribute].push(record.id)
}
</script>
