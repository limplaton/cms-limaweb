<template>
  <IPopover @hide="cancelSearch" @show="handlePopoverShow">
    <IPopoverButton :text="associationsText" link />

    <IPopoverPanel :class="widthClass">
      <IOverlay :show="associationsBeingSaved || isLoading">
        <IPopoverHeader>
          <IPopoverHeading :text="$t('core::app.associate_with_record')" />
        </IPopoverHeader>

        <IPopoverBody>
          <IFormGroup class="relative">
            <IFormInput
              v-model="searchQuery"
              class="pr-8"
              :placeholder="$t('core::app.search_records')"
              debounce
              @input="search"
            />

            <IButton
              v-show="searchQuery"
              class="absolute right-1.5 top-1.5 sm:top-1"
              icon="XSolid"
              basic
              small
              @click="cancelSearch"
            />
          </IFormGroup>

          <IText
            v-show="
              isSearching &&
              !hasSearchResults &&
              !isLoading &&
              !minimumAsyncCharactersRequirement
            "
            class="text-center"
            :text="$t('core::app.no_search_results')"
          />

          <IText
            v-show="isSearching && minimumAsyncCharactersRequirement"
            class="text-center"
            :text="
              $t('core::app.type_more_to_search', {
                characters: totalCharactersLeftToPerformSearch,
              })
            "
          />

          <div v-for="data in records" :key="data.resource">
            <ITextDark
              v-show="data.records.length > 0"
              class="mb-2 mt-3 font-medium"
              :text="data.title"
            />

            <IFormCheckboxField
              v-for="(record, index) in data.records"
              :key="data.resource + '-' + record.id"
            >
              <IFormCheckbox
                :id="data.resource + '-' + record.id"
                v-model:checked="selected[data.resource]"
                :value="record.id"
                :disabled="
                  record.disabled ||
                  (primaryRecordDisabled === true &&
                    primaryResourceName === data.resource &&
                    hasPrimaryRecord &&
                    parseInt(primaryRecord.id) === parseInt(record.id))
                "
                @change="
                  onCheckboxChange(record, data.resource, data.is_search)
                "
              />

              <div class="flex items-center">
                <IFormCheckboxLabel
                  class="grow"
                  :for="data.resource + '-' + record.id"
                >
                  {{ record.display_name }}
                </IFormCheckboxLabel>

                <ILink
                  v-if="record.path && record.path !== $route.path"
                  class="-mr-1 -mt-1"
                  variant="neutral"
                  :to="record.path"
                >
                  <Icon icon="Eye" class="size-4" />
                </ILink>

                <slot
                  name="after-record"
                  :index="index"
                  :title="data.title"
                  :resource="data.resource"
                  :record="record"
                  :is-searching="isSearching"
                  :is-selected="selected[data.resource].includes(record.id)"
                  :selected-records="selected[data.resource]"
                />
              </div>
            </IFormCheckboxField>
          </div>
        </IPopoverBody>
      </IOverlay>
    </IPopoverPanel>
  </IPopover>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, toRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import castArray from 'lodash/castArray'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'
import map from 'lodash/map'
import orderBy from 'lodash/orderBy'
import sortBy from 'lodash/sortBy'

import { useLoader } from '@/Core/composables/useLoader'
import { useResourceable } from '@/Core/composables/useResourceable'
import { CancelToken } from '@/Core/services/HTTP'

const props = defineProps({
  widthClass: { type: String, default: 'w-72' },
  // The actual v-model for the selected associations
  modelValue: Object,
  resourceName: String,
  resourceId: [String, Number],
  associationsCount: Number,

  primaryResourceName: String,
  primaryRecord: Object,
  primaryRecordDisabled: Boolean,

  initialAssociateables: Object,
  associateables: Object, // selected by default

  limitInitialAssociateables: { type: Number, default: 3 },

  excludedResources: [String, Array],
})

const emit = defineEmits([
  'update:modelValue',
  'update:associationsCount',
  'synced',
  'change',
])

const { t } = useI18n()
const { setLoading, isLoading } = useLoader()

