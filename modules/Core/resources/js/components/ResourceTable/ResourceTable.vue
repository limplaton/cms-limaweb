<template>
  <div v-if="showEmptyState" class="m-auto mt-8 max-w-5xl">
    <IEmptyState v-bind="emptyState" />
  </div>

  <IOverlay v-if="!initialDataLoaded && !showEmptyState" :show="true" />

  <div
    :class="
      shouldDisplayTable ? 'opacity-100' : 'pointer-events-none opacity-0'
    "
  >
    <div
      class="mb-3 mt-6 space-y-2 sm:mt-0 md:flex md:items-center md:space-x-4 md:space-y-0"
    >
      <SearchInput
        :id="`${resourceName}-table-search`"
        v-model="search"
        @update:model-value="request(true)"
      />

      <div v-if="componentReady && hasRules" class="flex space-x-1 sm:!ml-2">
        <FiltersDropdown
          placement="bottom-start"
          :view="filtersView"
          :identifier="filtersIdentifier"
          @apply="applyFilters"
        />
      </div>

      <slot
        name="header"
        :total="collection.state.meta.total"
        :meta="collection.state.meta"
        :is-pre-empty="isPreEmpty"
      />

      <div
        v-if="componentReady && tableSettings.customizeable"
        class="flex-1 text-right"
      >
        <IButton
          v-if="withCustomizeButton"
          variant="secondary"
          icon="Cog"
          @click="customizeTable"
        />

        <ResourceTableCustomize
          ref="customizationRef"
          :table-id="tableId"
          :url-path="computedUrlPath"
        />
      </div>
    </div>

    <RulesDisplay
      v-if="componentReady && hasRules && hasRulesApplied"
      :identifier="filtersIdentifier"
      :view="filtersView"
    />

    <FilterBuilder
      v-if="componentReady"
      :view="filtersView"
      :identifier="filtersIdentifier"
      :active-filter-id="filterId"
      @apply="applyFilters"
    />

    <IOverlay :show="isLoading">
      <ResourceTableActions
        v-if="isSelectable && hasSelectedRows"
        :actions="tableSettings.actions"
        :ids="selectedRowsIds"
        :table-id="tableId"
        :request-params="actionRequestParams"
        :resource-name="resourceName"
        :total-results="collection.total"
        @unselect="unselectAllRows"
        @run="$emit('actionExecuted', $event)"
      />

      <ITableOuter class="mt-3">
        <ITable
          :id="'table-' + tableId"
          :class="[
            '[&_tbody>tr>td:first-child]:px-4 [&_thead>tr>th:first-child]:px-4',
            collection.isEmpty()
              ? '[&_.resizer]:pointer-events-none [&_.resizer]:!opacity-0'
              : '',
          ]"
          :condensed="tableSettings.condensed"
          :max-height="maxHeightPx"
          :grid="tableSettings.bordered"
          fixed-layout
        >
          <ResourceTableHead
            :is-loaded="initialDataLoaded"
            :columns="columnsForResizer"
            :disabled="!tableSettings.customizeable"
            @resized="handleColumnsResized"
          >
            <SortableDraggable
              v-model="visibleColumns"
              tag="tr"
              :class="
                isSticky
                  ? '[&>th]:sticky [&>th]:top-0 [&>th]:bg-opacity-75 [&>th]:backdrop-blur-sm [&>th]:backdrop-filter'
                  : ''
              "
              :move="onColumnMoveHandler"
              :item-key="item => 'th' + item.attribute"
              v-bind="{
                ...$draggable.scrollable,
                delay: 100,
                ghostClass: 'sortable-column-ghost',
                filter: '.resizer, .draggable-exclude',
                preventOnFilter: false,
              }"
              @update:model-value="saveColumns"
            >
              <template #item="{ element, index }">
                <ResourceTableHeader
                  :attribute="element.attribute"
                  :class="[
                    element.thClass,
                    !tableSettings.customizeable || !element.customizeable
                      ? 'draggable-exclude'
                      : '',
                  ]"
                  :wrap="element.wrap"
                  :customizeable="
                    tableSettings.customizeable && element.customizeable
                  "
                  :table-customizeable="tableSettings.customizeable"
                  :label="element.label"
                  :align="element.align"
                  :condensed="tableSettings.condensed"
                  :is-selectable="isSelectable && index === 0"
                  :is-sortable="element.sortable"
                  :is-primary="element.primary"
                  :is-ordered="collection.isOrderedBy(element.attribute)"
                  :is-sorted-ascending="
                    collection.isSorted('asc', element.attribute)
                  "
                  :width="element.width || 'auto'"
                  :total-selected="selectedRowsCount"
                  :all-rows-selected="allRowsSelected"
                  @customize="customizeTable"
                  @sort-asc="attr => collection.sortAsc(attr)"
                  @sort-desc="attr => collection.sortDesc(attr)"
                  @checkbox-changed="handleCheckboxChanged"
                  @updated="handleColumnUpdated(index, $event)"
                />
              </template>
            </SortableDraggable>
          </ResourceTableHead>

          <ITableBody>
            <ITableRow
              v-for="row in collection.items"
              :key="row.id"
              :aria-selected="row.tSelected"
              :class="[
                'group/tr',
                row._row_class,
                row._row_border
                  ? '[&>td:first-child]:before:absolute [&>td:first-child]:before:left-0 [&>td:first-child]:before:top-0 [&>td:first-child]:before:h-full [&>td:first-child]:before:w-auto [&>td:first-child]:before:border-l-2 [&>td:first-child]:before:border-transparent'
                  : '',
                row._row_border
                  ? {
                      '[&>td:first-child]:before:!border-warning-500':
                        row._row_border === 'warning',
                      '[&>td:first-child]:before:!border-danger-500':
                        row._row_border === 'danger',
                      '[&>td:first-child]:before:!border-success-500':
                        row._row_border === 'success',
                      '[&>td:first-child]:before:!border-info-500':
                        row._row_border === 'info',
                      '[&>td:first-child]:before:!border-primary-500':
                        row._row_border === 'primary',
                    }
                  : '',
              ]"
              @click="selectOnRowClick($event, row)"
            >
              <ResourceTableCell
                v-for="(column, cidx) in visibleColumns"
                :key="'td-' + column.attribute"
                :attribute="column.attribute"
                :wrap="column.wrap"
                :condensed="tableSettings.condensed"
                :has-required-field="
                  column.field ? column.field.isRequired : false
                "
                :align="column.align"
                :newlineable="column.newlineable"
                :customizeable="
                  tableSettings.customizeable && column.customizeable
                "
                :link="column.link"
                :route="column.route"
                :class="column.tdClass"
                :is-primary="column.primary"
                :is-selected="row.tSelected || false"
                :is-selectable="isSelectable && cidx === 0"
                :row="row"
                @selected="selectRow"
              >
                <slot
                  v-bind="{ column, row, resourceName, resourceId: row.id }"
                  :name="column.attribute"
                >
                  <span
                    v-if="!column.component && !column.field?.indexComponent"
                  >
                    {{ row[column.attribute] }}
                  </span>

                  <component
                    :is="column.component || column.field.indexComponent"
                    v-else
                    :field="
                      column.field
                        ? {
                            ...column.field,
                            value: row[column.attribute],
                          }
                        : undefined
                    "
                    v-bind="{ column, row, resourceName, resourceId: row.id }"
                    @reload="request"
                  />
                </slot>
              </ResourceTableCell>
            </ITableRow>

            <ITableRow v-if="collection.isEmpty()" data-slot="empty">
              <ITableCell v-show="initialDataLoaded" :colspan="totalColumns">
                <IText>{{ emptyText }}</IText>
              </ITableCell>
            </ITableRow>
          </ITableBody>
        </ITable>

        <TablePagination
          v-if="collection.hasPagination"
          class="px-4 py-3"
          :is-current-page-check="page => collection.isCurrentPage(page)"
          :has-next-page="collection.hasNextPage"
          :has-previous-page="collection.hasPreviousPage"
          :links="collection.pagination"
          :render-links="collection.shouldRenderLinks"
          :from="collection.from"
          :to="collection.to"
          :total="collection.total"
          :loading="isLoading"
          @go-to-next="collection.nextPage()"
          @go-to-previous="collection.previousPage()"
          @go-to-page="collection.page($event)"
        />
      </ITableOuter>
    </IOverlay>
  </div>
