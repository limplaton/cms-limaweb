<template>
  <div class="flex items-center space-x-0.5">
    <IDropdown v-slot="{ hide }" :placement="placement">
      <IDropdownButton variant="secondary" v-bind="$attrs">
        <span
          class="sm:max-w-[13rem] sm:truncate"
          v-text="activeFilter?.name || $t('core::filters.filters')"
        />
      </IDropdownButton>

      <IDropdownMenu class="w-[19rem]">
        <div class="px-2.5 py-2">
          <ILink
            :text="$t('core::filters.new')"
            :class="[
              'mr-2.5',
              {
                'border-r border-neutral-200 pr-2.5 dark:border-white/10':
                  activeFilter,
              },
            ]"
            basic
            @click="initiateNewFilter(hide)"
          />

          <ILink
            v-show="activeFilter"
            class="border-r border-neutral-200 pr-2.5 dark:border-white/10"
            :text="$t('core::filters.edit')"
            basic
            @click="initiateEdit(hide)"
          />

          <ILink
            v-show="activeFilter"
            class="pl-2.5"
            :text="$t('core::filters.clear_applied')"
            basic
            @click="clearActive"
          />

          <SearchInput
            v-show="hasSavedFilters"
            v-model="search"
            class="mb-1 mt-3"
            :placeholder="$t('core::filters.search')"
            @keydown.stop="() => 'stop headless ui trying active click'"
          />
        </div>

        <IDropdownSeparator />

        <ITextDark
          v-show="hasSavedFilters && !searchResultIsEmpty"
          class="px-2.5 py-2 font-medium"
          :text="$t('core::filters.available')"
        />

        <IText
          v-show="!hasSavedFilters || searchResultIsEmpty"
          class="block px-3 py-2 text-center"
          :text="$t('core::filters.not_available')"
        />

        <div
          v-show="hasSavedFilters && !searchResultIsEmpty"
          class="inline-block max-h-80 w-full overflow-auto"
        >
          <FilterDropdownItem
            v-for="filter in filteredList"
            :key="filter.id"
            :identifier="identifier"
            :view="view"
            :filter-id="filter.id"
            :can-delete="
              filter.authorizations.delete &&
              !filter.is_readonly &&
              !filter.is_shared_from_another_user
            "
            :name="filter.name"
            @click="handleFilterSelected"
            @delete-requested="destroy(filter.id)"
          />
        </div>
      </IDropdownMenu>
    </IDropdown>

    <IButton
      v-show="editButtonIsVisible"
      v-i-tooltip="$t('core::filters.edit_filter')"
      icon="Pencil"
      basic
      @click="toggleFiltersBuilderVisibility"
    />

    <IButton
      v-show="addFilterButtonIsVisible"
      v-i-tooltip="$t('core::filters.add_filter')"
      icon="Filter"
      basic
      @click="toggleFiltersBuilderVisibility"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

import { emitGlobal } from '@/Core/composables/useGlobalEventListener'
import { useQueryBuilder } from '@/Core/composables/useQueryBuilder'

import { useFilterable } from '../../composables/useFilterable'

import FilterDropdownItem from './FiltersDropdownItem.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  placement: { default: 'bottom-end', type: String },
  identifier: { required: true, type: String },
  view: { required: true, type: String },
})

const emit = defineEmits(['apply'])

const { queryBuilderRules, hasRulesApplied, resetQueryBuilderRules } =
  useQueryBuilder(props.identifier, props.view)

const {
  filters,
  activeFilter,
  filtersBuilderVisible,
  toggleFiltersBuilderVisibility,
  deleteFilter,
} = useFilterable(props.identifier, props.view)

const search = ref(null)

const searchResultIsEmpty = computed(
  () => search.value && filteredList.value.length === 0
)

const editButtonIsVisible = computed(
  () => hasRulesApplied.value || activeFilter.value
)

const addFilterButtonIsVisible = computed(
  () => !hasRulesApplied.value && !activeFilter.value
)

const hasSavedFilters = computed(() => filters.value.length > 0)

const filteredList = computed(() => {
  if (!search.value) {
    return filters.value
  }

  return filters.value.filter(filter => {
    return filter.name.toLowerCase().includes(search.value.toLowerCase())
  })
})

function clearActive() {
  activeFilter.value = null

  emit('apply', queryBuilderRules.value)
}

function initiateEdit(hide) {
  toggleFiltersBuilderVisibility()
  hide()
}

function initiateNewFilter(hide) {
  if (activeFilter.value) {
    clearActive()
  }

  filtersBuilderVisible.value = true

  hide()
}

function handleFilterSelected(id) {
  emitGlobal(`${props.identifier}-${props.view}-filter-selected`, id)
}

async function destroy(id) {
  await Innoclapps.confirm()
  await deleteFilter(id)
  resetQueryBuilderRules()

  emit('apply', queryBuilderRules.value)
}
</script>
