<template>
  <IPopover @hide="cancelSearch">
    <IPopoverButton link>
      {{ totalGuestsText }}
    </IPopoverButton>

    <IPopoverPanel class="w-72">
      <IOverlay :show="isLoading">
        <IPopoverHeader>
          <IPopoverHeading :text="$t('activities::activity.guests')" />
        </IPopoverHeader>

        <IPopoverBody>
          <IFormGroup class="relative">
            <IFormInput
              v-model="searchQuery"
              class="pr-8"
              :placeholder="searchPlaceholder"
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

          <div class="max-h-96 overflow-y-auto break-all px-1">
            <div v-for="data in guestables" :key="data.resource">
              <ITextDark
                v-show="data.records.length > 0"
                class="mb-2 mt-3 font-medium"
                :text="data.title"
              />

              <IFormCheckboxField
                v-for="record in data.records"
                :key="data.resource + '-' + record.id"
                class="mb-2 gap-y-0"
              >
                <IFormCheckbox
                  v-model:checked="selected[data.resource]"
                  :value="record.id"
                  @change="onChange(record, data.resource, data.is_search)"
                />

                <IFormCheckboxLabel :text="record.guest_display_name" />

                <IFormCheckboxDescription
                  :class="record.guest_email ? '-mt-1' : ''"
                  :text="record.guest_email"
                />
              </IFormCheckboxField>
            </div>
          </div>
        </IPopoverBody>
      </IOverlay>
    </IPopoverPanel>
  </IPopover>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import filter from 'lodash/filter'
import findIndex from 'lodash/findIndex'
import isObject from 'lodash/isObject'
import map from 'lodash/map'
import sortBy from 'lodash/sortBy'
import uniq from 'lodash/uniq'

import { useApp } from '@/Core/composables/useApp'
import { useLoader } from '@/Core/composables/useLoader'
import { CancelToken } from '@/Core/services/HTTP'

const props = defineProps({
  /**
   * The actual v-model for the selected guests
   *
   * @type {Object}
   */
  modelValue: {},

  /**
   * Available contacts for selection
   *
   * @type {Object}
   */
  contacts: {},

  // All guests of the record, use only on EDIT
  // We need all the guests in case there are guests not directly associated with the resource
  guests: {},

  /**
   * The popover placement
   */
  placement: { type: String, default: 'bottom' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const { t } = useI18n()
const { setLoading, isLoading } = useLoader()

const minimumAsyncCharacters = 2
const totalAsyncSearchCharacters = ref(0)
const minimumAsyncCharactersRequirement = ref(false)
const limitSearchResults = 5
const searchQuery = ref('')
// The selected guests
const selected = ref({})
// Guests selected from search results
const selectedFromSearch = ref({})
const searchResults = ref({})
const cancelTokens = {}

const template = {
  contacts: {
    title: t('contacts::contact.contacts'),
    resource: 'contacts',
  },
  users: {
    title: t('users::user.users'),
    resource: 'users',
  },
}

const { users } = useApp()

watch(
  selected,
  newVal => {
    emit('update:modelValue', newVal)
  },
  { deep: true }
)

watch(
  () => props.guests,
  () => {
    setSelectedGuests()
  }
)

const resources = computed(() => Object.keys(template))

const totalCharactersLeftToPerformSearch = computed(
  () => minimumAsyncCharacters - totalAsyncSearchCharacters.value
)

const searchPlaceholder = computed(() => t('core::app.search_records'))

const hasSearchResults = computed(() => {
  let hasSearchResult = false

  resources.value.every(resource => {
    hasSearchResult = searchResults.value[resource]
      ? searchResults.value[resource].records.length > 0
      : false

    return hasSearchResult ? false : true
  })

  return hasSearchResult
})

const isSearching = computed(() => searchQuery.value != '')

const totalGuestsText = computed(() => {
  let totalSelected = 0

  resources.value.forEach(resource => {
    totalSelected += selected.value[resource]
      ? selected.value[resource].length
      : 0
  })

  return t('activities::activity.count_guests', totalSelected)
})

const guestables = computed(() => {
  if (hasSearchResults.value) {
    return searchResults.value
  }

  let guestables = {}

  let addRecord = (resourceName, record) => {
    if (
      findIndex(guestables[resourceName].records, [
        'id',
        parseInt(record.id),
      ]) === -1
    ) {
      guestables[resourceName].records.push(record)
    }
  }

  resources.value.forEach(resource => {
    guestables[resource] = Object.assign({}, template[resource], {
      records: [],
    })

    if (props.guests) {
      filter(props.guests, ['resource_name', resource]).forEach(record =>
        addRecord(resource, record)
      )
    }

    // Check for any selected from search
    if (selectedFromSearch.value[resource]) {
      selectedFromSearch.value[resource].records.forEach(record =>
        addRecord(resource, record)
      )
    }
  })

  users.value.forEach(user => addRecord('users', user))
  props.contacts.forEach(contact => addRecord('contacts', contact))

  return map(guestables, data => {
    data.records = sortBy(data.records, 'guest_display_name')

    return data
  })
})

function createResolveableRequests(q) {
  // The order of the promises must be the same
  // like in the order of the template keys data property
  // Create promises array
  let promises = []

  resources.value.forEach(resource => {
    promises.push(
      Innoclapps.request().get(`/${resource}/search`, {
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

/**
 * Cancel any previous requests via the cancel token
 *
 * @return {Void}
 */
function cancelPreviousRequests() {
  Object.keys(cancelTokens).forEach(resource => {
    if (cancelTokens[resource]) {
      cancelTokens[resource]()
    }
  })
}

function onChange(record, resource, fromSearch) {
  if (!selectedFromSearch.value[resource] && fromSearch) {
    selectedFromSearch.value[resource] = {
      records: [],
      is_search: fromSearch,
    }
  }

  nextTick(() => {
    // User checked record selected from search
    if (selected.value[resource].includes(record.id) && fromSearch) {
      selectedFromSearch.value[resource].records.push(record)
    } else if (selectedFromSearch.value[resource]) {
      // Unchecked, now remove it it from the selectedFromSearch
      let selectedIndex = findIndex(
        selectedFromSearch.value[resource].records,
        ['id', parseInt(record.id)]
      )

      if (selectedIndex != -1) {
        selectedFromSearch.value[resource].records.splice(selectedIndex, 1)
      }
    }

    emit('change', selected.value)
  })
}

function cancelSearch() {
  searchQuery.value = ''
  search('')
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
    resources.value.forEach((resource, key) => {
      searchResults.value[resource] = Object.assign({}, template[resource], {
        records: map(values[key].data, record => {
          record.from_search = true

          return record
        }),
        is_search: true,
      })
    })

    setLoading(false)
  })
}

function setSelectedGuests() {
  let selectedGuests = {}

  resources.value.forEach(resource => {
    let resourceSelected = []

    if (props.modelValue && props.modelValue[resource]) {
      resourceSelected = isObject(props.modelValue[resource][0])
        ? map(props.modelValue[resource], 'id')
        : props.modelValue[resource]
    }

    // Set the selected value via the guests
    if (props.guests) {
      resourceSelected = resourceSelected.concat(
        map(filter(props.guests, ['resource_name', resource]), 'id')
      )
    }
    selectedGuests[resource] = uniq(resourceSelected)
  })

  selected.value = selectedGuests
}

onMounted(() => {
  nextTick(setSelectedGuests)
})

defineExpose({ setSelectedGuests })
</script>
