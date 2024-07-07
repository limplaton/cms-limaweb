<template>
  <ICard>
    <ICardHeader>
      <slot name="header">
        <ICardHeading>
          {{ header || $t('core::import.import_records') }}
        </ICardHeading>
      </slot>

      <ICardActions>
        <IButton
          v-if="!importingViaBatch"
          variant="secondary"
          icon="DocumentDownload"
          :text="$t('core::import.download_sample')"
          @click="downloadSample(`/${resourceName}/import/sample`)"
        />
      </ICardActions>
    </ICardHeader>

    <ResourceImportSteps v-if="!importingViaBatch" :steps="steps" />

    <ICardBody>
      <IAlert v-if="importingViaBatch">
        <IAlertBody>
          <div class="flex items-center space-x-2">
            <h4
              v-t="'core::import.import_in_progress'"
              class="text-base font-medium text-info-700 sm:text-sm"
            />

            <ISpinner class="size-4"></ISpinner>
          </div>
          {{ $t('core::import.records_being_imported_in_batches') }}
        </IAlertBody>
      </IAlert>

      <IAlert
        v-if="rowsExceededMessage"
        variant="danger"
        class="mb-5"
        dismissible
      >
        <IAlertBody>
          {{ rowsExceededMessage }}
        </IAlertBody>
      </IAlert>

      <MediaUpload
        v-if="!importingViaBatch"
        class="ml-12 lg:ml-5"
        extensions="csv"
        :action-url="
          appendQueryString(
            `${$scriptConfig('apiURL')}/${resourceName}/import/upload`
          )
        "
        :multiple="false"
        :show-output="false"
        :upload-text="$t('core::import.start')"
        @file-uploaded="handleFileUploaded"
      />

      <div v-if="importBeingMapped" class="mt-5">
        <ITextDisplay
          class="mb-3"
          :text="$t('core::import.spreadsheet_columns')"
        />

        <div class="flex">
          <div class="w-1/2">
            <div
              v-for="(column, index) in importBeingMapped.mappings"
              :key="'mapping-' + index"
              :class="[
                'mb-2 mr-3 flex h-16 flex-col justify-center rounded-lg px-4 ring-1 ring-inset ring-neutral-300 dark:ring-neutral-500/30',
                'bg-white dark:bg-neutral-500/10',
                !column.attribute ? 'opacity-50' : '',
              ]"
            >
              <IFormLabel :required="isColumnRequired(column)">
                {{ column.original }}
                <ITextSmall
                  v-if="column.skip && !isColumnRequired(column)"
                  as="span"
                >
                  ({{ $t('core::import.column_will_not_import') }})
                </ITextSmall>
              </IFormLabel>

              <IText class="truncate" :text="column.preview" />
            </div>
          </div>

          <div class="w-1/2">
            <div
              v-for="(column, index) in importBeingMapped.mappings"
              :key="'field-' + index"
              class="mb-2 flex h-16 items-center"
            >
              <Icon
                icon="ChevronRightSolid"
                class="mr-3 size-5 text-neutral-800 dark:text-neutral-200"
              />

              <IFormSelect
                v-model="importBeingMapped.mappings[index].attribute"
                class="h-16 hover:bg-neutral-100 dark:hover:bg-neutral-800"
                @input="importBeingMapped.mappings[index].skip = !$event"
              >
                <option v-if="!isColumnRequired(column)" value="">N/A</option>

                <option
                  v-for="field in importBeingMapped.fields"
                  :key="'field-' + index + '-' + field.attribute"
                  :disabled="isFieldMapped(field.attribute)"
                  :value="field.attribute"
                  v-text="field.label"
                />
              </IFormSelect>
            </div>
          </div>
        </div>
      </div>
    </ICardBody>

    <ICardFooter
      v-if="importBeingMapped"
      class="flex items-center justify-end space-x-2"
    >
      <IButton
        variant="secondary"
        :disabled="importIsInProgress"
        :text="$t('core::app.cancel')"
        @click="destroy(importBeingMapped.id)"
      />

      <IButton
        variant="primary"
        :loading="importIsInProgress"
        :disabled="importIsInProgress"
        :text="
          importIsInProgress
            ? $t('core::app.please_wait')
            : $t('core::import.import')
        "
        @click="performImport"
      />
    </ICardFooter>
  </ICard>

  <ICardHeader class="mt-8">
    <ICardHeading :text="$t('core::import.history')" />
  </ICardHeader>

  <ICard :overlay="loadingImportHistory">
    <div v-if="hasImportHistory" class="px-6">
      <ITable class="[--gutter:theme(spacing.6)]" bleed>
        <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
          <ITableRow>
            <ITableHeader>
              {{ $t('core::import.date') }}
            </ITableHeader>

            <ITableHeader>
              {{ $t('core::import.file_name') }}
            </ITableHeader>

            <ITableHeader>
              {{ $t('core::import.user') }}
            </ITableHeader>

            <ITableHeader class="text-center">
              {{ $t('core::import.total_imported') }}
            </ITableHeader>

            <ITableHeader class="text-center">
              {{ $t('core::import.total_duplicates') }}
            </ITableHeader>

            <ITableHeader class="text-center">
              {{ $t('core::import.total_skipped') }}
            </ITableHeader>

            <ITableHeader class="text-center">
              {{ $t('core::import.progress') }}
            </ITableHeader>

            <ITableHeader class="text-center">
              {{ $t('core::import.status') }}
            </ITableHeader>

            <ITableHeader />
          </ITableRow>
        </ITableHead>

        <ITableBody>
          <template v-for="history in computedImports" :key="history.id">
            <ITableRow>
              <ITableCell class="font-medium">
                {{ localizedDateTime(history.created_at) }}
              </ITableCell>

              <ITableCell>
                {{ history.file_name }}
              </ITableCell>

              <ITableCell>
                {{ history.user.name }}
              </ITableCell>

              <ITableCell>
                <div class="flex items-center justify-center space-x-1">
                  <span>
                    {{ history.imported }}
                  </span>

                  <ILink
                    v-if="
                      history.revertable &&
                      history.authorizations.revert &&
                      !revertInProgress[history.id]
                    "
                    class="text-sm font-medium sm:text-xs"
                    variant="danger"
                    :text="$t('core::import.revert')"
                    @click="revert(history)"
                  />

                  <ISpinner
                    v-if="revertInProgress[history.id]"
                    class="size-4"
                  />
                </div>
              </ITableCell>

              <ITableCell class="text-center">
                {{ history.duplicates }}
              </ITableCell>

              <ITableCell class="text-center">
                {{ history.skipped }}

                <span
                  v-if="
                    history.skip_file_filename &&
                    history.skipped > 0 &&
                    (history.authorizations.downloadSkipFile ||
                      history.authorizations.uploadFixedSkipFile)
                  "
                >
                  <ILink
                    class="text-sm font-medium sm:text-xs"
                    :text="$t('core::import.why_skipped')"
                    @click="
                      showSkipInfoFor === history.id
                        ? (showSkipInfoFor = null)
                        : (showSkipInfoFor = history.id)
                    "
                  />
                </span>
              </ITableCell>

              <ITableCell class="text-center align-middle">
                <div class="relative h-4 rounded-full bg-neutral-200">
                  <div
                    class="h-4 rounded-full bg-success-500"
                    :style="{ width: history.progress + '%' }"
                  />

                  <span
                    class="absolute inset-0 flex items-center justify-center text-xs font-medium text-neutral-900"
                  >
                    {{ history.progress }}%
                  </span>
                </div>
              </ITableCell>

              <ITableCell class="text-center">
                <span v-i-tooltip="history.status" class="inline-block">
                  <Icon
                    v-if="history.status === 'mapping'"
                    icon="Bars3CenterLeft"
                    class="size-5 animate-pulse text-neutral-500 dark:text-neutral-400"
                  />

                  <Icon
                    v-else-if="history.status === 'finished'"
                    icon="CheckCircle"
                    class="size-5 text-success-500 dark:text-success-400"
                  />

                  <Icon
                    v-else-if="history.status === 'in-progress'"
                    icon="DotsHorizontal"
                    class="size-5 animate-bounce text-neutral-500 dark:text-neutral-400"
                  />
                </span>
              </ITableCell>

              <ITableCell>
                <div class="flex items-center justify-end space-x-2">
                  <ILink
                    v-if="
                      history.status === 'mapping' &&
                      (!importBeingMapped ||
                        (importBeingMapped &&
                          importBeingMapped.id != history.id))
                    "
                    :text="$t('core::app.continue')"
                    @click="continueMapping(history.id)"
                  />

                  <IButton
                    v-if="
                      !importingViaBatch &&
                      !revertInProgress[history.id] &&
                      history.authorizations.delete
                    "
                    icon="Trash"
                    basic
                    small
                    @click="destroy(history.id, true)"
                  />
                </div>
              </ITableCell>
            </ITableRow>

            <ITableRow
              v-if="
                history.skip_file_filename &&
                showSkipInfoFor === history.id &&
                (history.authorizations.downloadSkipFile ||
                  history.authorizations.uploadFixedSkipFile)
              "
            >
              <ITableCell colspan="4">
                <ITextDark class="font-semibold">
                  {{ $t('core::import.skip_file') }}
                </ITextDark>

                <IText>
                  {{
                    $t('core::import.total_rows_skipped', {
                      count: history.skipped,
                    })
                  }}
                </IText>

                <IText
                  class="mt-3 max-w-4xl whitespace-normal font-normal"
                  :text="$t('core::import.skip_file_generation_info')"
                />

                <IText
                  class="my-2 max-w-3xl whitespace-normal font-normal"
                  :text="$t('core::import.skip_file_fix_and_continue')"
                />

                <div class="mb-4 mt-8 flex items-center">
                  <ILink
                    v-if="history.authorizations.downloadSkipFile"
                    class="mr-4"
                    :text="$t('core::import.download_skip_file')"
                    @click="downloadSkipFile(history.id)"
                  />

                  <media-upload
                    v-if="history.authorizations.uploadFixedSkipFile"
                    extensions="csv"
                    name="skip_file"
                    input-id="skip_file"
                    :action-url="
                      appendQueryString(
                        `${$scriptConfig('apiURL').replace(
                          /\/+$/,
                          ''
                        )}${generateImportUri(history.id, '/skip-file')}`
                      )
                    "
                    :multiple="false"
                    :show-output="false"
                    :select-file-text="
                      $t('core::import.upload_fixed_skip_file')
                    "
                    :upload-text="$t('core::import.start')"
                    @file-uploaded="handleSkipFileUploaded($event)"
                  />
                </div>
              </ITableCell>

              <ITableCell colspan="4" />
            </ITableRow>
          </template>
        </ITableBody>
      </ITable>
    </div>

    <ICardBody v-else v-show="!loadingImportHistory" class="py-12 text-center">
      <Icon icon="DocumentText" class="mx-auto size-12 text-neutral-400" />

      <IText class="mt-2" :text="$t('core::import.no_history')" />
    </ICardBody>
  </ICard>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import FileDownload from 'js-file-download'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'
