<template>
  <MainLayout>
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />

      <NavbarItems>
        <IDropdownMinimal
          :placement="tableEmpty ? 'bottom-end' : 'bottom'"
          horizontal
        >
          <IDropdownItem
            icon="DocumentAdd"
            :to="{
              name: 'import-resource',
              params: { resourceName },
            }"
            :text="$t('core::import.import')"
          />

          <IDropdownItem
            v-if="$gate.userCan('export companies')"
            icon="DocumentDownload"
            :text="$t('core::app.export.export')"
            @click="$dialog.show('export-modal')"
          />

          <IDropdownItem
            icon="Trash"
            :to="{
              name: 'trashed-resource-records',
              params: { resourceName },
            }"
            :text="$t('core::app.soft_deletes.trashed')"
          />

          <IDropdownItem
            icon="Cog"
            :text="$t('core::table.list_settings')"
            @click="customizeTable"
          />
        </IDropdownMinimal>

        <IButton
          v-show="!tableEmpty"
          variant="primary"
          icon="PlusSolid"
          :to="{ name: 'create-company' }"
          :text="$t('contacts::company.create')"
        />
      </NavbarItems>
    </template>

    <CardsRenderer
      v-if="showCards"
      class="mb-6"
      :resource-name="resourceName"
    />

    <CompaniesTable
      :table-id="tableId"
      :initialize="initialize"
      @loaded="tableEmpty = $event.isPreEmpty"
      @deleted="refreshIndex"
    />

    <ResourceExport
      url-path="/companies/export"
      :resource-name="resourceName"
      :filters-view="tableId"
      :title="$t('contacts::company.export')"
    />

    <!-- Create -->
    <RouterView
      name="create"
      :redirect-to-view="true"
      @created="
        ({ isRegularAction }) => (!isRegularAction ? refreshIndex() : undefined)
      "
      @hidden="$router.back"
    />
  </MainLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { onBeforeRouteUpdate, useRoute } from 'vue-router'

import CardsRenderer from '@/Core/components/Cards/CardsRenderer.vue'
import ResourceExport from '@/Core/components/Export/ResourceExport.vue'
import { emitGlobal } from '@/Core/composables/useGlobalEventListener'
import { useTable } from '@/Core/composables/useTable'

import CompaniesTable from '../components/CompaniesTable.vue'

const resourceName = Innoclapps.resourceName('companies')
const tableId = 'companies'

const route = useRoute()
const { reloadTable, customizeTable } = useTable(tableId)

const initialize = ref(route.meta.initialize)
const tableEmpty = ref(true)

const showCards = computed(() => initialize.value && !tableEmpty.value)

function refreshIndex() {
  emitGlobal('refresh-cards')

  reloadTable()
}

/**
 * Before the cached route is updated
 * For all cases set that intialize index to be true
 * This helps when intially "initialize" was false
 * But now when the user actually sees the index, it should be updated to true
 */
onBeforeRouteUpdate((to, from, next) => {
  initialize.value = true

  next()
})
</script>
