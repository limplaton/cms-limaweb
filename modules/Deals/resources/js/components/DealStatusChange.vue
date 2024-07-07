<template>
  <div v-if="dealStatus === 'open'">
    <div class="inline-flex">
      <IButton
        v-if="dealStatus !== 'won'"
        v-i-tooltip="$t('deals::deal.actions.mark_as_won')"
        variant="success"
        class="mr-1.5 px-5"
        :loading="requestInProgress['won']"
        :disabled="requestInProgress['won']"
        :text="$t('deals::deal.status.won')"
        @click="changeStatus('won')"
      />

      <IPopover v-if="dealStatus !== 'lost'" :placement="lostPopoverPlacement">
        <IPopoverButton variant="danger" class="px-5">
          <span
            v-i-tooltip="$t('deals::deal.actions.mark_as_lost')"
            v-t="'deals::deal.status.lost'"
          />
        </IPopoverButton>

        <IPopoverPanel class="w-72 lg:w-[22rem]">
          <IPopoverHeader>
            <IPopoverHeading :text="$t('deals::deal.actions.mark_as_lost')" />
          </IPopoverHeader>

          <IPopoverBody class="flex flex-col">
            <IFormGroup
              label-for="lost_reason"
              :label="$t('deals::deal.lost_reasons.lost_reason')"
              :optional="!setting('lost_reason_is_required')"
              :required="setting('lost_reason_is_required')"
            >
              <LostReasonField v-model="form.lost_reason" />

              <IFormError :error="form.getError('lost_reason')" />
            </IFormGroup>

            <IButton
              variant="danger"
              :loading="requestInProgress['lost']"
              :disabled="requestInProgress['lost']"
              :text="$t('deals::deal.actions.mark_as_lost')"
              block
              @click="changeStatus('lost')"
            />
          </IPopoverBody>
        </IPopoverPanel>
      </IPopover>
    </div>
  </div>

  <div v-else class="flex items-center space-x-2">
    <IBadge
      :variant="dealStatus === 'won' ? 'success' : 'danger'"
      :icon="dealStatus === 'won' ? 'CheckBadge' : 'XSolid'"
      :text="$t('deals::deal.status.' + dealStatus)"
    />

    <IButton
      variant="secondary"
      :disabled="requestInProgress['open']"
      :loading="requestInProgress['open']"
      :text="$t('deals::deal.reopen')"
      @click="changeStatus('open')"
    />
  </div>
</template>

<script setup>
import { reactive } from 'vue'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { throwConfetti } from '@/Core/utils'

import LostReasonField from './DealLostReasonField.vue'

const props = defineProps({
  dealId: { type: Number, required: true },
  dealStatus: { type: String, required: true },
  lostPopoverPlacement: { type: String, default: 'bottom' },
})

const emit = defineEmits(['updated'])

const { setting } = useApp()

const { form } = useForm({ lost_reason: null }, { resetOnSuccess: true })

const { updateResource } = useResourceable(Innoclapps.resourceName('deals'))

const requestInProgress = reactive({
  won: false,
  lost: false,
  open: false,
})

async function changeStatus(status) {
  requestInProgress[status] = true

  try {
    let updatedDeal = await updateResource(form.fill({ status }), props.dealId)

    if (status === 'won') {
      throwConfetti()
    }

    emit('updated', updatedDeal)
  } finally {
    requestInProgress[status] = false
  }
}
</script>
