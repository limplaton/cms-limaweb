<template>
  <IModal
    size="xl"
    :visible="filtersBuilderVisible"
    :title="
      !activeFilter
        ? $t('core::filters.create')
        : $t('core::filters.edit_filter')
    "
    :ok-text="
      saving ? $t('core::filters.save_and_apply') : $t('core::filters.apply')
    "
    :ok-disabled="!filtersCanBeApplied || form.busy"
    :cancel-text="$t('core::app.hide')"
    :hide-footer="isReadonly"
    static-backdrop
    @ok="submit"
    @shown="handleModalShown"
    @hidden="filtersBuilderVisible = false"
  >
    <IAlert v-if="isReadonly" class="mb-3">
      <IAlertBody>
        {{ $t('core::filters.is_readonly') }}
      </IAlertBody>
    </IAlert>

    <template #title="{ title }">
      <span class="flex items-center">
        <Icon
          v-if="isCurrentFilterDefault"
          class="-mt-px mr-1 size-5"
          icon="Star"
        />

        <span v-text="title" />
      </span>
    </template>

    <div class="mb-3 flex items-center justify-end space-x-2">
      <ILink
        v-if="!isReadonly && hasRulesApplied"
        :text="$t('core::filters.clear_rules')"
        basic
        @click="resetRulesAndRefresh"
      />

      <IDropdownMinimal
        v-if="isReadonly || (activeFilter && canDelete)"
        small
        horizontal
      >
        <template v-if="isReadonly">
          <IDropdownItem v-if="!isCurrentFilterDefault" @click="markAsDefault">
            <IDropdownItemLabel>
              {{ $t('core::filters.mark_as_default') }}
            </IDropdownItemLabel>

            <IDropdownItemDescription>
              {{ $t('core::filters.mark_as_default_for_current_account_only') }}
            </IDropdownItemDescription>
          </IDropdownItem>

          <IDropdownItem
            v-else
            :text="$t('core::filters.unmark_as_default')"
            @click="unmarkAsDefault"
          />
        </template>

        <span
          v-if="activeFilter && canDelete"
          v-i-tooltip="
            isSystemDefault
              ? $t('core::filters.system_default_delete_info')
              : null
          "
        >
          <IDropdownItem
            :disabled="isSystemDefault || isReadonly"
            :text="$t('core::app.delete')"
            :confirm-text="$t('core::app.confirm')"
            confirmable
            @confirmed="destroy"
          />
        </span>
      </IDropdownMinimal>
    </div>

    <QueryBuilder
      v-bind="$attrs"
      :read-only="isReadonly"
      :identifier="identifier"
      :view="view"
    />

    <div v-show="saving && !isReadonly" class="mt-5">
      <div class="grid grid-cols-2 gap-4">
        <IFormGroup
          label-for="filter_name"
          :label="$t('core::filters.name')"
          required
        >
          <IFormInput
            id="filter_name"
            v-model="form.name"
            name="name"
            type="text"
            :placeholder="$t('core::filters.name')"
          />

          <IFormError :error="form.getError('name')" />
        </IFormGroup>

        <IFormGroup :label="$t('core::filters.share.with')" required>
          <IDropdown adaptive-width>
            <IDropdownButton variant="secondary" class="w-full">
              {{
                form.is_shared
                  ? $t('core::filters.share.everyone')
                  : $t('core::filters.share.private')
              }}
            </IDropdownButton>

            <IDropdownMenu>
              <IDropdownItem
                :active="form.is_shared === false"
                @click="form.is_shared = false"
              >
                <IDropdownItemLabel :text="$t('core::filters.share.private')" />

                <IDropdownItemDescription
                  :text="$t('core::filters.share.private_info')"
                />
              </IDropdownItem>

              <IDropdownItem
                v-if="!hasRulesAppliedWithAuthorization"
                :active="form.is_shared === true"
                @click="form.is_shared = true"
              >
                <IDropdownItemLabel
                  :text="$t('core::filters.share.everyone')"
                />

                <IDropdownItemDescription
                  :text="$t('core::filters.share.everyone_info')"
                />
              </IDropdownItem>
            </IDropdownMenu>
          </IDropdown>

          <IFormError :error="form.getError('is_shared')" />
        </IFormGroup>
      </div>

      <IAlert v-if="hasRulesAppliedWithAuthorization" class="mb-3" show>
        <IAlertBody>
          {{
            $t('core::filters.cannot_be_shared', {
              rules: rulesLabelsWithAuthorization,
            })
          }}
        </IAlertBody>
      </IAlert>
    </div>

    <IFormGroup v-show="saving && !isReadonly">
      <IFormCheckboxField>
        <IFormCheckbox
          v-model:checked="defaulting"
          @update:checked="showDefaultFilterInfo = $event"
        />

        <IFormCheckboxLabel :text="$t('core::filters.mark_as_default')" />

        <IFormCheckboxDescription
          v-show="showDefaultFilterInfo"
          :text="$t('core::filters.mark_as_default_for_current_account_only')"
        />
      </IFormCheckboxField>
    </IFormGroup>

    <template #modal-cancel="{ cancel, text }">
      <div class="flex space-x-5">
        <IFormSwitchField v-show="!editing && !activeFilter && hasRulesApplied">
          <IFormSwitchLabel :text="$t('core::filters.save_as_new')" />

          <IFormSwitch v-model="saving" />
        </IFormSwitchField>

        <IButton
          class="hidden sm:inline-flex"
          :disabled="form.busy"
          :text="text"
          basic
          @click="cancel"
        />
      </div>
    </template>
  </IModal>