import orderBy from 'lodash/orderBy'

import MediaUpload from '@/Core/components/Media/MediaUpload.vue'
import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'

import ResourceImportSteps from './ResourceImportSteps.vue'

const props = defineProps({
  header: String,
  resourceName: { required: true, type: String },
  requestQueryString: { type: Object, default: () => ({}) },
})

const { t } = useI18n()
const { resetStoreState } = useApp()
const { localizedDateTime } = useDates()
const route = useRoute()
const router = useRouter()

const imports = ref([])
const usingSkipFile = ref(false)
const showSkipInfoFor = ref(null)
const importBeingMapped = ref(null)
const rowsExceededMessage = ref(null)
const loadingImportHistory = ref(false)
const importIsInProgress = ref(false)
const revertInProgress = ref({})
const importingViaBatch = ref(false)

const steps = ref([
  {
    id: '01',
    name: t('core::import.steps.step_1.name'),
    description: t('core::import.steps.step_1.description'),
    status: 'current',
  },
  {
    id: '02',
    name: t('core::import.steps.step_2.name'),
    description: t('core::import.steps.step_2.description'),
    status: 'upcoming',
  },
  {
    id: '03',
    name: t('core::import.steps.step_3.name'),
    description: t('core::import.steps.step_3.description'),
    status: 'upcoming',
  },
  {
    id: '04',
    name: t('core::import.steps.step_4.name'),
    description: t('core::import.steps.step_4.description'),
    status: 'upcoming',
  },
])

