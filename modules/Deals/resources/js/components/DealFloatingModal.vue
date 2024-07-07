<template>
  <ISlideover
    id="dealFloatingModal"
    :title="resource.display_name"
    :sub-title="modalSubTitle"
    static-backdrop
    form
    @submit="updateHandler(form)"
  >
    <FieldsPlaceholder v-if="!floatingReady" />

    <div v-else class="flex flex-col">
      <div
        class="mb-7 rounded-lg border px-4 py-3 md:px-5"
        :class="{
          'border-primary-100 bg-primary-50/60 text-primary-800 dark:border-primary-400/20 dark:bg-primary-800/10 dark:text-primary-400':
            resource.status === 'open',
          'border-danger-100 bg-danger-50/50 text-danger-800 dark:border-danger-400/20 dark:bg-danger-800/10 dark:text-danger-400':
            resource.status === 'lost',
          'border-success-300/30 bg-success-50/50 text-success-800 dark:border-success-400/20 dark:bg-success-900/10 dark:text-success-400':
            resource.status === 'won',
        }"
      >
        <div class="flex flex-col items-center sm:-mt-1 md:flex-row">
          <div class="shrink-0 text-base sm:text-sm md:mr-3">
            <DealStagePopover
              :deal-id="resource.id"
              :pipeline="resource.pipeline"
              :stage-id="resource.stage_id"
              :status="resource.status"
              :authorized-to-update="resource.authorizations.update"
              @updated="synchronizeAndEmitUpdatedEvent($event, true)"
            />
          </div>

          <p
            class="text-base/5 opacity-90 sm:text-sm/5 md:hidden"
            v-text="beenInStageText"
          />

          <DealStatusChange
            class="my-2 md:my-0 md:ml-auto"
            lost-popover-placement="bottom-end"
            :deal-id="resource.id"
            :deal-status="resource.status"
            @updated="synchronizeAndEmitUpdatedEvent($event, true)"
          />
        </div>

        <p
          class="-mt-1.5 hidden text-center text-base/5 opacity-90 sm:text-sm/5 md:block md:text-left"
          v-text="beenInStageText"
        />
      </div>

      <FieldsButtonCollapse
        v-if="totalCollapsableFields > 0"
        v-model:collapsed="fieldsCollapsed"
        class="mb-2 ml-auto"
        :total="totalCollapsableFields"
      />

      <FormFields
        v-if="mode === 'edit'"
        :fields="fields"
        :form="form"
        :resource-name="resourceName"
        :resource-id="resource.id"
        :collapsed="fieldsCollapsed"
        is-floating
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      />

      <DetailFields
        v-else
        :fields="fields"
        :resource-name="resourceName"
        :resource-id="resource.id"
        :resource="resource"
        :collapsed="fieldsCollapsed"
        is-floating
        @updated="synchronizeAndEmitUpdatedEvent($event, true)"
      />

      <div
        v-show="mode === 'detail'"
        class="mt-6 h-2 border-t border-neutral-200 dark:border-neutral-500/30"
      />

      <div
        :class="[
          'mt-6 space-y-6 sm:space-y-8',
          mode === 'detail' ? 'sm:px-10' : 'px-1',
        ]"
      >
        <div>
          <DealContactsList
            :float-mode="mode"
            :deal-id="resource.id"
            :contacts="resource.contacts"
            :authorize-dissociate="resource.authorizations.update"
            :show-create-button="resource.authorizations.update"
            @dissociated="
              ensureFieldsAndFormIsSynced('contacts', resource.contacts),
                synchronizeAndEmitUpdatedEvent({
                  contacts: { id: $event, _delete: true },
                })
            "
            @create-requested="contactBeingCreated = true"
          />
        </div>

        <div>
          <DealCompaniesList
            :float-mode="mode"
            :deal-id="resource.id"
            :companies="resource.companies"
            :authorize-dissociate="resource.authorizations.update"
            :show-create-button="resource.authorizations.update"
            @dissociated="
              ensureFieldsAndFormIsSynced('companies', resource.companies),
                synchronizeAndEmitUpdatedEvent({
                  companies: { id: $event, _delete: true },
                })
            "
            @create-requested="companyBeingCreated = true"
          />
        </div>

        <div>
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
            :resource-id="resource.id"
            :media="resource.media"
            :authorize-delete="resource.authorizations.update"
            is-floating
            @uploaded="synchronizeAndEmitUpdatedEvent({ media: [$event] })"
            @deleted="
              synchronizeAndEmitUpdatedEvent({
                media: { id: $event.id, _delete: true },
              })
            "
          />
        </div>
      </div>

      <CreateContactModal
        v-model:visible="contactBeingCreated"
        :overlay="false"
        :deals="[resource]"
        @created="handleContactCreated"
        @restored="handleContactCreated"
      />

      <CreateCompanyModal
        v-model:visible="companyBeingCreated"
        :overlay="false"
        :deals="[resource]"
        @created="handleCompanyCreated"
        @restored="handleCompanyCreated"
      />
    </div>

    <template #modal-footer>
      <div class="flex justify-end space-x-2">
        <IButton
          variant="secondary"
          :disabled="!floatingReady"
          :text="$t('core::app.view_record')"
          @click="$emit('viewRequested', $event)"
        />

        <IButton
          v-if="mode === 'edit'"
          type="submit"
          variant="primary"
          :loading="form.busy"
          :text="$t('core::app.save')"
          :disabled="
            !floatingReady || form.busy || !resource.authorizations.update
          "
        />
      </div>
    </template>
  </ISlideover>
