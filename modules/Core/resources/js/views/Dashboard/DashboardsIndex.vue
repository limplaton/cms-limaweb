<template>
  <MainLayout>
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />

      <div class="flex items-center space-x-3 lg:space-x-5">
        <IDropdownMinimal placement="bottom-end" horizontal>
          <IDropdownItem
            icon="PencilAlt"
            :text="$t('core::dashboard.edit_current')"
            @click="redirectToEdit(dashboard)"
          />

          <IDropdownItem
            icon="Plus"
            :text="$t('core::dashboard.new_dashboard')"
            @click="$dialog.show('newDashboard')"
          />

          <IDropdownItem
            icon="Trash"
            :text="$t('core::dashboard.delete_current')"
            @click="$confirm(() => destroy(dashboard))"
          />
        </IDropdownMinimal>

        <IDropdown placement="bottom-end">
          <IDropdownButton
            :icon="dashboard.is_default ? 'Star' : undefined"
            :class="[
              'max-w-72 sm:max-w-[13rem]',
              !componentReady ? 'pointer-events-none blur' : '',
            ]"
            :active="dashboard.is_default || userDashboards.length === 0"
            basic
          >
            <span
              class="truncate"
              v-text="componentReady ? dashboard.name : 'Dashboard'"
            />
          </IDropdownButton>

          <IDropdownMenu>
            <IDropdownItem
              v-for="userDashboard in userDashboards"
              :key="userDashboard.id"
              :text="userDashboard.name"
              :active="userDashboard.id === dashboard.id"
              @click="setActiveDashboard(userDashboard)"
            />
          </IDropdownMenu>
        </IDropdown>
      </div>
    </template>

    <div
      v-if="!componentReady"
      class="before:box-inherit after:box-inherit columns-1 gap-x-6 lg:columns-2"
    >
      <CardPlaceholder v-once class="mb-5" pulse />

      <CardPlaceholder v-once class="mb-5" size="lg" pulse />

      <CardPlaceholder v-once class="mb-5" pulse />

      <CardPlaceholder v-once class="mb-5" pulse />

      <CardPlaceholder v-once class="mb-5" size="lg" pulse />

      <CardPlaceholder v-once class="mb-5" size="lg" pulse />

      <CardPlaceholder v-once class="mb-5" pulse />

      <CardPlaceholder v-once class="mb-5" pulse />
    </div>

    <SortableDraggable
      v-model="mutableCards"
      handle=".card-drag-handle"
      class="before:box-inherit after:box-inherit columns-1 gap-x-6 lg:columns-2"
      item-key="uriKey"
      v-bind="$draggable.common"
      @change="saveCardsOrder"
    >
      <template #item="{ element }">
        <div class="mb-8 break-inside-avoid sm:mb-5">
          <div class="relative">
            <div
              class="card-drag-handle absolute left-3 top-5 ml-px mt-0.5 inline-flex items-center justify-center text-neutral-500 dark:text-neutral-300 sm:left-1"
            >
              <Icon icon="Selector" class="size-4 cursor-move" />
            </div>

            <component :is="element.component" :card="element" />
          </div>
        </div>
      </template>
    </SortableDraggable>

    <IModal
      id="newDashboard"
      size="sm"
      :ok-text="$t('core::app.create')"
      :ok-disabled="form.busy"
      :title="$t('core::dashboard.create')"
      form
      @submit="create"
      @shown="() => $refs.inputNameRef.focus()"
    >
      <IFormGroup
        label-for="dashboard-name"
        :label="$t('core::dashboard.name')"
        required
      >
        <IFormInput
          id="dashboard-name"
          ref="inputNameRef"
          v-model="form.name"
        />

        <IFormError :error="form.getError('name')" />
      </IFormGroup>
    </IModal>
  </MainLayout>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import filter from 'lodash/filter'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'
import orderBy from 'lodash/orderBy'
import sortBy from 'lodash/sortBy'

import CardPlaceholder from '@/Core/components/Cards/CardPlaceholder.vue'
import { useApp } from '@/Core/composables/useApp'
import { useCards } from '@/Core/composables/useCards'
import { useForm } from '@/Core/composables/useForm'

const { t } = useI18n()
const router = useRouter()
const store = useStore()

const { currentUser } = useApp()
const { cards, fetchCards, applyUserConfig } = useCards()

const { form } = useForm({
  name: '',
})

const mutableCards = ref([])
const dashboard = ref({})

const componentReady = computed(() => cards.value.length > 0)

const enabledAndSortedCards = computed(() =>
  sortBy(filter(applyUserConfig(cards.value, dashboard.value), 'enabled'), [
    'order',
    'asc',
  ])
)

const userDashboards = computed(
  () =>
    orderBy(
      currentUser.value.dashboards,
      ['is_default', 'name'],
      ['desc', 'asc']
    ) || []
)

const defaultDashboard = computed(
  () =>
    find(userDashboards.value, ['is_default', true]) || userDashboards.value[0]
)

function redirectToEdit(dashboard) {
  router.push({
    name: 'edit-dashboard',
    params: {
      id: dashboard.id,
    },
  })
}

function create() {
  form.post('/dashboards').then(dashboard => {
    Innoclapps.success(t('core::dashboard.created'))
    store.commit('users/ADD_DASHBOARD', dashboard)
    redirectToEdit(dashboard)
  })
}

function setActiveDashboard(activeDashboard) {
  dashboard.value = activeDashboard
  nextTick(() => (mutableCards.value = enabledAndSortedCards.value))
}

function saveCardsOrder() {
  let cardsWithConfig = applyUserConfig(cards.value, dashboard.value)

  let payload = cardsWithConfig.map(originalCard => {
    let index = findIndex(mutableCards.value, ['uriKey', originalCard.uriKey])

    return {
      key: originalCard.uriKey,
      enabled: originalCard.enabled,
      order: index === -1 ? originalCard.order : index + 1,
    }
  })

  Innoclapps.request()
    .put(`/dashboards/${dashboard.value.id}`, {
      cards: payload,
    })
    .then(({ data }) => {
      setActiveDashboard(data)
      store.commit('users/UPDATE_DASHBOARD', data)
    })
}

async function destroy(dashboard) {
  await Innoclapps.request().delete(`/dashboards/${dashboard.id}`)

  Innoclapps.success(t('core::dashboard.deleted'))
  store.commit('users/REMOVE_DASHBOARD', dashboard.id)
  setActiveDashboard(defaultDashboard.value)
}

onMounted(() => {
  fetchCards().then(() => {
    nextTick(() => {
      setActiveDashboard(defaultDashboard.value)
    })
  })
})
</script>