/**
 * Get the computed imports ordered by date
 */
const computedImports = computed(() =>
  orderBy(imports.value, 'created_at', 'desc')
)

/**
 * Indicates whether the resource has import history
 */
const hasImportHistory = computed(() => computedImports.value.length > 0)

/**
 * Change the current import step to
 */
function changeCurrentStep(id, status) {
  // When changing to "complete" or "current" we will
  // update all other steps below this step to complete
  if (status === 'complete' || status === 'current') {
    let stepsBelowStep = steps.value.filter(
      step => parseInt(step.id) < parseInt(id)
    )

    stepsBelowStep.forEach(step => (step.status = 'complete'))
  }

  if (status === 'current') {
    // When changing to current, all steps above this step will be upcoming
    let stepsAboveStep = steps.value.filter(
      step => parseInt(step.id) > parseInt(id)
    )

    stepsAboveStep.forEach(step => (step.status = 'upcoming'))
  }

  steps.value[findIndex(steps.value, ['id', id])].status = status
}

/**
 * Create URL for the import request.
 */
function generateImportUri(id, extra = '') {
  return `/${props.resourceName}/import/${id}${extra}`
}

/**
 * Append query string to the given url
 */
function appendQueryString(url, queryString = {}) {
  if (
    Object.keys(props.requestQueryString).length > 0 ||
    Object.keys(queryString).length > 0
  ) {
    let str = []
    let allQueryString = { ...props.requestQueryString, ...queryString }

    for (var q in allQueryString)
      str.push(
        encodeURIComponent(q) + '=' + encodeURIComponent(allQueryString[q])
      )

    url += '?' + str.join('&')
  }

  return url
}