</template>

<script setup>
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  onUnmounted,
  reactive,
  ref,
  watch,
} from 'vue'
import { useI18n } from 'vue-i18n'
import { useTimeoutFn } from '@vueuse/core'
import isEqual from 'lodash/isEqual'
import sortBy from 'lodash/sortBy'

import FilterBuilder from '@/Core/components/Filters/FilterBuilder.vue'
import FiltersDropdown from '@/Core/components/Filters/FiltersDropdown.vue'
import RulesDisplay from '@/Core/components/QueryBuilder/RulesDisplay.vue'
import { useApp } from '@/Core/composables/useApp'
import { useFilterable } from '@/Core/composables/useFilterable'
import { useGlobalEventListener } from '@/Core/composables/useGlobalEventListener'
import { useLoader } from '@/Core/composables/useLoader'
import { useQueryBuilder } from '@/Core/composables/useQueryBuilder'
import { CancelToken } from '@/Core/services/HTTP'

import { useTable } from '../../composables/useTable'
import Collection from '../Table/Collection'
import TablePagination from '../Table/TablePagination.vue'

import ResourceTableActions from './ResourceTableActions.vue'
import ResourceTableCell from './ResourceTableCell.vue'
import ResourceTableCustomize from './ResourceTableCustomize.vue'
import ResourceTableHead from './ResourceTableHead.vue'
import ResourceTableHeader from './ResourceTableHeader.vue'