</template>

<script setup>
import { computed, inject, nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import ResourceRecordMediaList from '@/Core/components/Media/ResourceRecordMediaList.vue'
import { useDates } from '@/Core/composables/useDates'
import { useForm } from '@/Core/composables/useForm'

import DealCompaniesList from './DealCompaniesList.vue'
import DealContactsList from './DealContactsList.vue'
import DealStagePopover from './DealStagePopover.vue'
import DealStatusChange from './DealStatusChange.vue'

const props = defineProps({
  resource: { required: true, type: Object },
  fields: { required: true, type: Array },
  mode: { required: true, type: String },
  floatingReady: { required: true, type: Boolean },
  updateHandler: { required: true, type: Function },
})

defineEmits(['viewRequested'])

const resourceName = Innoclapps.resourceName('deals')

const { form } = useForm()

const fieldsCollapsed = ref(true)
const companyBeingCreated = ref(false)
const contactBeingCreated = ref(false)

const synchronizeAndEmitUpdatedEvent = inject('synchronizeAndEmitUpdatedEvent')
const hydrateFields = inject('hydrateFields')
const totalCollapsableFields = inject('totalCollapsableFields')

const { t } = useI18n()
const { humanizeDuration, localizedDateTime } = useDates()

const modalSubTitle = computed(() => {
  if (!props.floatingReady) return ''

  return `${t('core::app.created_at')} ${localizedDateTime(
    props.resource.created_at
  )}`
})

const beenInStageText = computed(() => {
  return t('deals::deal.been_in_stage_time', {
    time: humanizeDuration(
      props.resource.time_in_stages[props.resource.stage.id]
    ),
  })
})

function ensureFieldsAndFormIsSynced(attribute, data) {
  hydrateFields({ [attribute]: data })

  form.fill(
    attribute,
    data.map(r => r.id)
  )
}

function handleCompanyCreated(data) {
  companyBeingCreated.value = false
  synchronizeAndEmitUpdatedEvent({ companies: data.company })

  nextTick(() => {
    ensureFieldsAndFormIsSynced('companies', props.resource.companies)
  })
}

function handleContactCreated(data) {
  contactBeingCreated.value = false
  synchronizeAndEmitUpdatedEvent({ contacts: data.contact })

  nextTick(() => {
    ensureFieldsAndFormIsSynced('contacts', props.resource.contacts)
  })
}
</script>
