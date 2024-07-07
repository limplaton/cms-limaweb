<template>
  <div
    v-bind="$attrs"
    ref="reference"
    role="button"
    :class="[
      'relative w-full text-left text-base/6 sm:text-sm/6',
      {
        'ring-2 ring-inset ring-primary-600 dark:ring-primary-600':
          open && !simple,
        'rounded-lg px-3.5 py-2.5 shadow-sm ring-1 ring-inset ring-neutral-300 dark:ring-neutral-500/30 sm:px-3 sm:py-1.5':
          !simple,
        'bg-white dark:bg-neutral-500/10': !simple && !disabled,
        'bg-neutral-200 dark:bg-neutral-700/10': !simple && disabled,
        'pointer-events-none': disabled,
      },
    ]"
    @click="open ? hide() : show()"
  >
    <div class="flex items-center">
      <div
        :id="`cs${uid}__combobox`"
        role="combobox"
        aria-label="Search for an option"
        :class="[
          'flex items-center',
          {
            'cursor-text': filterable && !simple,
            truncate: truncate,
          },
        ]"
        :aria-expanded="dropdownOpen.toString()"
        :aria-owns="`cs${uid}__listbox`"
      >
        <div
          v-show="!search || multiple"
          :class="[
            'space-x-1.5',
            {
              'absolute left-auto flex opacity-40': !multiple && dropdownOpen,
              'max-w-[calc(100%-55px)]': truncate && !multiple && dropdownOpen,
              'overflow-hidden': truncate,
            },
          ]"
        >
          <slot
            v-for="option in selectedValue"
            name="selected-option-container"
            :option="normalizeOptionForSlot(option)"
            :label="getOptionLabel(option)"
            :truncate="truncate"
            v-bind="selectedOptionAttrs"
          >
            <ISelectSelectedOption
              v-slot="{ label }"
              :option="option"
              :label="getOptionLabel(option)"
              :class="[
                typeof truncate === 'string' ? truncate : '',
                truncate ? 'block min-w-0 truncate' : '',
              ]"
              v-bind="selectedOptionAttrs"
            >
              <slot
                name="selected-option"
                v-bind="normalizeOptionForSlot(option)"
              >
                {{ label }}
              </slot>

              <slot name="after-selected-option-inner" :option="option" />
            </ISelectSelectedOption>
          </slot>
        </div>
      </div>

      <div v-show="!simple" class="mr-4 grow">
        <input
          :id="inputId"
          ref="inputSearchRef"
          type="search"
          aria-autocomplete="list"
          :class="[
            (dropdownOpen || isValueEmpty || searching) && !simple
              ? 'w-full'
              : 'w-0',
            'cs__search border-0 bg-transparent p-0 text-base leading-none placeholder-neutral-400 focus:border-0 focus:ring-0 dark:text-white dark:placeholder-neutral-400 sm:text-sm',
          ]"
          :name="inputName"
          :tabindex="tabindex"
          :disabled="disabled"
          :value="search"
          :autocomplete="autocomplete"
          :placeholder="searchPlaceholder"
          :readonly="simple"
          :aria-labelledby="`cs${uid}__combobox`"
          :aria-controls="`cs${uid}__listbox`"
          :aria-activedescendant="
            dropdownOpen && filteredOptions[typeAheadPointer]
              ? `cs${uid}__option-${typeAheadPointer}`
              : undefined
          "
          @blur="onSearchBlur"
          @focus="onSearchFocus"
          @input="search = $event.target.value"
          @compositionstart="isComposing = true"
          @compositionend="isComposing = true"
          @keydown.delete="maybeDeleteValue"
          @keydown.esc="onEscape"
          @keydown.prevent.up="typeAheadUp"
          @keydown.prevent.down="typeAheadDown"
          @keydown.prevent.enter="!isComposing && typeAheadSelect()"
        />
      </div>

      <div
        class="flex items-center [&>[data-slot=icon]]:size-5 [&>[data-slot=icon]]:shrink-0 [&>[data-slot=icon]]:text-neutral-400 [&>[data-slot=icon]]:sm:size-4"
      >
        <ISelectClearButton
          v-show="showClearButton"
          class="mr-2"
          :disabled="disabled"
          @clear="clearSelection"
        />

        <ISpinner v-if="mutableLoading" class="-mt-0.5 mr-2" />

        <Icon v-if="showToggleIcon" class="toggle-icon" :icon="toggleIcon" />
      </div>
    </div>
  </div>

  <Teleport :to="teleportTo">
    <div
      v-if="open"
      ref="dropdownMenuWrapperRef"
      :class="[
        '__popper overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-500/30 dark:bg-neutral-800',
        listWrapperClass,
      ]"
      :style="{
        zIndex: 99999,
        width:
          typeof referenceElWidth === 'number' ? `${referenceElWidth}px` : '',
        ...floatingStyles,
      }"
    >
      <div
        v-if="simple && filterable"
        class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-500/30"
      >
        <IFormInput
          v-model="search"
          type="search"
          :placeholder="searchPlaceholder"
        />
      </div>

      <slot name="header" />

      <SortableDraggable
        v-bind="{ ...$draggable.common, ghostClass: 'drag-ghost' }"
        :id="`cs${uid}__listbox`"
        ref="dropdownMenuRef"
        :key="`cs${uid}__listbox`"
        handle=".select-option-draggable-handle"
        tag="ul"
        role="listbox"
        :model-value="filteredOptions"
        :item-key="item => getOptionKey(item)"
        :class="[listClass, 'max-h-80 overflow-y-auto']"
        @update:model-value="$emit('update:draggable', $event)"
      >
        <template v-if="totalFilteredOptions === 0 && !loading" #header>
          <li
            class="relative cursor-default select-none px-4 py-2 text-base/6 text-neutral-600 dark:text-neutral-300 sm:text-sm/6"
          >
            <slot
              name="no-options"
              v-bind="{
                search: search,
                loading: loading,
                searching: searching,
                text: noOptionsText,
              }"
            >
              {{ noOptionsText }}
            </slot>
          </li>
        </template>

        <template #item="{ element: option, index }">
          <ISelectOption
            :key="index"
            :uid="uid"
            :swatch-color="option.swatch_color"
            :index="index"
            :active="index === typeAheadPointer"
            :label="getOptionLabel(option)"
            :is-selectable="selectable(option)"
            :is-selected="isOptionSelected(option)"
            @selected="select(option)"
            @type-ahead-pointer="typeAheadPointer = $event"
          >
            <template #default="{ label }">
              <slot name="option" v-bind="normalizeOptionForSlot(option)">
                {{ label }}
              </slot>
            </template>

            <template #option-inner="innerSlotProps">
              <span class="absolute right-5 top-2">
                <div class="flex items-center space-x-1.5">
                  <slot name="option-actions" v-bind="innerSlotProps" />

                  <Icon
                    v-show="filteredOptions.length > 1"
                    v-if="reorderable && !searching"
                    icon="Selector"
                    class="select-option-draggable-handle size-5 cursor-move text-neutral-600 dark:text-neutral-200"
                  />
                </div>
              </span>
            </template>
          </ISelectOption>
        </template>
      </SortableDraggable>

      <slot name="footer" />
    </div>
  </Teleport>