const minimumAsyncCharacters = 2
const totalAsyncSearchCharacters = ref(0)
const minimumAsyncCharactersRequirement = ref(false)
const limitSearchResults = 5
const searchQuery = ref('')
const associatedResources = ref([])
// The selected associations
const selected = ref({})
// Associations selected from search results
const selectedFromSearchResults = ref({})
const searchResults = ref({})
const cancelTokens = {}

const availableAssociateables = {
  contacts: {
    title: t('contacts::contact.contacts'),
    resource: 'contacts',
  },
  companies: {
    title: t('contacts::company.companies'),
    resource: 'companies',
  },
  deals: {
    title: t('deals::deal.deals'),
    resource: 'deals',
  },
}

const { syncAssociations, associationsBeingSaved } = useResourceable(
  toRef(props, 'resourceName')
)

if (props.excludedResources) {
  castArray(props.excludedResources).forEach(resourceName => {
    delete availableAssociateables[resourceName]
  })
}

const totalCharactersLeftToPerformSearch = computed(
  () => minimumAsyncCharacters - totalAsyncSearchCharacters.value
)

const isSearching = computed(() => searchQuery.value != '')

const hasPrimaryRecord = computed(() => Boolean(props.primaryRecord))

/**
 * The available associations resources, sorted as the primary is always first
 */
const resources = computed(() =>
  sortBy(Object.keys(availableAssociateables), resource => {
    return [resource !== props.primaryResourceName, resource]
  })
)

const allSelectedIdsString = computed(() => {
  let ids = []

  for (let resource in resources.value) {
    ids = ids.concat(getSelectedResourceIds(resource))
  }

  return ids.join(',')
})

const hasSearchResults = computed(() =>
  resources.value.some(
    resource =>
      searchResults.value[resource] &&
      searchResults.value[resource].records.length > 0
  )
)

const records = computed(() => {
  if (hasSearchResults.value) {
    return searchResults.value
  }

  let data = {}

  let addRecord = (resourceName, record) => {
    if (
      findIndex(data[resourceName].records, ['id', parseInt(record.id)]) === -1
    ) {
      data[resourceName].records.push(record)
    }
  }

  resources.value.forEach(resource => {
    data[resource] = Object.assign({}, availableAssociateables[resource], {
      records: [],
    })

    // Push the primary associateable
    if (hasPrimaryRecord.value && resource === props.primaryResourceName) {
      addRecord(resource, props.primaryRecord)
    }

    if (props.initialAssociateables) {
      getParsedAssociateablesFromInitialData(resource).forEach(record =>
        addRecord(resource, record)
      )
    }

    if (Object.hasOwn(props.associateables || {}, resource)) {
      props.associateables[resource].forEach(record =>
        addRecord(resource, record)
      )
    }

    if (Object.hasOwn(associatedResources.value, resource)) {
      associatedResources.value[resource].forEach(record =>
        addRecord(resource, record)
      )
    }

    // Check for any selected from search
    if (Object.hasOwn(selectedFromSearchResults.value, resource)) {
      selectedFromSearchResults.value[resource].records.forEach(record =>
        addRecord(resource, record)
      )
    }
  })

  return data
})

const totalSelected = computed(() => {
  let total = props.associationsCount || 0

  if (
    resources.value.some(
      resource =>
        selected.value[resource] && selected.value[resource].length > 0
    )
  ) {
    total = calculateTotalSelectedFromSelectedObject()
  }

  return total
})

const associationsText = computed(() => {
  if (totalSelected.value === 0) {
    return t('core::app.no_associations')
  }

  return t('core::app.associated_with_total_records', {
    count: totalSelected.value,
  })
})

function calculateTotalSelectedFromSelectedObject() {
  return Object.values(selected.value).reduce((total, resource) => {
    return total + resource.length
  }, 0)
}

function retrieveAssociatedResources() {
  setLoading(true)

  Innoclapps.request(
    `/associations/${props.resourceName}/${props.resourceId}`,
    {
      perPage: 100,
    }
  )
    .then(({ data }) => {
      associatedResources.value = data
      setSelectedRecords()
    })
    .finally(() => setLoading(false))
}

function handlePopoverShow() {
  if (props.resourceName && props.resourceId) {
    retrieveAssociatedResources()
  }
}

function getParsedAssociateablesFromInitialData(resource) {
  return orderBy(
    (props.initialAssociateables[resource] || []).slice(
      0,
      props.limitInitialAssociateables
    ),
    'created_at',
    'desc'
  )
}