const props = defineProps({
  tableId: { type: String, required: true },
  resourceName: { type: String, required: true },
  actionRequestParams: { type: Object, default: () => ({}) },
  dataRequestQueryString: { type: Object, default: () => ({}) },
  withCustomizeButton: Boolean,
  emptyState: Object,
  urlPath: String,
  /**
   * The filter id to intially apply to the table
   * If not provided, the default one will be used (if any)
   */
  filterId: Number,
})

const emit = defineEmits(['loaded', 'actionExecuted'])

let clearPollingIntervalId = null
let demoResizeMessageDisplayed = false
let demoHideMessageDisplayed = false
let watchersInitialized = false
let unwatch = []

const { t } = useI18n()
const { setLoading, isLoading } = useLoader()
const { scriptConfig } = useApp()

const {
  settings: tableSettings,
  fetchActions,
  fetchSettings,
  customizeTable,
} = useTable(props.tableId)

const collection = reactive(new Collection())
const search = ref('')
const componentReady = ref(false)
const initialDataLoaded = ref(false)
const customizationRef = ref(null)

let requestCancelToken = null

const emptyText = computed(() => {
  if (collection.isNotEmpty()) return ''
  if (isLoading.value) return '...'
  if (search.value) return t('core::app.no_search_results')

  return t('core::table.empty')
})

const filtersIdentifier = computed(() => tableSettings.value.identifier)
const filtersView = computed(() => props.tableId)
const isPreEmpty = computed(() => collection.state.meta.pre_total === 0)

/**
 * When no maxHeight is provided, just set the maxHeight to big number e.q. 10000px because when the user
 * previous had height, and updated resetted the table, VueJS won't set the height to auto or remove the previous height
 */
const maxHeightPx = computed(() =>
  tableSettings.value.maxHeight !== null
    ? tableSettings.value.maxHeight + 'px'
    : '10000px'
)

const isSticky = computed(() => tableSettings.value.maxHeight !== null)

const showEmptyState = computed(() => {
  // Indicates whether there is performed any request to the server for data
  if (typeof collection.state.meta.pre_total == 'undefined') {
    return false
  }

  return isPreEmpty.value && props.emptyState != undefined
})

const requestQueryStringParams = computed(() => ({
  page: collection.currentPage,
  per_page: collection.perPage,
  order: collection.get('order'),
  ...tableSettings.value.requestQueryString, // Additional server params passed from table php file
  ...props.dataRequestQueryString,
}))

// Ensure they are ordered by order so they are immediately updated when dragging the headings
const columns = computed(() =>
  sortBy(tableSettings.value.columns || [], 'order')
)

const visibleColumns = computed({
  get() {
    return columns.value.filter(
      column => (!column.hidden || column.hidden == false) && column.attribute
    )
  },
  set(value) {
    // We will make sure to update the store before the "save" request
    // so the changes are reflected on the ui immediately without
    // the user to wait the "save" request to finish.
    const currentColumns = tableSettings.value.columns.map(col => {
      const newValueColumn = value.find(vc => vc.attribute === col.attribute)

      return newValueColumn
        ? {
            ...col,
            ...newValueColumn,
            order: value.indexOf(newValueColumn) + 1,
            hidden: newValueColumn.visible === false,
          }
        : col
    })

    tableSettings.value = { ...tableSettings.value, columns: currentColumns }
  },
})

const columnsForResizer = computed(() =>
  visibleColumns.value.map(column => ({
    attribute: column.attribute,
    width: column.width,
    minWidth: column.minWidth,
    customizeable: column.customizeable,
  }))
)

