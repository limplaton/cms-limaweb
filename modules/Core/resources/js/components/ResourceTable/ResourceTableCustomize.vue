<template>
  <IModal
    :id="tableId + 'listSettings'"
    size="sm"
    :visible="tableBeingCustomized"
    :description="$t('core::table.customize_list_view')"
    :title="$t('core::table.list_settings')"
    @hidden="handleModalHidden"
  >
    <div v-if="tableSettings.allowDefaultSortChange" class="mb-4 mt-10">
      <ITextDark
        class="mb-1 font-medium"
        :text="$t('core::table.default_sort')"
      />

      <SortableDraggable
        v-model="sorted"
        class="space-y-2"
        handle=".sort-draggable-handle"
        :item-key="item => item.attribute + '-' + item.direction"
        v-bind="$draggable.common"
      >
        <template #item="{ index }">
          <div class="flex items-center space-x-1.5">
            <div class="grow">
              <IFormSelect
                :id="'column_' + index"
                v-model="sorted[index].attribute"
              >
                <!-- ios by default selects the first field but no events are triggered in this case
                we will make sure to add blank one -->
                <option v-if="!sorted[index].attribute" value=""></option>

                <option
                  v-for="sortableColumn in sortable"
                  v-show="!isSortedColumnDisabled(sortableColumn.attribute)"
                  :key="sortableColumn.attribute"
                  :value="sortableColumn.attribute"
                  v-text="sortableColumn.label"
                />
              </IFormSelect>
            </div>

            <div class="flex-auto">
              <IFormSelect
                :id="'column_type_' + index"
                v-model="sorted[index].direction"
              >
                <option value="asc">
                  Asc (<span v-t="'core::app.ascending'"></span>)
                </option>

                <option value="desc">
                  Desc (<span v-t="'core::app.descending'"></span>)
                </option>
              </IFormSelect>
            </div>

            <IButton
              :variant="index === 0 ? 'secondary' : 'danger'"
              :disabled="index === 0 && isAddSortColumnDisabled"
              :soft="index > 0"
              :icon="index === 0 ? 'PlusSolid' : 'MinusSolid'"
              @click="index === 0 ? addSortedColumn() : removeSorted(index)"
            />

            <Icon
              class="sort-draggable-handle size-5 cursor-move text-neutral-500"
              icon="Selector"
            />
          </div>
        </template>
      </SortableDraggable>
    </div>

    <IFormLabel
      class="mb-1.5 font-medium"
      for="search-table-columns"
      :label="$t('core::table.columns')"
    />

    <SearchInput
      id="search-table-columns"
      v-model="search"
      @update:model-value="setMutableCustomizeableColumns"
    />

    <div class="my-4 max-h-[400px] overflow-auto">
      <SortableDraggable
        v-model="mutableCustomizeableColumns"
        item-key="attribute"
        :move="onColumnMove"
        v-bind="{ ...$draggable.scrollable, filter: 'label' }"
      >
        <template #item="{ element, index }">
          <div
            v-i-tooltip="
              element.primary ? $t('core::table.primary_column') : ''
            "
            :class="[
              'mb-1.5 mr-2 flex items-center rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-500/30',
              element.primary
                ? 'bg-neutral-50 dark:bg-neutral-800'
                : 'hover:bg-neutral-50 dark:hover:bg-neutral-800',
            ]"
          >
            <div class="grow">
              <IFormCheckboxField>
                <IFormCheckbox
                  v-model:checked="mutableCustomizeableColumns[index].visible"
                  :disabled="element.primary === true"
                />

                <IFormCheckboxLabel class="inline-flex items-center space-x-1">
                  <Icon
                    v-if="element.helpText"
                    v-i-tooltip="element.helpText"
                    icon="QuestionMarkCircle"
                    class="size-4 text-neutral-600"
                  />

                  <span>
                    {{ element.label }}
                  </span>
                </IFormCheckboxLabel>
              </IFormCheckboxField>
            </div>

            <Icon
              v-if="!element.primary"
              class="size-5 cursor-move text-neutral-500"
              icon="Selector"
            />
          </div>
        </template>
      </SortableDraggable>
    </div>

    <IFormGroup
      label-for="tableSettingsPerPage"
      :label="$t('core::table.per_page')"
    >
      <IFormSelect id="tableSettingsPerPage" v-model="perPage">
        <option v-for="number in [25, 50, 100]" :key="number" :value="number">
          {{ number }}
        </option>
      </IFormSelect>
    </IFormGroup>

    <IFormGroup
      label-for="tableSettingsMaxHeight"
      :label="$t('core::table.max_height')"
      :description="$t('core::table.max_height_info')"
    >
      <div class="relative mt-1 rounded-md shadow-sm">
        <IFormInput
          id="tableSettingsMaxHeight"
          v-model="maxHeight"
          type="number"
          min="200"
          step="50"
          class="pr-8 sm:pr-10 [&::-webkit-calendar-picker-indicator]:opacity-0"
          list="maxHeight"
        />

        <datalist id="maxHeight">
          <option value="200" />

          <option value="250" />

          <option value="300" />

          <option value="350" />

          <option value="400" />

          <option value="500" />
        </datalist>

        <div
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
        >
          <span class="-mt-1 text-neutral-400">px</span>
        </div>
      </div>
    </IFormGroup>

    <div class="mt-5">
      <IFormCheckboxField>
        <IFormCheckbox v-model:checked="bordered" />

        <IFormCheckboxLabel :text="$t('core::table.bordered')" />
      </IFormCheckboxField>

      <IFormCheckboxField>
        <IFormCheckbox v-model:checked="condensed" />

        <IFormCheckboxLabel :text="$t('core::table.condensed')" />
      </IFormCheckboxField>

      <IFormGroup
        :description="pollingEnabled ? $t('core::table.polling_info') : null"
      >
        <IFormCheckboxField>
          <IFormCheckbox
            v-model:checked="pollingEnabled"
            @change="pollingInterval = $event ? DEFAULT_POLLING_INTERVAL : null"
          />

          <IFormCheckboxLabel :text="$t('core::table.enable_polling')" />
        </IFormCheckboxField>

        <IFormInput
          v-show="pollingEnabled"
          v-model="pollingInterval"
          type="number"
          min="10"
          class="mt-2"
          @blur="
            pollingInterval < MINIMUM_POLLING_INTERVAL
              ? (pollingInterval = MINIMUM_POLLING_INTERVAL)
              : undefined
          "
        />
      </IFormGroup>
    </div>

    <template #modal-footer>
      <div class="flex items-center justify-between text-right">
        <IButton
          :disabled="form.busy"
          :text="$t('core::app.reset')"
          :confirm-text="$t('core::app.confirm')"
          basic
          confirmable
          @confirmed="reset"
        />

        <div class="space-x-2">
          <IButton
            :disabled="form.busy"
            :text="$t('core::app.cancel')"
            basic
            @click="customizeTable(false)"
          />

          <IButton
            variant="primary"
            :disabled="form.busy"
            :text="$t('core::app.save')"
            @click="save()"
          />
        </div>
      </div>
    </template>
  </IModal>
