<template>
  <div
    class="h-dropper fixed bottom-0 left-0 right-0 border-t border-neutral-300 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800 sm:left-56"
  >
    <IModal
      id="markAsLostModal"
      ok-variant="danger"
      size="sm"
      :title="$t('deals::deal.actions.mark_as_lost')"
      :ok-disabled="changeStatusForm.busy"
      :ok-text="$t('deals::deal.actions.mark_as_lost')"
      form
      @submit="markAsLost(markingAsLostID)"
      @hidden="markAsLostModalHidden"
    >
      <IFormGroup
        label-for="lost_reason"
        :label="$t('deals::deal.lost_reasons.lost_reason')"
        :optional="!setting('lost_reason_is_required')"
        :required="setting('lost_reason_is_required')"
      >
        <LostReasonField v-model="changeStatusForm.lost_reason" />

        <IFormError :error="changeStatusForm.getError('lost_reason')" />
      </IFormGroup>
    </IModal>

    <div class="flex justify-end">
      <div
        class="h-dropper relative w-1/3 border-t-2 border-success-500 sm:w-1/5"
      >
        <SortableDraggable
          class="h-dropper dropper-won dropper"
          :model-value="[]"
          :item-key="item => item.id"
          :group="{ group: 'won', put: true, pull: false }"
          @change="movedToWon"
        >
          <template #item><div></div></template>

          <template #header>
            <div
              v-t="'deals::deal.status.won'"
              class="dropper-header h-dropper absolute inset-0 flex place-content-center items-center font-medium text-neutral-600 dark:text-neutral-200"
            />
          </template>
        </SortableDraggable>
      </div>

      <div
        class="h-dropper relative w-1/3 border-t-2 border-danger-500 sm:w-1/5"
      >
        <SortableDraggable
          class="h-dropper dropper-lost dropper"
          :model-value="[]"
          :item-key="item => item.id"
          :group="{ name: 'lost', put: true, pull: false }"
          @change="movedToLost"
        >
          <template #item><div></div></template>

          <template #header>
            <div
              v-t="'deals::deal.status.lost'"
              class="dropper-header h-dropper absolute inset-0 flex place-content-center items-center font-medium text-neutral-600 dark:text-neutral-200"
            />
          </template>
        </SortableDraggable>
      </div>

      <div
        class="h-dropper relative w-1/3 border-t-2 border-neutral-800 sm:w-1/5"
      >
        <SortableDraggable
          class="h-dropper dropper-delete dropper"
          :model-value="[]"
          :item-key="item => item.id"
          :group="{ name: 'delete', put: true, pull: false }"
          async
          @change="movedToDelete"
        >
          <template #item><div></div></template>

          <template #header>
            <div
              v-t="'core::app.delete'"
              class="dropper-header h-dropper absolute inset-0 flex place-content-center items-center font-medium text-neutral-600 dark:text-neutral-200"
            />
          </template>
        </SortableDraggable>
      </div>
    </div>
  </div>
</template>

<script setup>
// https://stackoverflow.com/questions/51619243/vue-draggable-delete-item-by-dragging-into-designated-region
import { ref } from 'vue'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { throwConfetti } from '@/Core/utils'

import LostReasonField from './DealLostReasonField.vue'

defineProps({
  resourceId: { required: true },
})

const emit = defineEmits(['deleted', 'won', 'refreshRequested'])

const { setting } = useApp()

const markingAsLostID = ref(null)

const { form: changeStatusForm } = useForm(
  { lost_reason: null },
  { resetOnSuccess: true }
)

const { updateResource, deleteResource } = useResourceable(
  Innoclapps.resourceName('deals')
)

/**
 * Handle deal moved to delete dropper
 */
async function movedToDelete(e) {
  if (e.added) {
    try {
      await Innoclapps.confirm()
      await deleteResource(e.added.element.id)
      emit('deleted', e.added.element)
    } catch {
      refresh()
    }
  }
}

/**
 * Request board refresh
 */
function refresh() {
  emit('refreshRequested')
}

/**
 * Handle deal moved to lost dropper
 */
function movedToLost(e) {
  if (e.added) {
    markingAsLostID.value = e.added.element.id
    Innoclapps.dialog().show('markAsLostModal')
  }
}

/**
 * Handle the mark as lost modal hidden event
 */
function markAsLostModalHidden() {
  changeStatusForm.reset()
  changeStatusForm.errors.clear()
  markingAsLostID.value = null
  refresh()
}

/**
 * Mark the deal as lost
 */
function markAsLost(id) {
  updateResource(changeStatusForm.fill({ status: 'lost' }), id).then(() =>
    Innoclapps.dialog().hide('markAsLostModal')
  )
}

/**
 * Mark the deal as lost
 */
function markAsWon(id) {
  updateResource(changeStatusForm.fill({ status: 'won' }), id)
    .then(deal => {
      throwConfetti()
      emit('won', deal)
      refresh()
    })
    .catch(() => refresh())
}

/**
 * Handle deal moved to won dropper
 */
function movedToWon(e) {
  if (e.added) {
    markAsWon(e.added.element.id)
  }
}
</script>

<style>
.h-dropper {
  height: 75px;
}

.dropper .bottom-hidden {
  display: none;
}

.dropper-delete .sortable-chosen.sortable-ghost::before {
  background: black;
  content: ' ';
  min-height: 55px;
  min-width: 100%;
  display: block;
}

.dropper-lost .sortable-chosen.sortable-ghost::before {
  background: rgba(var(--color-danger-600));
  content: ' ';
  min-height: 55px;
  min-width: 100%;
  display: block;
}

.dropper-won .sortable-chosen.sortable-ghost::before {
  background: rgba(var(--color-success-600));
  content: ' ';
  min-height: 55px;
  min-width: 100%;
  display: block;
}
</style>
