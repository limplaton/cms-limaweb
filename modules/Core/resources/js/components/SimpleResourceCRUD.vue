<template>
  <div>
    <IModal
      id="createRecordModal"
      size="sm"
      :ok-text="$t('core::app.create')"
      :ok-disabled="form.busy"
      :title="$t('core::resource.create', { resource: singularLabel })"
      static-backdrop
      form
      @submit="create"
      @hidden="handleModalHiddenEvent"
    >
      <FormFields
        :fields="fields"
        :form="form"
        :resource-name="resourceName"
        is-floating
        focus-first
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      />
    </IModal>

    <IModal
      id="updateRecordModal"
      size="sm"
      :ok-text="$t('core::app.save')"
      :ok-disabled="form.busy"
      :title="$t('core::resource.edit', { resource: singularLabel })"
      static-backdrop
      form
      @hidden="handleModalHiddenEvent"
      @submit="update"
    >
      <FormFields
        :fields="fields"
        :form="form"
        :resource-name="resourceName"
        :resource-id="form.id"
        is-floating
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      />
    </IModal>

    <ICardHeader>
      <slot name="header">
        <ICardHeading :text="label" />
      </slot>

      <ICardActions>
        <IButton
          v-show="withCancel"
          :text="$t('core::app.go_back')"
          basic
          @click="requestCancel"
        />

        <IButton
          variant="primary"
          icon="PlusSolid"
          :text="$t('core::resource.create', { resource: singularLabel })"
          @click="prepareCreate"
        />
      </ICardActions>
    </ICardHeader>

    <ICard>
      <AsyncTable
        ref="tableRef"
        wrapper-class="px-6"
        class="[--gutter:theme(spacing.6)]"
        sort-by="name"
        :table-id="resourceName"
        :request-uri="resourceName"
        :fields="columns"
        bleed
      >
        <template #name="{ row }">
          <div class="flex justify-between">
            <ILink
              class="font-medium"
              :text="row.name"
              @click="prepareEdit(row.id)"
            />
          </div>
        </template>

        <template #actions="{ row }">
          <ITableRowActions>
            <ITableRowAction
              icon="PencilAlt"
              :text="$t('core::app.edit')"
              @click="prepareEdit(row.id)"
            />

            <span
              v-i-tooltip="
                row.is_primary
                  ? $t('core::resource.primary_record_delete_info', {
                      resource: singularLabel,
                    })
                  : null
              "
            >
              <ITableRowAction
                icon="Trash"
                :disabled="row.is_primary"
                :text="$t('core::app.delete')"
                @click="$confirm(() => destroy(row.id))"
              />
            </span>
          </ITableRowActions>
        </template>
      </AsyncTable>
    </ICard>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import AsyncTable from '@/Core/components/Table/AsyncTable.vue'
import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'
import { useResourceFields } from '@/Core/composables/useResourceFields'

import { useResourceable } from '../composables/useResourceable'

const props = defineProps({
  resourceName: { required: true, type: String },
  withCancel: { type: Boolean, default: true },
})

const emit = defineEmits(['cancel', 'updated', 'created', 'deleted'])

const { t } = useI18n()
const { scriptConfig, resetStoreState } = useApp()

const { fields, hydrateFields, getCreateFields, getUpdateFields } =
  useResourceFields()

const { form } = useForm()

const { updateResource, createResource, retrieveResource, deleteResource } =
  useResourceable(props.resourceName)

const columns = ref([
  {
    key: 'id',
    label: t('core::app.id'),
    sortable: true,
  },
  {
    key: 'name',
    label: t('core::fields.label'),
    sortable: true,
  },
  {
    key: 'actions',
    label: '',
    width: '8%',
    tdClass: 'text-right',
  },
])

const tableRef = ref(null)

const singularLabel = scriptConfig(
  `resources.${props.resourceName}.singularLabel`
)

const label = scriptConfig(`resources.${props.resourceName}.label`)

function handleModalHiddenEvent() {
  form.reset()
  fields.value = []
}

/**
 * Request cancel edit
 */
function requestCancel() {
  emit('cancel')
}

/**
 * Prepare resource record create
 */
async function prepareCreate() {
  let createFields = await getCreateFields(props.resourceName)
  columns.value[1].key = createFields[0].attribute

  fields.value = createFields

  Innoclapps.dialog().show('createRecordModal')
}

/**
 * Prepare the resource record edit
 */
async function prepareEdit(id) {
  let updateFields = await getUpdateFields(props.resourceName, id)
  let resource = await retrieveResource(id)

  columns.value[1].key = updateFields[0].attribute
  form.fill('id', id)

  fields.value = updateFields
  hydrateFields(resource)

  Innoclapps.dialog().show('updateRecordModal')
}

/**
 * Store resource record in storage
 */
async function create() {
  await createResource(form)

  actionExecuted('created')
  Innoclapps.dialog().hide('createRecordModal')
}

/**
 * Update resource record in storage
 */
async function update() {
  await updateResource(form, form.id)

  actionExecuted('updated')
  Innoclapps.dialog().hide('updateRecordModal')
}

/**
 * Remove resource record from storage
 */
async function destroy(id) {
  await deleteResource(id)

  actionExecuted('deleted')
}

/**
 * Handle action executed
 */
function actionExecuted(action) {
  Innoclapps.success(t('core::resource.' + action))
  tableRef.value.reload()
  resetStoreState()
  emit(action)
}
</script>

<style scoped>
::v-deep(table thead th:first-child) {
  width: 7%;
}

::v-deep(table thead th:first-child a) {
  justify-content: center;
}

::v-deep(table tbody td:first-child) {
  width: 7%;
  text-align: center;
}
</style>
