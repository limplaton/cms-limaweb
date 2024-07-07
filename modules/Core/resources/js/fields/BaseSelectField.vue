<template>
  <BaseFormField
    v-slot="baseFieldSlotProps"
    :field="field"
    :value="value"
    v-bind="$attrs"
  >
    <slot
      v-bind="{
        ...baseFieldSlotProps,
        ...{
          selectValue,
          handleSelectInputChange,
          createOption,
          onDropdownOpen: tryToLazyLoadOptions,
          headerText,
          noOptionsText,
          onSearch,
          lazyLoadingOptions,
          filterable,
          options: filteredOptions,
        },
      }"
    />
  </BaseFormField>
</template>

<script setup>
import { computed, ref, toValue } from 'vue'
import { watch } from 'vue'
import { useI18n } from 'vue-i18n'
import find from 'lodash/find'
import uniqBy from 'lodash/uniqBy'

import { debounce, isBlank } from '@/Core/utils'

defineOptions({ inheritAttrs: false })

const props = defineProps(['field', 'value', 'options'])

const { t } = useI18n()

const performedAsyncSearch = ref(false)
const minimumAsyncCharacters = ref(2)
const totalAsyncSearchCharacters = ref(0)
const minimumAsyncCharactersRequirement = ref(false)
const lazyLoadingOptions = ref(false)
const optionsFromAsyncSearch = ref(null)
const lazyLoadedOptions = ref(null)

const isAsync = computed(() => Boolean(props.field.asyncUrl))
const filterable = computed(() => (isAsync.value ? false : true))

const filteredOptions = computed(() => {
  if (Array.isArray(props.options)) {
    return props.options
  }

  const optionsFromFieldValue = (function () {
    if (!props.field.value) return []

    if (Array.isArray(props.field.value)) {
      return props.field.value.filter(option => typeof option === 'object')
    }

    if (typeof props.field.value === 'object') return [props.field.value]

    return []
  })()

  if (Array.isArray(optionsFromAsyncSearch.value)) {
    return optionsFromAsyncSearch.value
  }

  if (lazyLoadedOptions.value) {
    return uniqBy(
      [].concat(
        lazyLoadedOptions.value,
        props.field.options || [],
        optionsFromFieldValue
      ),
      props.field.valueKey
    )
  }

  return uniqBy(
    [].concat(props.field.options || [], optionsFromFieldValue),
    props.field.valueKey
  )
})

const selectValue = ref(null)

const headerText = computed(() => {
  if (isAsync.value && minimumAsyncCharactersRequirement.value) {
    return t('core::app.type_more_to_search', {
      characters:
        minimumAsyncCharacters.value - totalAsyncSearchCharacters.value,
    })
  }

  return ''
})

const noOptionsText = computed(() => {
  if (!isAsync.value || (isAsync.value && performedAsyncSearch.value)) {
    return t('core::app.no_search_results')
  }

  // This is shown only the first time user clicked on the select (only for async)
  return t('core::app.type_to_search')
})

async function tryToLazyLoadOptions() {
  if (!props.field.lazyLoad || lazyLoadedOptions.value !== null) {
    return
  }

  let allLoadedOptions = []
  const lazyLoad = toValue(props.field.lazyLoad)

  for (const lazyLoadData of !Array.isArray(lazyLoad) ? [lazyLoad] : lazyLoad) {
    let loadedOptions = await lazyLoadOptions(
      lazyLoadData.url,
      lazyLoadData.params
    )

    allLoadedOptions = allLoadedOptions.concat(
      loadedOptions?.data || loadedOptions
    )
  }

  lazyLoadedOptions.value = uniqBy(allLoadedOptions, 'id')
}

async function lazyLoadOptions(url, params) {
  lazyLoadingOptions.value = true

  const { data } = await Innoclapps.request(url, {
    params,
  })

  lazyLoadingOptions.value = false

  return data
}

function onSearch(search, loading) {
  // Regular search is performed via the select field when it's not async.
  if (!isAsync.value) {
    return
  }

  asyncSearch(search, loading)
}

const asyncSearch = debounce(async (q, loading) => {
  if (q == '') {
    optionsFromAsyncSearch.value = null

    return
  }

  const totalCharacters = q.length

  totalAsyncSearchCharacters.value = totalCharacters

  if (filterable.value || totalCharacters < minimumAsyncCharacters.value) {
    minimumAsyncCharactersRequirement.value = true

    return q
  }

  minimumAsyncCharactersRequirement.value = false
  loading(true)

  let { data } = await Innoclapps.request(props.field.asyncUrl, {
    params: {
      q: q,
    },
  })

  optionsFromAsyncSearch.value = data
  performedAsyncSearch.value = true

  loading(false)
}, 400)

function createOption(newOption) {
  return {
    [props.field.valueKey]: newOption,
    [props.field.labelKey]: newOption,
  }
}

function handleSelectInputChange(value) {
  selectValue.value = value
}

function syncSelectValueBasedOnModelValue(newVal, oldVal) {
  if (isBlank(newVal)) {
    selectValue.value = null

    return
  }

  // Remove any previous values in select
  if (
    Array.isArray(oldVal) &&
    Array.isArray(selectValue.value) &&
    selectValue.value.length > 0
  ) {
    oldVal.forEach(val => {
      // Only update if the old value does not exists in the new value from the watch trigger.
      if (newVal.indexOf(val) === -1) {
        if (typeof selectValue.value[0] === 'object') {
          selectValue.value = selectValue.value.filter(
            object => object[props.field.valueKey] != val
          )
        } else {
          selectValue.value = selectValue.value.filter(
            selectVal => selectVal != oldVal
          )
        }
      }
    })
  }

  // Handle array's
  if (
    filteredOptions.value.length &&
    typeof filteredOptions.value[0] === 'object'
  ) {
    // multi select
    if (Array.isArray(newVal)) {
      if (!Array.isArray(selectValue.value)) {
        selectValue.value = []
      }

      newVal.forEach(val => {
        let selectedOptionObject = find(filteredOptions.value, [
          props.field.valueKey,
          val,
        ])

        if (
          selectedOptionObject &&
          !find(selectValue.value, [props.field.valueKey, val])
        ) {
          selectValue.value.push(selectedOptionObject)
        }
      })
    } else {
      // regular/single select
      if (selectValue.value != newVal) {
        selectValue.value = find(filteredOptions.value, [
          props.field.valueKey,
          newVal,
        ])
      }
    }
  }
}

watch(() => props.value, syncSelectValueBasedOnModelValue, {
  immediate: true,
  deep: true,
})
</script>
