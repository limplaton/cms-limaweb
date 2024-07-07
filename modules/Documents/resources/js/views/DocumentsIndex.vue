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
            icon="Bars3CenterLeft"
            :to="{
              name: 'document-templates-index',
            }"
            :text="$t('documents::document.template.manage')"
          />

          <IDropdownItem
            icon="Trash"
            :to="{
              name: 'trashed-resource-records',
              params: { resourceName: resourceName },
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
          :to="{ name: 'create-document' }"
          :text="$t('documents::document.create')"
        />
      </NavbarItems>
    </template>

    <CardsRenderer
      v-if="showCards"
      class="mb-6"
      :resource-name="resourceName"
    />

    <DocumentsTable
      :table-id="tableId"
      :initialize="initialize"
      @loaded="tableEmpty = $event.isPreEmpty"
      @deleted="refreshIndex"
    />

    <!-- Create router view -->
    <RouterView name="create" @created="refreshIndex" @sent="refreshIndex" />

    <!-- Edit router view -->
    <RouterView
      name="edit"
      :exit-using="() => $router.push({ name: 'document-index' })"
      @changed="refreshIndex"
      @deleted="refreshIndex"
      @cloned="refreshIndex"
    />
  </MainLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { onBeforeRouteUpdate, useRoute } from 'vue-router'

import CardsRenderer from '@/Core/components/Cards/CardsRenderer.vue'
import { emitGlobal } from '@/Core/composables/useGlobalEventListener'
import { useTable } from '@/Core/composables/useTable'

import DocumentsTable from '../components/DocumentsTable.vue'

const resourceName = Innoclapps.resourceName('documents')
const tableId = 'documents'

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
