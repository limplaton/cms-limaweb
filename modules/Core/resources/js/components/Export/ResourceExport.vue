<template>
  <IModal
    :id="modalId"
    :title="computedTitle"
    :ok-loading="exportInProgress"
    :ok-disabled="exportInProgress"
    :ok-text="$t('core::app.export.export')"
    @ok="performExport"
  >
    <IFormGroup :label="$t('core::app.export.type')">
      <IFormSelect v-model="form.type">
        <option value="csv">CSV</option>

        <option value="xls">XLS</option>

        <option value="xlsx">XLSX</option>
      </IFormSelect>
    </IFormGroup>

    <IFormGroup class="space-y-1">
      <template #label>
        <div class="inline-flex items-center space-x-2">
          <IFormLabel as="p" :label="$t('core::dates.range')" />

          <IDropdown
            v-if="form.period !== 'all'"
            @show="fetchFieldsIfNotFetched"
          >
            <IDropdownButton
              class="mt-0.5"
              :text="
                !selectedDateRangeField
                  ? $t('core::app.created_at')
                  : selectedDateRangeField.label
              "
              basic
            />

            <IDropdownMenu>
              <ISpinner
                v-if="!dateableFields.length"
                class="h-5 w-5 text-neutral-400"
              />

              <IDropdownItem
                v-for="dateField in dateableFields"
                :key="dateField.attribute"
                :text="dateField.label"
                :active="
                  (selectedDateRangeField &&
                    selectedDateRangeField.attribute === dateField.attribute) ||
                  (!selectedDateRangeField &&
                    dateField.attribute === 'created_at')
                "
                @click="selectedDateRangeField = dateField"
              />
            </IDropdownMenu>
          </IDropdown>
        </div>
      </template>

      <IFormRadioField v-for="period in periods" :key="period.text">
        <IFormRadio v-model="form.period" name="period" :value="period.value" />

        <IFormRadioLabel :text="period.text" />
      </IFormRadioField>
    </IFormGroup>

    <IFormGroup v-if="isCustomOptionSelected" class="sm:ml-6">
      <IFormLabel
        for="custom-period-start"
        class="mb-1"
        :label="$t('core::app.export.select_range')"
      />

      <DateRangePicker
        id="custom-period"
        v-model="form.customPeriod"
        name="custom-period"
      />
    </IFormGroup>

    <IFormGroup class="mt-6">
      <IFormRadioField>
        <IFormRadio
          v-model="fieldsAreBeingSelected"
          name="select_fields"
          :value="false"
        />

        <IFormRadioLabel :text="$t('core::fields.all')" />
      </IFormRadioField>

      <IFormRadioField>
        <IFormRadio
          v-model="fieldsAreBeingSelected"
          name="select_fields"
          :value="true"
        />

        <IFormRadioLabel :text="$t('core::fields.select')" />
      </IFormRadioField>
    </IFormGroup>

    <div v-if="fieldsAreBeingSelected">
      <div class="grid grid-cols-3 gap-x-4 gap-y-2">
        <div
          v-for="field in fields"
          :key="field.attribute"
          class="flex items-center rounded-md border border-neutral-200 px-3 py-1 dark:border-neutral-500/30"
        >
          <IFormCheckboxField>
            <IFormCheckbox
              v-model:checked="form.fields"
              :value="field.attribute"
              :disabled="field.primary"
            />

            <IFormCheckboxLabel
              class="max-w-full truncate"
              :text="field.label"
            />
          </IFormCheckboxField>
        </div>
      </div>
    </div>

    <div
      v-show="canUseFilterForExport"
      class="mt-5 rounded-lg border border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-500 dark:bg-neutral-700"
    >
      <IFormCheckboxField>
        <IFormCheckbox v-model:checked="shouldApplyFilters" />

        <IFormCheckboxLabel :text="$t('core::app.export.apply_filters')" />
      </IFormCheckboxField>
    </div>
  </IModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { whenever } from '@vueuse/core'
import FileDownload from 'js-file-download'

import { useDates } from '@/Core/composables/useDates'
import { useForm } from '@/Core/composables/useForm'
import { useQueryBuilder } from '@/Core/composables/useQueryBuilder'

const props = defineProps({
  resourceName: String,
  filtersView: String,
  title: String,
  urlPath: { required: true, type: String },
  modalId: { default: 'export-modal', type: String },
})

const { t } = useI18n()
const { UTCDateTimeInstance } = useDates()

const { form } = useForm({
  period: 'last_7_days',
  type: 'csv',
  fields: [],
  customPeriod: {
    start: UTCDateTimeInstance.startOf('month').toISODate(),
    end: UTCDateTimeInstance.toISODate(),
  },
})

const fields = ref([])
const selectedDateRangeField = ref(null)
const fieldsAreBeingSelected = ref(false)

const fieldsFetched = computed(() => fields.value.length > 0)

const dateableFields = computed(() =>
  fields.value.filter(f => f.dateable === true)
)

const periods = [
  { text: t('core::dates.today'), value: 'today' },
  { text: t('core::dates.periods.7_days'), value: 'last_7_days' },
  { text: t('core::dates.this_month'), value: 'this_month' },
  { text: t('core::dates.last_month'), value: 'last_month' },
  { text: t('core::app.all'), value: 'all', id: 'all' },
  { text: t('core::dates.custom'), value: 'custom', id: 'custom' },
]

const {
  queryBuilderRules,
  hasRulesApplied,
  rulesAreValid: hasValidFilterRules,
} = useQueryBuilder(props.resourceName, props.filtersView)

const shouldApplyFilters = ref(true)

const exportInProgress = ref(false)

const computedTitle = computed(
  () => props.title || t('core::app.export.export')
)

const isCustomOptionSelected = computed(() => form.period === 'custom')

const canUseFilterForExport = computed(
  () => hasRulesApplied.value && hasValidFilterRules.value
)

function getFileNameFromResponseHeaders(response) {
  return response.headers['content-disposition'].split('filename=')[1]
}

async function fetchFieldsIfNotFetched() {
  if (!fieldsFetched.value) {
    fields.value = await retrieveExportFields()
  }
}

async function retrieveExportFields() {
  const { data } = await Innoclapps.request(
    `${props.resourceName}/export-fields`
  )

  return data
}

function performExport() {
  exportInProgress.value = true

  Innoclapps.request()
    .post(
      props.urlPath,
      {
        fields: fieldsAreBeingSelected.value ? form.fields : null,
        date_range_field:
          form.period !== 'all'
            ? selectedDateRangeField.value?.attribute
            : null,
        period: !isCustomOptionSelected.value
          ? form.period === 'all'
            ? null
            : form.period
          : form.customPeriod,
        type: form.type,
        filters:
          shouldApplyFilters.value && canUseFilterForExport.value
            ? queryBuilderRules.value
            : null,
      },
      {
        responseType: 'blob',
      }
    )
    .then(response => {
      FileDownload(response.data, getFileNameFromResponseHeaders(response))
    })
    .finally(() => (exportInProgress.value = false))
}

whenever(fieldsAreBeingSelected, async () => {
  await fetchFieldsIfNotFetched()

  if (form.fields.length === 0) {
    fields.value.forEach(field => {
      form.fields.push(field.attribute)
    })
  }
})
</script>
