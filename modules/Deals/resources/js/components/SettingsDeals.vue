<template>
  <div class="space-y-8">
    <div>
      <PipelinesIndex />
    </div>

    <div>
      <ICardHeader>
        <ICardHeading :text="$t('deals::deal.deals')" />
      </ICardHeader>

      <ICard :overlay="!componentReady">
        <ICardBody>
          <IFormSwitchGroup>
            <IFormSwitchField>
              <IFormSwitchLabel
                :text="$t('deals::deal.settings.allow_lost_reason_enter')"
              />

              <IFormSwitchDescription
                :text="$t('deals::deal.settings.allow_lost_reason_enter_info')"
              />

              <IFormSwitch
                v-model="form.allow_lost_reason_enter"
                @change="submit"
              />
            </IFormSwitchField>

            <IFormSwitchField>
              <IFormSwitchLabel
                :text="$t('deals::deal.settings.lost_reason_is_required')"
              />

              <IFormSwitchDescription
                :text="$t('deals::deal.settings.lost_reason_is_required_info')"
              />

              <IFormSwitch
                v-model="form.lost_reason_is_required"
                @change="submit"
              />
            </IFormSwitchField>
          </IFormSwitchGroup>
        </ICardBody>
      </ICard>
    </div>

    <div>
      <LostReasonsIndex />
    </div>
  </div>
</template>

<script setup>
import { useSettings } from '@/Core/composables/useSettings'

import LostReasonsIndex from '../views/DealsLostReasonsIndex.vue'
import PipelinesIndex from '../views/DealsPipelinesIndex.vue'

const { form, submit, isReady: componentReady } = useSettings()
</script>
