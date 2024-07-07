<template>
  <WorkflowsForm
    v-if="editOrCreate"
    :workflow="workflow"
    :triggers="triggers"
    @hide="editOrCreate = false"
    @update:workflow="$emit('update:workflow', $event)"
    @delete-requested="$emit('deleteRequested', $event)"
  />

  <div :class="{ 'opacity-70': !workflow.is_active, hidden: editOrCreate }">
    <div class="flex items-center px-4 py-5 sm:px-6">
      <div class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between">
        <div class="truncate">
          <div class="flex">
            <ILink
              class="flex items-center truncate font-medium"
              @click="editOrCreate = true"
            >
              {{ workflow.title }}

              <Icon icon="ArrowRight" class="ml-1 size-4" />
            </ILink>
          </div>

          <div class="sm:flex sm:items-center sm:space-x-4">
            <ITextDark>
              {{
                $t('core::workflow.total_executions', {
                  total: workflow.total_executions || 0,
                })
              }}
            </ITextDark>

            <IText v-if="workflow.created_at" class="truncate">
              {{ $t('core::app.created_at') }}:
              {{ localizedDateTime(workflow.created_at) }}
            </IText>
          </div>
        </div>

        <div class="mt-2 shrink-0 sm:ml-5 sm:mt-0">
          <IFormSwitchField>
            <IFormSwitchLabel :text="$t('core::app.active')" />

            <IFormSwitch
              :model-value="workflow.is_active"
              @change="handleWorkflowActiveChangeEvent"
            />
          </IFormSwitchField>
        </div>
      </div>

      <IDropdownMinimal class="ml-2 shrink-0 self-start sm:self-auto">
        <IDropdownItem
          icon="PencilAlt"
          :text="$t('core::app.edit')"
          @click="editOrCreate = true"
        />

        <IDropdownItem
          icon="Trash"
          :text="$t('core::app.delete')"
          @click="requestDelete"
        />
      </IDropdownMinimal>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useDates } from '@/Core/composables/useDates'

import WorkflowsForm from './WorkflowsForm.vue'

const props = defineProps({
  triggers: { required: true, type: Array },
  workflow: { required: true, type: Object },
})

const emit = defineEmits(['update:workflow', 'deleteRequested'])

const { t } = useI18n()
const { localizedDateTime } = useDates()

const editOrCreate = ref(false)

function handleWorkflowActiveChangeEvent(value) {
  Innoclapps.request()
    .put(`/workflows/${props.workflow.id}`, {
      ...{ ...props.workflow, ...{ data: {} } },
      ...props.workflow.data,
      ...{ is_active: value },
    })
    .then(({ data }) => {
      emit('update:workflow', { ...data, key: props.workflow.key })
      Innoclapps.success(t('core::workflow.updated'))
    })
}

function requestDelete() {
  emit('deleteRequested', props.workflow)
}

if (!props.workflow.id) {
  editOrCreate.value = true
}
</script>