const totalColumns = computed(() => visibleColumns.value.length)

const computedUrlPath = computed(
  () => props.urlPath || '/' + props.resourceName + '/' + 'table'
)

const selectedRows = computed(() =>
  collection.items.filter(row => row.tSelected)
)

const selectedRowsCount = computed(() => selectedRows.value.length)
const selectedRowsIds = computed(() => selectedRows.value.map(row => row.id))
const hasSelectedRows = computed(() => selectedRowsCount.value > 0)

const shouldDisplayTable = computed(
  () => !showEmptyState.value && initialDataLoaded.value
)

const allRowsSelected = computed(
  () => selectedRowsCount.value === collection.items.length
)

const isSelectable = computed(() => {
  return (
    collection.items.length > 0 &&
    (tableSettings.value.hasCustomActions ||
      tableSettings.value.actions.length > 0)
  )
})

const {
  queryBuilderRules: rules,
  rulesAreValid,
  hasRulesApplied,
  resetQueryBuilderRules,
} = useQueryBuilder(filtersIdentifier, filtersView)

const { hasRules, activeFilter, filtersBuilderVisible } = useFilterable(
  filtersIdentifier,
  filtersView
)

/**
 * Make new HTTP table request.
 *
 * @param {boolean} viaUserSearch
 */
async function request(viaUserSearch = false) {
  if (isLoading.value) return

  cancelPreviousRequest()
  setLoading(true)

  // Reset the current page as the search won't be accurate as there will
  // be offset on the query and if any results are found, won't be queried
  if (viaUserSearch && collection.currentPage !== 1) {
    setPage(1)
  }

  const params = { ...requestQueryStringParams.value }
  if (rulesAreValid.value) params.rules = rules.value
  if (search.value) params.q = search.value

  try {
    const response = await Innoclapps.request(computedUrlPath.value, {
      params,
      cancelToken: new CancelToken(token => (requestCancelToken = token)),
    })

    collection.setState(response.data)

    configureWatchers()

    emit('loaded', { isPreEmpty: isPreEmpty.value })
  } catch (error) {
    console.error(error)
  } finally {
    setLoading(false)

    if (!initialDataLoaded.value) {
      useTimeoutFn(() => {
        initialDataLoaded.value = true
      }, 150)
    }
  }
}

function saveColumns(columns, refreshTable = true) {
  if (!scriptConfig('demo')) {
    customizationRef.value.save(columns, refreshTable !== false)
  } else if (!demoHideMessageDisplayed) {
    Innoclapps.info(
      'The state of the columns is not saved in the demo and will reset upon refreshing the page.',
      7000
    )
    demoHideMessageDisplayed = true
  }
}

function handleColumnsResized(columns) {
  if (scriptConfig('demo')) {
    displayDemoResizeMessage()

    return
  }

  updateColumnWidths(columns)
}

function displayDemoResizeMessage() {
  if (!demoResizeMessageDisplayed) {
    Innoclapps.info(
      'Column resizing is not saved in the demo and will reset upon navigating away from this page.',
      7000
    )
    demoResizeMessageDisplayed = true
  }
}

function updateColumnWidths(columns) {
  const updatedColumns = visibleColumns.value.map((column, index) =>
    column.customizeable
      ? {
          ...column,
          width: columns[index].width,
        }
      : column
  )

  visibleColumns.value = updatedColumns
  saveColumns(updatedColumns, false)
}

function handleColumnUpdated(index, settings) {
  const updatedColumns = [...visibleColumns.value]
  updatedColumns[index] = { ...updatedColumns[index], ...settings }

  visibleColumns.value = updatedColumns
  saveColumns(updatedColumns, false)
}

function configurePolling() {
  clearPollingIntervalId = setInterval(
    request,
    tableSettings.value.pollingInterval * 1000
  )
}

function clearPolling() {
  clearPollingIntervalId && clearInterval(clearPollingIntervalId)
}

/**
 * Prepare the component
 */
function prepareComponent() {
  collection.perPage = parseInt(tableSettings.value.perPage)
  collection.set('order', tableSettings.value.order)

  // Set the watchers after the inital data setup
  // This helps to immediately trigger watcher change|new value before setting the data
  nextTick(() => {
    if (hasRules.value) {
      // Configure the watchers for filters, the filters will update the data
      // and the watchers will catch the change in "requestQueryStringParams" to invoke the request
      configureWatchers()
    } else {
      request()
    }
    componentReady.value = true
  })
}

