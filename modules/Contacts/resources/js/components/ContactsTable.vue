<template>
  <ResourceTable
    v-if="initialize"
    :resource-name="resourceName"
    :table-id="tableId"
    :empty-state="{
      to: { name: 'create-contact' },
      title: $t('contacts::contact.empty_state.title'),
      buttonText: $t('contacts::contact.create'),
      description: $t('contacts::contact.empty_state.description'),
      secondButtonText: $t('core::import.from_file', { file_type: 'CSV' }),
      secondButtonIcon: 'DocumentAdd',
      secondButtonTo: {
        name: 'import-resource',
        params: { resourceName },
      },
    }"
    v-bind="$attrs"
  >
    <template #header="{ total }">
      <ITextDark
        class="font-medium"
        :text="$t('contacts::contact.count.all', { count: total })"
      />
    </template>

    <template #actions="{ row }">
      <ITableRowActions>
        <ITableRowAction
          icon="Clock"
          :text="$t('activities::activity.create')"
          @click="
            activityBeingCreatedRow = {
              ...row,
              name: row.display_name,
            }
          "
        />

        <ITableRowAction
          v-if="row.authorizations.update"
          icon="PencilAlt"
          :text="$t('core::app.edit')"
          @click="
            floatResourceInEditMode({
              resourceName,
              resourceId: row.id,
            })
          "
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

  <CreateActivityModal
    :visible="activityBeingCreatedRow !== null"
    :contacts="[activityBeingCreatedRow]"
    :with-extended-submit-buttons="true"
    :go-to-list="false"
    @created="
      ({ isRegularAction }) => (
        isRegularAction ? (activityBeingCreatedRow = null) : '', reloadTable()
      )
    "
    @hidden="activityBeingCreatedRow = null"
  />
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useFloatingResourceModal } from '@/Core/composables/useFloatingResourceModal'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useTable } from '@/Core/composables/useTable'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  tableId: { required: true, type: String },
  initialize: { default: true, type: Boolean },
})

const emit = defineEmits(['deleted'])

const resourceName = Innoclapps.resourceName('contacts')

const { t } = useI18n()
const { reloadTable } = useTable(props.tableId)
const { floatResourceInEditMode } = useFloatingResourceModal()
const { deleteResource } = useResourceable(resourceName)

const activityBeingCreatedRow = ref(null)

async function destroy(id) {
  await deleteResource(id)

  emit('deleted', id)

  reloadTable()

  Innoclapps.success(t('core::resource.deleted'))
}
</script>