</template>

<script setup>
import { computed, nextTick, ref, toRaw, watch } from 'vue'
import { useStore } from 'vuex'

import QueryBuilder from '@/Core/components/QueryBuilder'
import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'
import { useGlobalEventListener } from '@/Core/composables/useGlobalEventListener'
import { useQueryBuilder } from '@/Core/composables/useQueryBuilder'

import { useFilterable } from '../../composables/useFilterable'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  view: { required: true, type: String },
  identifier: { required: true, type: String },
  initialApply: { default: true, type: Boolean },
  activeFilterId: Number,
})

const emit = defineEmits(['apply'])

const store = useStore()
const { currentUser } = useApp()

const { form } = useForm()
const editing = ref(false)
const saving = ref(false)
const defaulting = ref(false)
const showDefaultFilterInfo = ref(false)

const {
  availableRules: builderAvailableRules,
  queryBuilderRules,
  rulesAreValid,
  hasRulesApplied,
  findRule,
  resetQueryBuilderRules,
  totalValidRules,
} = useQueryBuilder(props.identifier, props.view)

const {
  availableRules,
  filtersBuilderVisible,
  activeFilter,
  currentUserDefaultFilter,
  findFilter,
  deleteFilter,
} = useFilterable(props.identifier, props.view)

// We must set the available rules in the builder.
builderAvailableRules.value = availableRules.value

/**
 * Get the rule labels with authorization
 */
const rulesLabelsWithAuthorization = computed(() => {
  return rulesWithAuthorization.value
    .filter(r => Boolean(findRule(r.id)))
    .map(r => r.label)
    .join(', ')
})

/**
 * Get all the rules in the query builder that are having authorization
 */
const rulesWithAuthorization = computed(() =>
  availableRules.value.filter(rule => rule.has_authorization)
)

/**
 * Indicates if there are rules in the query builder that are with authorization
 */
const hasRulesAppliedWithAuthorization = computed(() =>
  rulesWithAuthorization.value.some(rule => findRule(rule.id))
)

/**
 * Indicates if the curent active filter is sytem default
 */
const isSystemDefault = computed(
  () => activeFilter.value && activeFilter.value.is_system_default
)

/**
 * Indicates if currently applied filter is default
 */
const isCurrentFilterDefault = computed(
  () =>
    activeFilter.value &&
    currentUserDefaultFilter.value &&
    activeFilter.value.id == currentUserDefaultFilter.value.id
)

/**
 * Indicates if the filters can be applied
 */
const filtersCanBeApplied = computed(
  () => !(!rulesAreValid.value || totalValidRules.value === 0)
)

/**
 * Indicates if the active filter is read only
 */
const isReadonly = computed(() => {
  if (!activeFilter.value) return false

  return (
    activeFilter.value.is_readonly ||
    activeFilter.value.is_shared_from_another_user
  )
})

const canUpdate = computed(() => activeFilter.value.authorizations.update)
const canDelete = computed(() => activeFilter.value.authorizations.delete)
const PUSH_FILTER = (...params) => store.commit('filters/PUSH', ...params)
const UPDATE_FILTER = (...params) => store.commit('filters/UPDATE', ...params)

const UNMARK_AS_DEFAULT = (...params) =>
  store.commit('filters/UNMARK_AS_DEFAULT', ...params)

/**
 * Reset the filters state
 */