</template>

<script setup>
// TODO, https://vueuse.org/integrations/useFocusTrap/
import { computed, onBeforeUnmount, onMounted, ref, toRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { autoUpdate, flip, offset, useFloating } from '@floating-ui/vue'
import { onClickOutside } from '@vueuse/core'

import { debounce as debounceFn } from '@/Core/utils'

import IFormInput from '../Form/IFormInput.vue'
import ISpinner from '../ISpinner.vue'

import uniqueId from './utils/uniqueId'
import ISelectClearButton from './ISelectClearButton.vue'
import ISelectOption from './ISelectOption.vue'
import ISelectSelectedOption from './ISelectSelectedOption.vue'
import propsDefinition from './props'
import { useTypeAheadPointer } from './useTypeAheadPointer'

defineOptions({
  name: 'ICustomSelect',
  inheritAttrs: false,
})

const props = defineProps(propsDefinition)

const emit = defineEmits([
  'update:modelValue',
  'update:draggable',
  'open',
  'close',
  'cleared',
  'optionSelecting',
  'optionCreated',
  'optionSelected',
  'optionDeselecting',
  'optionDeselected',
  'search',
  'searchBlur',
  'searchFocus',
])

let debounceWatchHandle = null
let searchEmitterHandler = null
const reference = ref(null)
const dropdownMenuRef = ref(null)
const dropdownMenuWrapperRef = ref(null)
const inputSearchRef = ref(null)
const teleportTo = 'body'

const uid = uniqueId()
const search = ref('')
const open = ref(false)
const isComposing = ref(false)
const pushedTags = ref([])

// Internal value managed if no `modelValue` prop is passed
const _value = ref([])

const mutableLoading = ref(props.loading)

const { floatingStyles } = useFloating(reference, dropdownMenuWrapperRef, {
  placement: 'bottom',
  middleware: [offset(10), flip()],
  whileElementsMounted: autoUpdate,
})

const { t } = useI18n()

/**
 * Toggle props.loading. Optionally pass a boolean
 * value. If no value is provided, props.loading
 * will be set to the opposite of it's current value.
 */
function toggleLoading(toggle = null) {
  if (toggle == null) {
    return (mutableLoading.value = !mutableLoading.value)
  }

  return (mutableLoading.value = toggle)
}

/**
 * Local create option function
 */
function createOption(option) {
  let newOption = null

  if (props.createOptionProvider) {
    newOption = props.createOptionProvider(option)
  } else {
    newOption =
      typeof optionList.value[0] === 'object'
        ? { [props.label]: option }
        : option
  }

  emit('optionCreated', newOption)

  return newOption
}

/**
 * Callback to filter results when search text is provided.
 * Default implementation loops each option, and returns the result of "filterBy".
 */
function filter(options, search) {
  return options.filter(option => {
    let label = getOptionLabel(option)

    if (typeof label === 'number') {
      label = label.toString()
    }

    return props.filterBy(option, label, search)
  })
}

/**
 * Get the option label.
 */
function getOptionLabel(option) {
  if (props.optionLabel) {
    return props.optionLabel(option)
  }

  if (typeof option === 'object') {
    if (!Object.hasOwn(option, props.label)) {
      return console.warn(
        `[select warn]: Label key "option.${props.label}" does not` +
          ` exist in options object ${JSON.stringify(option)}.`
      )
    }

    return option[props.label]
  }

  return option
}

/**
 * Generate unique option key.
 */
function getOptionKey(option) {
  if (typeof option !== 'object') {
    return option
  }

  if (typeof props.optionKey === 'function') {
    return props.optionKey(option)
  }

  try {
    return Object.hasOwn(option, props.optionKey)
      ? option[props.optionKey]
      : sortAndStringify(option)
  } catch (e) {
    const warning =
      `[select warn]: Could not stringify this option ` +
      `to generate unique key. Please provide 'optionKey' prop ` +
      `to return a unique key for each option.`

    return console.warn(warning, option, e)
  }
}

function sortAndStringify(sortable) {
  const ordered = {}

  Object.keys(sortable)
    .sort()
    .forEach(key => {
      ordered[key] = sortable[key]
    })

  return JSON.stringify(ordered)
}

/**
 * Make sure tracked value is one option if possible.
 */
function setInternalValueFromOptions(value) {
  if (Array.isArray(value)) {
    _value.value = value.map(val => findOptionFromReducedValue(val))
  } else {
    _value.value = findOptionFromReducedValue(value)
  }
}

/**
 * Select a given option.
 */
function select(option) {
  emit('optionSelecting', option)

  if (!isOptionSelected(option)) {
    if (props.taggable && !optionExists(option)) {
      emit('optionCreated', option)
      pushTag(option)
    }

    if (props.multiple) {
      option = selectedValue.value.concat(option)
    }

    updateValue(option)
    emit('optionSelected', option)
  }

  onAfterSelect(option)
}

/**
 * De-select a given option.
 */
function deselect(option) {
  emit('optionDeselecting', option)

  updateValue(selectedValue.value.filter(val => !optionComparator(val, option)))

  emit('optionDeselected', option)
}

/**
 * Clears the currently selected value(s)
 */
function clearSelection() {
  updateValue(props.multiple ? [] : null)
  emit('cleared')
}

/**
 * Called from "select" after each selection.
 */
// eslint-disable-next-line no-unused-vars
function onAfterSelect(option) {
  if (props.closeOnSelect) {
    hide()
    // this.open = !this.open
    inputSearchRef.value.blur()
  }

  if (props.clearSearchOnSelect) {
    search.value = ''
  }
}

/**
 * Accepts a selected value, updates local state when required, and triggers the input event.
 */
function updateValue(value) {
  if (typeof props.modelValue === 'undefined') {
    // Vue select has to manage value
    _value.value = value
  }

  if (value !== null) {
    if (Array.isArray(value)) {
      value = value.map(val => props.reduce(val))
    } else {
      value = props.reduce(value)
    }
  }

  emit('update:modelValue', value)
}

function focus() {
  inputSearchRef.value.focus()
}

/**
 * Handle the dropdown shown event
 * We need to focus the search element when the dropdown is invoked via the toggle e.q. clicked not on the search input
 */
function show() {
  open.value = true
  focus()
}

/**
 * Handle the dropdown hidden event
 */
function hide() {
  open.value = false
  search.value = ''
}

/**
 * Check if the given option is currently selected.
 */
function isOptionSelected(option) {
  return selectedValue.value.some(value => optionComparator(value, option))
}

/**
 * Determine if two option objects are matching.
 */
function optionComparator(a, b) {
  if (props.optionComparatorProvider) {
    return props.optionComparatorProvider(a, b, getOptionKey)
  }

  return getOptionKey(a) === getOptionKey(b)
}

/**
 * Finds an option from the options where a reduced value matches the passed in value.
 */
function findOptionFromReducedValue(value) {
  const predicate = option =>
    JSON.stringify(props.reduce(option)) === JSON.stringify(value)

  const matches = [...props.options, ...pushedTags.value].filter(predicate)

  if (matches.length === 1) {
    return matches[0]
  }

  /**
   * This second loop is needed to cover an edge case where `taggable` + `reduce`
   * were used in conjunction with a `create-option` that doesn't create a
   * unique reduced value.
   *
   * @see https://github.com/sagalbot/vue-select/issues/1089#issuecomment-597238735
   */
  return matches.find(match => optionComparator(match, _value.value)) || value
}

/**
 * Delete the value on Delete keypress when there is no text in the search input, & there's tags to delete
 */
function maybeDeleteValue() {
  if (
    !inputSearchRef.value.value.length &&
    selectedValue.value &&
    selectedValue.value.length &&
    props.clearable
  ) {
    let value = null

    if (props.multiple) {
      value = Array.from(
        selectedValue.value.slice(0, selectedValue.value.length - 1)
      )
    }

    updateValue(value)
  }
}

/**
 * Determine if an option exists within "optionList" array.
 */
function optionExists(option) {
  return optionList.value.some(_option => optionComparator(_option, option))
}

/**
 * Ensures that options are always passed as objects to scoped slots.
 */
function normalizeOptionForSlot(option) {
  return typeof option === 'object' ? option : { [props.label]: option }
}

/**
 * If push-tags is true, push the given option to `pushedTags`.
 */
function pushTag(option) {
  pushedTags.value.push(option)
}

/**
 * If there is any text in the search input, remove it.
 * Otherwise, blur the search input to close the dropdown.
 */
function onEscape() {
  if (!search.value.length) {
    inputSearchRef.value.blur()
  } else {
    search.value = ''
  }
}

/**
 * Close the dropdown on blur.
 */
// eslint-disable-next-line no-unused-vars
function onSearchBlur(e) {
  emit('searchBlur')
}

/**
 * Open the dropdown on focus.
 */
function onSearchFocus() {
  emit('searchFocus')
}

/**
 * Get the text when there are not options available
 */
const noOptionsText = computed(() =>
  searching.value
    ? t('core::app.no_search_results')
    : t('core::app.not_enough_data')
)

/**
 * Determine if the component needs to track the state of values internally.
 */
const isTrackingValues = computed(
  () => typeof props.modelValue === 'undefined' || Boolean(props.reduce)
)

/**
 * The options that are currently selected.
 *
 * @return {Array}
 */
const selectedValue = computed(() => {
  let value = props.modelValue

  if (isTrackingValues.value) {
    // Vue select has to manage value internally
    value = _value.value
  }

  if (value) {
    return [].concat(value)
  }

  return []
})

/**
 * The options available to be chosen from the dropdown, including any tags that have been pushed.
 */
const optionList = computed(() =>
  props.options.concat(props.pushTags ? pushedTags.value : [])
)

/**
 * Return the current state of the search input
 */
const searching = computed(() => !!search.value)

/**
 * Return the current state of the dropdown menu.
 */
const dropdownOpen = computed(() => open.value && !mutableLoading.value)

/**
 * Return the placeholder string if it's set and there is no value selected.
 */
const searchPlaceholder = computed(() => {
  if (isValueEmpty.value && props.placeholder) {
    return props.placeholder
  }

  return ''
})

/**
 * Get the selected option attributes
 */
const selectedOptionAttrs = computed(() => ({
  deselect: deselect,
  multiple: props.multiple,
  disabled: props.disabled,
  searching: searching.value,
  simple: props.simple,
}))

/**
 * Get the total number of options in the select
 */
const totalFilteredOptions = computed(() => filteredOptions.value.length)

/**
 * The currently displayed options, filtered by the search elements value.
 * If tagging true, the search text will be prepended if it doesn't already exist.
 *
 * @return {Array}
 */
const filteredOptions = computed(() => {
  const list = [].concat(optionList.value)

  if (!props.filterable && !props.taggable) {
    return list
  }

  let options = search.value.length ? filter(list, search.value) : list

  if (props.taggable && search.value.length) {
    const createdOption = createOption(search.value)

    if (!optionExists(createdOption)) {
      if (props.displayNewOptionsLast) {
        options.unshift(createdOption)
      } else {
        options.push(createdOption)
      }
    }
  }

  return options
})

/**
 * Check if there aren't any options selected.
 */
const isValueEmpty = computed(() => selectedValue.value.length === 0)

/**
 * Determines if the clear button should be displayed.
 */
const showClearButton = computed(
  () => !props.multiple && props.clearable && !open.value && !isValueEmpty.value
)

const showToggleIcon = computed(
  () => (!props.simple || !(props.simple && props.disabled)) && props.toggleIcon
)

if (typeof props.modelValue !== 'undefined' && isTrackingValues.value) {
  setInternalValueFromOptions(props.modelValue)
}

const { typeAheadPointer, typeAheadUp, typeAheadDown, typeAheadSelect } =
  useTypeAheadPointer(
    filteredOptions,
    toRef(props, 'selectable'),
    toRef(props, 'autoscroll'),
    dropdownMenuRef,
    select
  )

/**
 * Make sure selected option is correct.
 */
watch(
  () => props.options,
  () => {
    if (props.modelValue && isTrackingValues.value) {
      setInternalValueFromOptions(props.modelValue)
    }
  }
)

/**
 * Make sure to update internal value if prop changes outside
 */
watch(
  () => props.modelValue,
  newVal => {
    if (isTrackingValues.value) {
      setInternalValueFromOptions(newVal)
    }
  }
)

/**
 * Always reset the value when the multiple prop changes.
 */
watch(() => props.multiple, clearSelection)

/**
 * Emits open/close events when the open data property changes
 */
watch(open, isOpen => {
  emit(isOpen ? 'open' : 'close')
})

/**
 * Anytime the search string changes, emit the 'search' event.
 */
watch(
  () => props.debounce,
  newDebounce => {
    if (debounceWatchHandle) {
      debounceWatchHandle()
    }

    if (searchEmitterHandler && searchEmitterHandler.cancel) {
      searchEmitterHandler.cancel()
    }

    searchEmitterHandler = debounceFn(
      newVal => emit('search', newVal, toggleLoading),
      newDebounce
    )

    debounceWatchHandle = watch(search, searchEmitterHandler)
  },
  { immediate: true }
)

/**
 * Sync the loading prop with the internal mutable loading value.
 */
watch(
  () => props.loading,
  newVal => {
    mutableLoading.value = newVal
  }
)

let referenceElResizeObserver = null
const referenceElWidth = ref(null)

function startReferenceElResizeObserver() {
  if (
    typeof window !== 'undefined' &&
    'ResizeObserver' in window &&
    reference.value
  ) {
    referenceElResizeObserver = new ResizeObserver(([entry]) => {
      referenceElWidth.value = entry.borderBoxSize.reduce(
        (acc, { inlineSize }) => acc + inlineSize,
        0
      )
    })

    referenceElResizeObserver.observe(reference.value)
  }
}

function clearReferenceElResizeObserver() {
  if (referenceElResizeObserver) {
    referenceElResizeObserver.disconnect()
    referenceElResizeObserver = undefined
    referenceElWidth.value = null
  }
}

onMounted(() => {
  startReferenceElResizeObserver()

  onClickOutside(dropdownMenuWrapperRef, hide, {
    ignore: [reference.value],
  })
})

onBeforeUnmount(clearReferenceElResizeObserver)

defineExpose({ focus, show, hide })
</script>

<style scoped>
.cs__search::-webkit-search-cancel-button {
  display: none !important;
}
.cs__search::-webkit-search-decoration,
.cs__search::-webkit-search-results-button,
.cs__search::-webkit-search-results-decoration,
.cs__search::-ms-clear {
  display: none !important;
}

.cs__search,
.cs__search:focus {
  appearance: none !important;
}
</style>
