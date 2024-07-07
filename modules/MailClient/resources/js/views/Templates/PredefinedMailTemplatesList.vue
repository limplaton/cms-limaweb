<template>
  <div>
    <div v-show="!isCreatingOrEditing">
      <div class="mb-3 text-right">
        <IButton
          variant="primary"
          :text="$t('mailclient::mail.templates.create')"
          @click="initiateCreate"
        />
      </div>

      <ITable class="[--gutter:theme(spacing.8)]" bleed>
        <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
          <ITableRow>
            <ITableHeader width="50%">
              {{ $t('mailclient.mail.templates.name') }}
            </ITableHeader>

            <ITableHeader>
              {{ $t('core::app.created_by') }}
            </ITableHeader>

            <ITableHeader>
              {{ $t('core::app.last_modified_at') }}
            </ITableHeader>
          </ITableRow>
        </ITableHead>

        <ITableBody>
          <ITableRow
            v-for="(template, index) in templatesByName"
            :key="template.id"
          >
            <ITableCell width="50%">
              <div class="flex">
                <div class="grow">
                  <ILink @click="initiateEdit(template.id)">
                    {{ template.name }}
                  </ILink>

                  <ITextSmall class="-mt-1">
                    {{ $t('mailclient::mail.templates.subject') }}:
                    {{ template.subject }}
                  </ITextSmall>
                </div>

                <div class="flex items-center space-x-2">
                  <IButton
                    variant="secondary"
                    :text="$t('mailclient::mail.templates.select')"
                    small
                    @click="handleTemplateSelected(index)"
                  />

                  <IDropdownMinimal
                    v-if="
                      template.authorizations.update ||
                      template.authorizations.delete
                    "
                  >
                    <IDropdownItem
                      v-if="template.authorizations.update"
                      :text="$t('core::app.edit')"
                      @click="initiateEdit(template.id)"
                    />

                    <IDropdownItem
                      v-if="template.authorizations.delete"
                      :text="$t('core::app.delete')"
                      :confirm-text="$t('core::app.confirm')"
                      confirmable
                      @confirmed="destroy(template.id)"
                    />
                  </IDropdownMinimal>
                </div>
              </div>
            </ITableCell>

            <ITableCell>
              {{ template.user.name }}
            </ITableCell>

            <ITableCell>
              {{ localizedDateTime(template.updated_at) }}
            </ITableCell>
          </ITableRow>

          <ITableRow v-show="!hasTemplates">
            <ITableCell class="p-5 text-center" :colspan="3">
              <IText v-t="'core::table.empty'" />
            </ITableCell>
          </ITableRow>
        </ITableBody>
      </ITable>
    </div>

    <PredefinedMailTemplatesCreate
      v-if="creatingTemplate"
      @cancel-requested="creatingTemplate = false"
      @created="handleTemplateCreatedEvent"
    />

    <PredefinedMailTemplatesEdit
      v-if="templateBeingUpdated"
      :template-id="templateBeingUpdated"
      @updated="handleTemplateUpdatedEvent"
      @cancel-requested="templateBeingUpdated = null"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useDates } from '@/Core/composables/useDates'

import { useMailTemplates } from '../../composables/useMailTemplates'

import PredefinedMailTemplatesCreate from './PredefinedMailTemplatesCreate.vue'
import PredefinedMailTemplatesEdit from './PredefinedMailTemplatesEdit.vue'

const emit = defineEmits([
  'selected',
  'updated',
  'created',
  'willEdit',
  'willCreate',
])

const { t } = useI18n()
const { localizedDateTime } = useDates()
const { templatesByName, deleteTemplate } = useMailTemplates()

const templateBeingUpdated = ref(null)
const creatingTemplate = ref(false)

const hasTemplates = computed(() => templatesByName.value.length > 0)

const isCreatingOrEditing = computed(
  () => templateBeingUpdated.value || creatingTemplate.value
)

function initiateEdit(id) {
  emit('willEdit', id)
  templateBeingUpdated.value = id
}

function initiateCreate() {
  creatingTemplate.value = true
  emit('willCreate')
}

function handleTemplateCreatedEvent(template) {
  creatingTemplate.value = false
  emit('created', template)
}

function handleTemplateUpdatedEvent(template) {
  templateBeingUpdated.value = false
  emit('updated', template)
}

async function destroy(id) {
  await deleteTemplate(id)

  Innoclapps.success(t('mailclient::mail.templates.deleted'))
}

function handleTemplateSelected(index) {
  emit('selected', templatesByName.value[index])
}
</script>