function resetState() {
  form.clear().set({
    name: null,
    rules: [],
    is_shared: false,
  })
  saving.value = false
  editing.value = false
  defaulting.value = false
}

/**
 * Handle the modal shown event
 */
function handleModalShown() {
  resetState()

  if (activeFilter.value && canUpdate.value) {
    setUpdateData()
  }
}

/**
 * Store new filter
 */
async function create() {
  form.fill('identifier', props.identifier)
  let filter = await form.post(`/filters`)

  PUSH_FILTER({
    filter: filter,
    identifier: props.identifier,
  })

  setActive(filter.id)

  return filter
}

/**
 * Update the currently active filter
 */
async function update() {
  const filter = await form.put(`/filters/${activeFilter.value.id}`)
  handleUpdatedLifeCycle(filter)

  return filter
}

/**
 * Submit the filters form
 */
async function submit() {
  if (!saving.value) {
    apply()
    filtersBuilderVisible.value = false

    return
  }

  form.fill('rules', queryBuilderRules.value)

  await (editing.value ? update() : create())
  await nextTick()

  if (!editing.value) {
    defaulting.value && markAsDefault()
  } else {
    if (isCurrentFilterDefault.value && !defaulting.value) {
      unmarkAsDefault()
    } else if (!isCurrentFilterDefault.value && defaulting.value) {
      markAsDefault()
    }
  }

  apply()
  filtersBuilderVisible.value = false
  showDefaultFilterInfo.value = false
}

/**
 * Set update data so the submit method can use
 */
function setUpdateData() {
  form.is_shared = activeFilter.value.is_shared
  form.name = activeFilter.value.name
  defaulting.value = isCurrentFilterDefault.value
  editing.value = true
  saving.value = true
}

/**
 * Delete filter
 */
async function destroy() {
  await deleteFilter(activeFilter.value.id)

  filtersBuilderVisible.value = false
  resetRulesAndRefresh()
}

/**
 * Make the active filter as default
 */
function markAsDefault() {
  Innoclapps.request()
    .put(`filters/${activeFilter.value.id}/${props.view}/default`)
    .then(({ data }) => {
      // We need to remove the previous default filter data
      if (
        currentUserDefaultFilter.value &&
        currentUserDefaultFilter.value.id != data.id
      ) {
        UNMARK_AS_DEFAULT({
          id: currentUserDefaultFilter.value.id,
          identifier: props.identifier,
          view: props.view,
          userId: currentUser.value.id,
        })
      }

      handleUpdatedLifeCycle(data)
    })
}

/**
 * Unmark the active filter as default
 *
 * @return {Void}
 */
function unmarkAsDefault() {
  Innoclapps.request()
    .delete(`/filters/${activeFilter.value.id}/${props.view}/default`)
    .then(({ data }) => {
      handleUpdatedLifeCycle(data)
    })
}

/**
 * Update the filter in Vuex
 */
function handleUpdatedLifeCycle(filter) {
  UPDATE_FILTER({
    identifier: props.identifier,
    filter: filter,
  })
}

/**
 * Apply filters event
 */
function apply() {
  emit('apply', queryBuilderRules.value)
}

/**
 * Set filter as active
 */
function setActive(id, emit = true) {
  let filter = findFilter(id)
  queryBuilderRules.value = structuredClone(toRaw(filter.rules) || {})
  activeFilter.value = filter.id

  if (emit) {
    apply()
  }
}

/**
 * Reset the applied query builder rules and perform refresh
 */
function resetRulesAndRefresh() {
  resetQueryBuilderRules()

  apply()
}

watch(hasRulesAppliedWithAuthorization, (newVal, oldVal) => {
  if (!oldVal && newVal) {
    form.is_shared = false
  }
})

useGlobalEventListener(
  `${props.identifier}-${props.view}-filter-selected`,
  setActive
)

if (props.activeFilterId) {
  setActive(props.activeFilterId, props.initialApply)
} else if (activeFilter.value) {
  // We will check if there is an active filter already applied,
  // helps keeping the previous filter applied when navigating away then coming back to the view,
  // additionally, we will perform perform a check if the rules are valid in case the active filter was edited
  // but not saved and the user navigated away from the view and came back but the filter was already invalid.
  if (!rulesAreValid.value) {
    queryBuilderRules.value = activeFilter.value.rules
  }
  props.initialApply && apply()
} else if (currentUserDefaultFilter.value) {
  setActive(currentUserDefaultFilter.value.id, props.initialApply)
} else {
  props.initialApply && apply()
}
</script>
