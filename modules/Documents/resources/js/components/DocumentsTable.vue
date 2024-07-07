<template>
  <div class="mb-2 block lg:hidden">
    <DocumentsTableStatusPicker v-model="selectedStatus" />
  </div>

  <ResourceTable
    v-if="initialize"
    :resource-name="resourceName"
    :table-id="tableId"
    :data-request-query-string="dataRequestQueryString"
    :empty-state="{
      to: { name: 'create-document' },
      title: $t('documents::document.empty_state.title'),
      buttonText: $t('documents::document.create'),
      description: $t('documents::document.empty_state.description'),
    }"
    v-bind="$attrs"
  >
    <template #header="{ total }">
      <div class="hidden lg:ml-6 lg:block">
        <DocumentsTableStatusPicker v-model="selectedStatus" />
      </div>

      <ITextDark
        class="font-medium"
        :text="$t('documents::document.count.all', { count: total })"
      />
    </template>

    <template #status="{ row }">
      <IBadge
        :color="statuses[row.status].color"
        :text="statuses[row.status].display_name"
      />
    </template>

    <template #actions="{ row }">
      <ITableRowActions>
        <ITableRowAction
          icon="Eye"
          :href="row.public_url"
          :text="$t('documents::document.view')"
        />

        <ITableRowAction
          v-if="row.authorizations.update && row.status === 'draft'"
          icon="Mail"
          :to="{
            name: 'edit-document',
            params: { id: row.id },
            query: { section: 'send' },
          }"
          :text="$t('documents::document.send.send')"
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
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import { useApp } from '@/Core/composables/useApp'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useTable } from '@/Core/composables/useTable'

import DocumentsTableStatusPicker from './DocumentsTableStatusPicker.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  tableId: { required: true, type: String },
  initialize: { default: true, type: Boolean },
})

const emit = defineEmits(['deleted'])

const resourceName = Innoclapps.resourceName('documents')

const router = useRouter()
const { t } = useI18n()
const { scriptConfig } = useApp()
const { reloadTable } = useTable(props.tableId)
const { deleteResource, cloneResource } = useResourceable(resourceName)

const statuses = scriptConfig('documents.statuses')
const selectedStatus = ref(null)

const dataRequestQueryString = computed(() => ({
  status: selectedStatus.value,
}))

async function clone(id) {
  const document = await cloneResource(id)

  reloadTable()
  router.push({ name: 'edit-document', params: { id: document.id } })
}

async function destroy(id) {
  await deleteResource(id)

  emit('deleted', id)
  reloadTable()

  Innoclapps.success(t('core::resource.deleted'))
}
</script>