function createResolveableRequests(q) {
  // The order of the promises must be the same
  // like in the order of the availableAssociateables keys data variable
  let promises = []

  resources.value.forEach(resource => {
    promises.push(
      Innoclapps.request(`/${resource}/search`, {
        params: {
          q: q,
          take: limitSearchResults,
        },
        cancelToken: new CancelToken(token => (cancelTokens[resource] = token)),
      })
    )
  })

  return promises
}

function cancelPreviousRequests() {
  Object.values(cancelTokens).forEach(cancel => {
    if (cancel) cancel()
  })
}

async function onCheckboxChange(record, resource, fromSearch) {
  if (!selectedFromSearchResults.value[resource] && fromSearch) {
    selectedFromSearchResults.value[resource] = {
      records: [],
      is_search: fromSearch,
    }
  }

  await nextTick()

  // User checked record selected from search
  if (selected.value[resource].includes(record.id) && fromSearch) {
    selectedFromSearchResults.value[resource].records.push(record)
  } else if (selectedFromSearchResults.value[resource]) {
    // Unchecked, now remove it it from the selectedFromSearchResults
    let selectedIndex = findIndex(
      selectedFromSearchResults.value[resource].records,
      ['id', parseInt(record.id)]
    )

    if (selectedIndex != -1) {
      selectedFromSearchResults.value[resource].records.splice(selectedIndex, 1)
    }
  }

  if (props.modelValue == undefined) {
    syncAssociations(props.resourceId, selected.value).then(updatedRecord => {
      emit('synced', updatedRecord)
      emit('change', selected.value)
      // Re-fetch the all of the associations to reflect the "is_primary_associated" attribute.
      retrieveAssociatedResources()
    })
  } else {
    emit('change', selected.value)
  }

  await nextTick()
  emit('update:associationsCount', calculateTotalSelectedFromSelectedObject())
}

function cancelSearch() {
  searchQuery.value = ''
  search('')
  cancelPreviousRequests()
}

function search(q) {
  const totalCharacters = q.length

  if (totalCharacters === 0) {
    searchResults.value = {}

    return
  }

  totalAsyncSearchCharacters.value = totalCharacters

  if (totalCharacters < minimumAsyncCharacters) {
    minimumAsyncCharactersRequirement.value = true

    return q
  }

  minimumAsyncCharactersRequirement.value = false
  cancelPreviousRequests()
  setLoading(true)

  Promise.all(createResolveableRequests(q)).then(values => {
    let results = {}

    resources.value.forEach((resource, key) => {
      results[resource] = Object.assign({}, availableAssociateables[resource], {
        records: map(values[key].data, record => {
          record.from_search = true

          // If the record it's originally disabled and exists in search results,
          // make sure it's disabled via the search results records as well.
          record.disabled =
            find(records.value[resource]?.records || [], ['id', record.id])
              ?.disabled || false

          return record
        }),
        is_search: true,
      })
    })

    searchResults.value = results

    setLoading(false)
  })
}

function getSelectedResourceIds(resource) {
  let ids = props.modelValue?.[resource] ?? []

  if (Object.hasOwn(associatedResources.value, resource)) {
    ids = ids.concat(map(associatedResources.value[resource], 'id'))
  }

  if (Object.hasOwn(props.associateables || {}, resource)) {
    ids = ids.concat(map(props.associateables[resource], 'id'))
  }

  return [...new Set(ids)] // Using Set for uniqueness
}

function setSelectedRecords() {
  const _selected = {}

  for (let resource of resources.value) {
    _selected[resource] = getSelectedResourceIds(resource)

    // When provided and not disabled, the primary resource is always selected.
    if (
      resource === props.primaryResourceName &&
      props.primaryRecordDisabled === true &&
      hasPrimaryRecord.value &&
      !_selected[resource].includes(props.primaryRecord.id)
    ) {
      _selected[resource].push(props.primaryRecord.id)
    }
  }

  selected.value = _selected
  emit('update:modelValue', _selected)
}

watch(allSelectedIdsString, () => {
  setSelectedRecords()
})

onMounted(setSelectedRecords)

defineExpose({ retrieveAssociatedResources })
</script>