/**
 * Fetch the current table settings.
 */
async function fetchTableSettings() {
  await fetchSettings(props.resourceName, {
    params: props.dataRequestQueryString,
  })
}

async function refetchActions() {
  await fetchActions(props.resourceName, {
    params: props.dataRequestQueryString,
  })
}

function setPage(page) {
  collection.currentPage = page
}

function registerReloaders() {
  useGlobalEventListener('floating-resource-updated', () => request())

  useGlobalEventListener('action-executed', ({ resourceName }) => {
    if (resourceName === props.resourceName) {
      request()
    }
  })

  useGlobalEventListener('reload-resource-table', id => {
    if (id === props.tableId) {
      request()
    }
  })

  useGlobalEventListener('resource-updated', ({ resourceName }) => {
    if (resourceName === props.resourceName) {
      request()
    }
  })
}

function cancelPreviousRequest() {
  if (!requestCancelToken) {
    return
  }

  requestCancelToken()
  requestCancelToken = null
}

// eslint-disable-next-line no-unused-vars
function applyFilters(rules) {
  // Wait till Vuex is updated
  nextTick(request)
}

function selectOnRowClick(e, row) {
  // Auto selecting works only if the table is selectable and there is at least one row selected.
  if (!isSelectable.value || selectedRowsCount.value === 0) {
    return
  }

  const nonSelectableTags = ['INPUT', 'SELECT', 'TEXTAREA', 'A', 'BUTTON']

  if (
    nonSelectableTags.includes(e.target.tagName) ||
    e.target.isContentEditable
  ) {
    return
  }

  selectRow(row)
}

function selectRow(row) {
  row.tSelected = !row.tSelected
}

function handleCheckboxChanged(isIndeterminate) {
  if (isIndeterminate || allRowsSelected.value) {
    unselectAllRows()
  } else {
    selectAllRows()
  }
}

function unselectAllRows() {
  collection.items.forEach(row => (row.tSelected = false))
}

function selectAllRows() {
  collection.items.forEach(row => (row.tSelected = true))
}

/**
 * Configure the component necessary watched
 */
function configureWatchers() {
  if (watchersInitialized === true) {
    return
  }

  watchersInitialized = true

  unwatch.push(
    watch(
      () => tableSettings.value.pollingInterval,
      (newVal, oldVal) => {
        if (!oldVal && newVal) {
          configurePolling()
        } else if (!newVal) {
          clearPolling()
        } else if (newVal && oldVal) {
          clearPolling()
          configurePolling()
        }
      },
      { immediate: true }
    )
  )

  unwatch.push(
    watch(
      requestQueryStringParams,
      (newVal, oldVal) => {
        if (!isEqual(newVal, oldVal)) {
          request()
        }
      },
      { deep: true }
    )
  )

  unwatch.push(
    watch(
      () => tableSettings.value.perPage,
      function (newVal) {
        collection.perPage = parseInt(newVal)
      }
    )
  )

  unwatch.push(
    watch(
      () => tableSettings.value.order,
      (newVal, oldVal) => {
        // Sometimes when fast switching through tables the order is undefined.
        if (newVal && !isEqual(newVal, oldVal)) {
          collection.set('order', newVal)
        }
      },
      {
        deep: true,
      }
    )
  )
}

function handleMountedLifeCycle() {
  registerReloaders()
  fetchTableSettings().then(prepareComponent)
}

function onColumnMoveHandler(e) {
  if (!tableSettings.value.customizeable) {
    return false
  }

  return customizationRef.value.onColumnMove(e)
}

onMounted(handleMountedLifeCycle)
onBeforeUnmount(cancelPreviousRequest)

onUnmounted(() => {
  unwatch.forEach(func => func())
  unwatch = []

  // We will check if there is an active filter already applied in store before clearing QB
  // helps keeping the previous filter applied when navigating from the page
  // where the filters are mounted and then going back
  if (!activeFilter.value) {
    resetQueryBuilderRules()
  }

  filtersBuilderVisible.value = false
  collection.flush()
  clearPolling()
  setLoading(false)
})

defineExpose({
  refetchActions,
  setPage,
})
</script>

<style>
th.sortable-chosen.sortable-drag,
th.sortable-chosen.sortable-column-ghost {
  @apply ring-1 ring-inset ring-neutral-400 dark:ring-neutral-600;
}
</style>
