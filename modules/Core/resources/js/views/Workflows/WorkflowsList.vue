<template>
  <ICardHeader>
    <ICardHeading :text="$t('core::workflow.workflows')" />

    <ICardActions>
      <IButton
        v-show="hasDefinedWorkflows"
        variant="primary"
        icon="PlusSolid"
        :disabled="workflowBeingCreated"
        :text="$t('core::workflow.create')"
        @click="add"
      />
    </ICardActions>
  </ICardHeader>

  <ICard :overlay="!componentReady">
    <template v-if="componentReady">
      <TransitionGroup
        v-if="hasDefinedWorkflows"
        name="flip-list"
        tag="ul"
        class="divide-y divide-neutral-200 dark:divide-neutral-500/30"
      >
        <li v-for="workflow in orderedWorkflows" :key="workflow.key">
          <WorkflowsListItem
            :workflow="workflow"
            :triggers="availableTriggers"
            @update:workflow="setWorkflowInList"
            @delete-requested="destroy"
          />
        </li>
      </TransitionGroup>

      <ICardBody v-else>
        <IEmptyState
          :button-text="$t('core::workflow.create')"
          :description="$t('core::workflow.info')"
          @click="add"
        />
      </ICardBody>
    </template>
  </ICard>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import findIndex from 'lodash/findIndex'
import orderBy from 'lodash/orderBy'

import { randomString } from '@/Core/utils'

import WorkflowsListItem from './WorkflowsListItem.vue'

const { t } = useI18n()

const availableTriggers = ref([])
const workflows = ref([])
const componentReady = ref(false)

const orderedWorkflows = computed(() =>
  orderBy(
    workflows.value,
    [
      item => (item.id == null ? 0 : 1), // null or undefined IDs first
      item => (item.is_active ? 1 : 0), // active items next
      'title', // then sort by title
    ],
    [
      'asc', // We want null IDs at the beginning, so we sort ascending (since we're using 0 for null IDs)
      'desc',
      'asc',
    ]
  )
)

const workflowBeingCreated = computed(
  () => workflows.value.filter(workflow => !workflow.id).length > 0
)

const hasDefinedWorkflows = computed(() => workflows.value.length > 0)

function add() {
  workflows.value.unshift({
    key: randomString(),
    title: '',
    description: null,
    is_active: false,
    trigger_type: null,
    action_type: null,
  })
}

function removeWorkflowFromList(key) {
  workflows.value.splice(findIndex(workflows.value, ['key', key]), 1)
}

function setWorkflowInList(workflow) {
  let index = findIndex(workflows.value, ['key', workflow.key])
  workflows.value[index] = workflow
}

function destroy(workflow) {
  if (!workflow.id) {
    removeWorkflowFromList(workflow.key)
  } else {
    Innoclapps.confirm().then(() => makeDestroyRequest(workflow))
  }
}

function makeDestroyRequest(workflow) {
  Innoclapps.request()
    .delete(`/workflows/${workflow.id}`)
    .then(() => {
      removeWorkflowFromList(workflow.key)

      Innoclapps.success(t('core::workflow.deleted'))
    })
}

Promise.all([
  Innoclapps.request('/workflows'),
  Innoclapps.request('/workflows/triggers'),
]).then(responses => {
  workflows.value.push(
    ...responses[0].data.map(w => ({ ...w, key: randomString() }))
  )
  availableTriggers.value = responses[1].data
  componentReady.value = true
})
</script>