</template>

<script setup>
import { computed, nextTick, ref, toRaw } from 'vue'
import filter from 'lodash/filter'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'
import orderBy from 'lodash/orderBy'

import { useForm } from '@/Core/composables/useForm'

import { useTable } from '../../composables/useTable'

const props = defineProps({
  tableId: { required: true, type: String },
  urlPath: { required: true, type: String },
})

const {
  settings: tableSettings,
  tableBeingCustomized,
  reloadTable,
  customizeTable,
} = useTable(props.tableId)

const sorted = ref([])
const mutableCustomizeableColumns = ref([])
const search = ref(null)
const maxHeight = ref(null)
const condensed = ref(false)
const bordered = ref(false)
const perPage = ref(null)

const MINIMUM_POLLING_INTERVAL = tableSettings.value.minimumPollingInterval
const DEFAULT_POLLING_INTERVAL = 25
const pollingInterval = ref(null)
const pollingEnabled = ref(false)

const { form } = useForm()

const customizeableColumns = computed(() =>
  filter(tableSettings.value.columns, 'customizeable')
)

const sortable = computed(() => filter(customizeableColumns.value, 'sortable'))

const isAddSortColumnDisabled = computed(() => {
  // Return true if all sortable columns are already sorted
  if (sorted.value.length === sortable.value.length) {
    return true
  }

  // Check if any sorted column has not been selected (has an empty 'attribute')
  return sorted.value.some(column => column.attribute === '')
})

