<template>
  <MainLayout class="dashboard-edit">
    <template #actions>
      <NavbarSeparator v-show="componentReady" class="hidden lg:block" />

      <IButton
        v-show="componentReady"
        variant="primary"
        :disabled="form.busy"
        :text="$t('core::app.save')"
        @click="update"
      />
    </template>

    <div class="mx-auto max-w-7xl lg:max-w-6xl">
      <form @submit.prevent="update">
        <IOverlay :show="!componentReady">
          <IFormGroup
            label-for="dashboard-name"
            :label="$t('core::dashboard.name')"
            required
          >
            <IFormInput id="dashboard-name" v-model="form.name" />

            <IFormError :error="form.getError('name')" />
          </IFormGroup>

          <IFormGroup v-if="canChangeDefaultState">
            <IFormCheckboxField>
              <IFormCheckbox v-model:checked="form.is_default" />

              <IFormCheckboxLabel :text="$t('core::dashboard.default')" />
            </IFormCheckboxField>

            <IFormError :error="form.getError('is_default')" />
          </IFormGroup>
        </IOverlay>

        <div
          v-for="card in cardsWithConfigApplied"
          :key="card.uriKey"
          class="mb-6 mt-8"
        >
          <IFormCheckboxField class="mb-2">
            <IFormCheckbox v-model:checked="status[card.uriKey]" />

            <IFormCheckboxLabel :text="$t('core::dashboard.cards.enabled')" />
          </IFormCheckboxField>

          <IText
            v-if="card.description"
            class="my-2"
            :text="card.description"
          />

          <div class="pointer-events-none block h-full w-full opacity-70">
            <component :is="card.component" :card="card" />
          </div>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import find from 'lodash/find'

import { useApp } from '@/Core/composables/useApp'
import { useCards } from '@/Core/composables/useCards'
import { useForm } from '@/Core/composables/useForm'
import { usePageTitle } from '@/Core/composables/usePageTitle'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const store = useStore()
const { currentUser } = useApp()
const { cards: allCardsForDashboard, fetchCards, applyUserConfig } = useCards()
const pageTitle = usePageTitle()

const status = ref({})
const componentReady = ref(false)

const { form } = useForm({
  cards: [],
  name: null,
  is_default: false,
})

const allUserDashboards = computed(() => currentUser.value.dashboards)

const totalDashboards = computed(() => allUserDashboards.value.length)

const dashboardBeingEdited = computed(() =>
  find(allUserDashboards.value, ['id', parseInt(route.params.id)])
)

const cardsWithConfigApplied = computed(() =>
  applyUserConfig(allCardsForDashboard.value, dashboardBeingEdited.value)
)

const canChangeDefaultState = computed(() => {
  // Allow to set as default on the last dashboard which is not default
  if (totalDashboards.value === 1) return true

  return (
    totalDashboards.value > 1 && dashboardBeingEdited.value.is_default === false
  )
})

function update() {
  // Map the status values in the form
  form.set(
    'cards',
    cardsWithConfigApplied.value.map(card => ({
      key: card.uriKey,
      order: card.order,
      enabled: status.value[card.uriKey],
    }))
  )

  form.put(`/dashboards/${dashboardBeingEdited.value.id}`).then(dashboard => {
    Innoclapps.success(t('core::dashboard.updated'))
    store.commit('users/UPDATE_DASHBOARD', dashboard)
    router.push({ name: 'dashboard' })
  })
}

async function prepareComponent() {
  if (!dashboardBeingEdited.value) {
    router.push({ name: 'not-found' })

    return
  }

  await fetchCards()
  await nextTick()

  // Set the status
  cardsWithConfigApplied.value.forEach(card => {
    status.value[card.uriKey] = card.enabled
  })

  pageTitle.value = dashboardBeingEdited.value.name

  form.set('name', dashboardBeingEdited.value.name)
  form.set('is_default', dashboardBeingEdited.value.is_default)

  componentReady.value = true
}

prepareComponent()
</script>

<style>
.dashboard-edit .hide-when-editing {
  display: none !important;
}
</style>
