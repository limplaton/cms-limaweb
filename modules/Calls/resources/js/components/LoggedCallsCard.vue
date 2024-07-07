<template>
  <IModal
    id="readCallOutcomeModal"
    v-model:visible="outcomeModalVisible"
    size="sm"
    :title="$t('calls.call.outcome.call_outcome')"
  >
    <!-- eslint-disable -->
    <ITextBlock
      v-html="outcomeBeingRead"
    />
    <!-- eslint-enable -->
    <template #modal-footer="{ cancel }">
      <div class="text-right">
        <IButton
          variant="secondary"
          :text="$t('core::app.hide')"
          @click="cancel"
        />
      </div>
    </template>
  </IModal>

  <CardAsyncTable :card="card">
    <template #date="{ formatted, row }">
      <IText>
        {{ row.user.name }} -
        <ILink class="inline-flex items-center" @click="readOutcome(row.body)">
          <span v-text="formatted"></span>

          <Icon icon="Window" class="ml-2 size-4" />
        </ILink>
      </IText>

      <div
        v-for="(associations, resourceName) in row.associations"
        :key="resourceName"
      >
        <div
          v-for="association in associations"
          :key="association.id"
          class="flex space-x-2"
        >
          <ILink
            class="font-normal underline underline-offset-2"
            :to="association.path"
            :text="association.display_name"
            basic
          />
        </div>
      </div>
    </template>
    <!-- eslint-disable-next-line vue/valid-v-slot -->
    <template #outcome.name="{ row, formatted }">
      <IBadge
        class="shrink-0"
        :text="formatted"
        :color="row.outcome.swatch_color"
      />
    </template>
  </CardAsyncTable>
</template>

<script setup>
import { ref } from 'vue'

defineProps({ card: Object })

const outcomeBeingRead = ref(null)
const outcomeModalVisible = ref(false)

function readOutcome(outcome) {
  outcomeBeingRead.value = outcome
  outcomeModalVisible.value = true
}
</script>