/**
 * Download skip file for the give import id
 * */
function downloadSkipFile(id) {
  Innoclapps.request(generateImportUri(id, '/skip-file'), {
    responseType: 'blob',
  }).then(response => {
    FileDownload(
      response.data,
      response.headers['content-disposition'].split('filename=')[1]
    )
  })
}

/**
 * Download sample import file
 */
function downloadSample(route) {
  Innoclapps.request(route).then(({ data }) => {
    FileDownload(data, 'sample.csv')

    if (
      steps.value[0].status === 'current' ||
      steps.value[3].status === 'complete'
    ) {
      changeCurrentStep('02', 'current')
    }
  })
}

/**
 * Check whether the field is mapped in a column
 */
function isFieldMapped(attribute) {
  return Boolean(
    find(importBeingMapped.value.mappings, ['attribute', attribute])
  )
}

/**
 * Revert the given import.
 */
async function revert(history) {
  await Innoclapps.confirm({
    message: t('core::import.revert_info'),
    confirmText: t('core::import.revert'),
  })

  const id = history.id
  const recordsPerRequest = 500
  const totalRecords = history.imported
  const totalRequests = Math.ceil(totalRecords / recordsPerRequest)

  revertInProgress.value[id] = true

  const performRevert = async () => {
    try {
      for (let i = 1; i <= totalRequests; i++) {
        try {
          const { data } = await Innoclapps.request().delete(
            generateImportUri(id, '/revert?limit=' + recordsPerRequest)
          )

          imports.value[findIndex(imports.value, ['id', parseInt(id)])] = data
        } catch {
          break
        }
      }
    } finally {
      revertInProgress.value[id] = false
    }
  }

  performRevert()
}

/**
 * Delete the given history
 */
async function destroy(id, force) {
  if (usingSkipFile.value && force !== true) {
    importBeingMapped.value = null

    return
  }

  await Innoclapps.confirm()
  await Innoclapps.request().delete(generateImportUri(id))

  handleAfterDelete(id)
}

