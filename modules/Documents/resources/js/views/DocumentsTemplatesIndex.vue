<template>
  <MainLayout>
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />

      <IButton
        v-show="!tableEmpty"
        variant="primary"
        icon="PlusSolid"
        :to="{ name: 'create-document-template' }"
        :text="$t('documents::document.template.create')"
      />
    </template>

    <ResourceTable
      :resource-name="resourceName"
      :table-id="tableId"
      :empty-state="{
        to: { name: 'create-document-template' },
        title: $t('documents::document.template.empty_state.title'),
        buttonText: $t('documents::document.template.create'),
        description: $t('documents::document.template.empty_state.description'),
      }"
      @loaded="tableEmpty = $event.isPreEmpty"
    >
      <template #actions="{ row }">
        <ITableRowActions>
          <ITableRowAction
            v-if="row.authorizations.update"
            icon="PencilAlt"
            :to="{ name: 'edit-document-template', params: { id: row.id } }"
            :text="$t('core::app.edit')"
          />

          <ITableRowAction
            icon="Duplicate"
            :text="$t('core::app.clone')"
            @click="clone(row.id)"
          />

          <ITableRowAction
            v-if="row.authorizations.delete"
            icon="Trash"
            :text="$t('core::app.delete')"
            @click="$confirm(() => destroy(row.id))"
          />
        </ITableRowActions>
      </template>
    </ResourceTable>
    <!-- Create, Edit -->
    <RouterView name="create" @created="reloadTable" />

    <RouterView name="edit" @updated="reloadTable" />
  </MainLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import { useResourceable } from '@/Core/composables/useResourceable'
import { useTable } from '@/Core/composables/useTable'

const resourceName = Innoclapps.resourceName('document-templates')
const tableId = 'document-templates'

const { t } = useI18n()
const router = useRouter()
const { reloadTable } = useTable(tableId)
const { deleteResource, cloneResource } = useResourceable(resourceName)

const tableEmpty = ref(true)

async function clone(id) {
  const template = await cloneResource(id)

  reloadTable()
  router.push({ name: 'edit-document-template', params: { id: template.id } })
}

async function destroy(id) {
  await deleteResource(id)

  reloadTable()

  Innoclapps.success(t('documents::document.template.deleted'))
}
</script>
