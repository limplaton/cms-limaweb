<template>
  <IModal
    id="boardSortModal"
    size="sm"
    :ok-disabled="!sortBy.field"
    :ok-text="$t('core::app.apply')"
    hide-header
    form
    @submit="apply"
  >
    <ITextDark class="mb-2 font-semibold">
      {{ $t('deals::deal.sort_by') }}
    </ITextDark>

    <div class="flex">
      <div class="mr-2 grow">
        <IFormSelect v-model="sortBy.field">
          <option
            v-t="'deals::fields.deals.expected_close_date'"
            value="expected_close_date"
          />

          <option v-t="'core::app.creation_date'" value="created_at" />

          <option v-t="'deals::fields.deals.amount'" value="amount" />

          <option v-t="'deals::deal.name'" value="name" />
        </IFormSelect>
      </div>

      <div>
        <IFormSelect v-model="sortBy.direction">
          <option value="asc">
            Asc (<span v-t="'core::app.ascending'"></span>)
          </option>

          <option value="desc">
            Desc (<span v-t="'core::app.descending'"></span>)
          </option>
        </IFormSelect>
      </div>
    </div>
  </IModal>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['applied'])

const sortBy = ref({
  field: null,
  direction: 'asc',
})

function hideModal() {
  Innoclapps.dialog().hide('boardSortModal')
}

function apply() {
  emit('applied', sortBy.value)
  hideModal()
}
</script>