function handleAfterDelete(id) {
  imports.value.splice(findIndex(imports.value, ['id', id]), 1)

  if (importBeingMapped.value && id == importBeingMapped.value.id) {
    importBeingMapped.value = null
    usingSkipFile.value = false
    changeCurrentStep('01', 'current')
  }
}

/**
 * Continue mapping the given import
 */
function continueMapping(id) {
  setImportForMapping(find(imports.value, ['id', parseInt(id)]))
  changeCurrentStep('03', 'current')
}

/**
 * Set the import instance for mapping
 */
function setImportForMapping(instance) {
  importBeingMapped.value = instance
}

/**
 * Retrieve the current resource imports
 */
async function retrieveImports() {
  loadingImportHistory.value = true

  const { data } = await Innoclapps.request(`${props.resourceName}/import`)

  imports.value = data
  loadingImportHistory.value = false
}

/**
 * Check whether the given import column is required
 */
function isColumnRequired(column) {
  let columnField = find(importBeingMapped.value.fields, [
    'attribute',
    column.detected_attribute,
  ])

  if (!column.detected_attribute || !columnField) {
    return false
  }

  return columnField.isRequired
}

/**
 * Handle file uploaded
 */
function handleFileUploaded(importBeingMapped) {
  setImportForMapping(importBeingMapped)
  imports.value.push(importBeingMapped)
  changeCurrentStep('03', 'current')
  rowsExceededMessage.value = null
}

/**
 * Handle skip file uploaded
 */
function handleSkipFileUploaded(importBeingMapped) {
  setImportForMapping(importBeingMapped)
  let index = findIndex(imports.value, ['id', parseInt(importBeingMapped.id)])
  imports.value[index] = importBeingMapped
  changeCurrentStep('03', 'current')
  usingSkipFile.value = true
}

function continueImport(id) {
  importingViaBatch.value = true

  Innoclapps.request()
    .post(appendQueryString(generateImportUri(id)))
    .then(({ data }) => {
      imports.value[findIndex(imports.value, ['id', parseInt(data.id)])] = data

      if (data.next_batch) {
        // Allow the user to see the progress in the UI properly.
        setTimeout(() => (window.location.href = `?import_id=${data.id}`), 2000)
      } else {
        router.replace({ query: {} })
        importingViaBatch.value = false
        Innoclapps.success(t('core::import.imported'))
      }

      // In case of any custom options created, reset the
      // store state for the cached fields
      resetStoreState()
    })
    .catch(() => {
      router.replace({ query: {} })
      importingViaBatch.value = false
    })
}

/**
 * Perform the import for the current import instance
 */
function performImport() {
  importIsInProgress.value = true

  Innoclapps.request()
    .post(appendQueryString(generateImportUri(importBeingMapped.value.id)), {
      mappings: importBeingMapped.value.mappings,
    })
    .then(({ data }) => {
      let index = findIndex(imports.value, ['id', parseInt(data.id)])

      if (index !== -1) {
        imports.value[index] = data
      } else {
        imports.value.push(data)
      }

      if (data.next_batch) {
        setTimeout(() => (window.location.href = `?import_id=${data.id}`), 1000)

        return
      }

      Innoclapps.success(t('core::import.imported'))
      importBeingMapped.value = null

      changeCurrentStep('04', 'complete')

      // In case of any custom options created, reset the
      // store state for the cached fields
      resetStoreState()
    })
    .catch(error => {
      let data = error.response.data

      if (data.deleted || data.rows_exceeded) {
        if (data.deleted) {
          handleAfterDelete(importBeingMapped.value.id)
        }

        if (data.rows_exceeded) {
          rowsExceededMessage.value = error.response.data.message
        }
      } else {
        changeCurrentStep('04', 'current')
      }
    })
    .finally(() => {
      importIsInProgress.value = false
      usingSkipFile.value = false
    })
}

async function prepareComponent() {
  await retrieveImports()

  if (route.query.import_id) {
    importingViaBatch.value = true
    continueImport(route.query.import_id)
  }
}

prepareComponent()
</script>