function onColumnMove(data) {
  // You can't reorder primary columns or actions column
  // you can't add new columns before the first primary column
  // as the first primary column contains specific data table related to the table
  // You can't add new columns after the last primary column
  const { index, futureIndex } = data.draggedContext
  const isPrimaryColumn = idx => mutableCustomizeableColumns.value[idx].primary

  if (
    isPrimaryColumn(index) ||
    (futureIndex === 0 && isPrimaryColumn(futureIndex))
  ) {
    return false
  }
}

function isSortedColumnDisabled(attribute) {
  return Boolean(find(sorted.value, ['attribute', attribute]))
}

function addSortedColumn() {
  sorted.value.push({ attribute: '', direction: 'asc' })
}

function removeSorted(index) {
  sorted.value.splice(index, 1)
}

function handleModalHidden() {
  if (tableBeingCustomized.value) {
    customizeTable(false)
  }

  search.value = null
  setMutableCustomizeableColumns()
}

function setDefaults() {
  setTableConfig()
  setMutableCustomizeableColumns()
}

function setMutableCustomizeableColumns() {
  mutableCustomizeableColumns.value = filter(
    customizeableColumns.value,
    column => {
      return (
        !search.value ||
        column.label.toLowerCase().includes(search.value.toLowerCase())
      )
    }
  )

  mutableCustomizeableColumns.value.forEach(column => {
    column.visible = !column.hidden
  })
}

function prepareColumnsForStorage(columns) {
  return columns.map((column, index) => ({
    attribute: column.attribute,
    order: index + 1,
    width: column.width,
    wrap: column.wrap,
    hidden: !column.visible,
  }))
}

function reset() {
  request(form.clear()).then(initializeComponent)
}

function save(columns, reload = true) {
  const prepareColumns = () => {
    const defaultColumns = orderBy(customizeableColumns.value, col =>
      findIndex(mutableCustomizeableColumns.value, ['attribute', col.attribute])
    )

    return prepareColumnsForStorage(columns || defaultColumns)
  }

  const formData = form.clear().set({
    order: sorted.value.filter(column => column.attribute !== ''),
    columns: prepareColumns(),
    pollingInterval: pollingInterval.value,
    maxHeight: maxHeight.value,
    condensed: condensed.value,
    bordered: bordered.value,
    perPage: perPage.value,
  })

  return request(formData, reload)
}

async function request(form, reload = true) {
  const data = await form.post(`${props.urlPath}/settings`)

  tableSettings.value = data

  // Clear the search value as it's used in the "setMutableCustomizeableColumns" function
  // In case the user performed a search, will save only the filtered columns.
  search.value = ''

  nextTick(setDefaults)

  // We will re-query the table because the hidden columns are not queried
  // and in this case the data won't be shown
  if (reload) {
    nextTick(() => reloadTable())
  }

  customizeTable(false)
}

function setTableConfig() {
  pollingInterval.value = tableSettings.value.pollingInterval
  pollingEnabled.value = parseInt(tableSettings.value.pollingInterval) >= 10

  maxHeight.value = tableSettings.value.maxHeight
  condensed.value = tableSettings.value.condensed
  bordered.value = tableSettings.value.bordered
  perPage.value = tableSettings.value.perPage
}

function initializeComponent() {
  setDefaults()
  sorted.value = structuredClone(toRaw(tableSettings.value.order))
}

initializeComponent()

defineExpose({ save, onColumnMove })
</script>
