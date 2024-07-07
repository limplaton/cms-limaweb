<template>
  <ICard class="mh-96">
    <ICardHeader>
      <ICardHeading :text="heading" />

      <ICardActions>
        <IButton
          v-show="fieldsVisible"
          variant="primary"
          :loading="saving"
          :disabled="requestInProgress"
          :text="$t('core::app.save')"
          small
          @click="submit(true)"
        />

        <IButton
          v-show="fieldsVisible"
          variant="secondary"
          :loading="resetting"
          :text="$t('core::app.reset')"
          :disabled="requestInProgress"
          :confirm-text="$t('core::app.confirm')"
          small
          confirmable
          @confirmed="reset"
        />

        <IButton
          v-show="fieldsVisible"
          variant="secondary"
          icon="ChevronUpSolid"
          :loading="fetching"
          :disabled="requestInProgress"
          small
          @click="toggle"
        />
      </ICardActions>
    </ICardHeader>

    <ICardBody class="cursor-pointer">
      <div class="lg:flex lg:items-center lg:justify-between">
        <IText :text="subHeading" @click="toggle" />

        <IButton
          v-show="!fieldsVisible"
          variant="secondary"
          class="mt-4 shrink-0 lg:ml-5 lg:mt-0"
          :text="$t('core::fields.manage')"
          small
          @click="toggle"
        />
      </div>

      <div v-show="fieldsVisible" class="mb-0 mt-3">
        <SearchInput v-model="search" />
      </div>
    </ICardBody>

    <ul v-show="fieldsVisible" class="max-h-96 overflow-y-auto">
      <SortableDraggable
        v-bind="$draggable.scrollable"
        handle=".field-draggable-handle"
        :list="filteredFields"
        :item-key="item => view + ' - ' + item.attribute"
        :group="view"
      >
        <template #item="{ element }">
          <li
            class="border-b border-neutral-200 px-4 py-4 first:border-t dark:border-neutral-500/30 sm:px-6"
            :class="{
              'bg-neutral-50 dark:bg-neutral-700/60': element.primary,
              'opacity-60': !element[visibilityKey],
            }"
          >
            <div class="flex items-center">
              <div class="grow">
                <div class="space-x-2">
                  <span
                    class="text-base/6 font-medium text-neutral-800 dark:text-white sm:text-sm/6"
                  >
                    {{ element.label }}
                  </span>

                  <IBadge
                    v-show="element.customField"
                    variant="info"
                    :text="$t('core::fields.custom.field')"
                  />

                  <IBadge
                    v-show="element.isUnique"
                    variant="success"
                    :text="$t('core::fields.field_is_unique')"
                  />

                  <IBadge
                    v-show="element.readonly"
                    variant="warning"
                    :text="$t('core::fields.is_readonly')"
                  />
                </div>

                <span
                  v-show="element.helpText"
                  class="text-xs text-neutral-800 dark:text-white"
                >
                  <br />{{ element.helpText }}
                </span>

                <span
                  v-if="element.primary"
                  class="text-xs font-medium text-info-600 dark:text-white"
                >
                  <br />{{ $t('core::fields.primary') }}
                </span>
              </div>

              <div class="inline-flex items-center space-x-1 self-start">
                <IButtonCopy
                  v-i-tooltip="$t('core::app.copy_api_key')"
                  :text="element.attribute"
                  small
                />

                <IButton
                  v-if="element.customField || element.optionsViaResource"
                  v-i-tooltip="$t('core::app.edit')"
                  icon="PencilAlt"
                  basic
                  small
                  @click="requestEdit(element)"
                />

                <IButton
                  v-if="element.customField"
                  v-i-tooltip="$t('core::app.delete')"
                  icon="Trash"
                  basic
                  small
                  @click="requestDelete(element.customField.id)"
                />

                <Icon
                  icon="Selector"
                  class="field-draggable-handle ml-1.5 size-4 cursor-move text-neutral-500"
                />
              </div>
            </div>

            <div v-if="!element.primary" class="mt-3">
              <IFormCheckboxField>
                <IFormCheckbox
                  v-model:checked="element[visibilityKey]"
                  :disabled="element.isRequired"
                />

                <IFormCheckboxLabel :text="$t('core::fields.visible')" />
              </IFormCheckboxField>

              <IFormCheckboxField
                v-if="element.canUnmarkUnique"
                class="gap-y-0"
              >
                <IFormCheckbox
                  :checked="element.isUnique && !element.uniqueUnmarked"
                  @change="element.uniqueUnmarked = !$event"
                />

                <IFormCheckboxLabel :text="$t('core::fields.mark_as_unique')" />

                <IFormCheckboxDescription v-if="element.uniqueUnmarked">
                  {{
                    !isCreateView
                      ? $t('core::fields.option_disabled_will_propagate', {
                          view_name: isUpdateView
                            ? $t('core::fields.settings.detail')
                            : $t('core::fields.settings.update'),
                        })
                      : ''
                  }}
                </IFormCheckboxDescription>
              </IFormCheckboxField>

              <IFormCheckboxField v-if="collapseOption">
                <IFormCheckbox v-model:checked="element.collapsed" />

                <IFormCheckboxLabel
                  :text="$t('core::fields.collapsed_by_default')"
                />
              </IFormCheckboxField>

              <IFormCheckboxField
                v-if="!element.isPrimary && !element.readonly"
                class="gap-y-0"
              >
                <IFormCheckbox
                  v-model:checked="element.isRequired"
                  @change="$event ? (element[visibilityKey] = true) : ''"
                />

                <IFormCheckboxLabel :text="$t('core::fields.is_required')" />

                <IFormCheckboxDescription v-if="element.isRequired">
                  {{
                    !isCreateView
                      ? $t('core::fields.option_enabled_will_propagate', {
                          view_name: isUpdateView
                            ? $t('core::fields.settings.detail')
                            : $t('core::fields.settings.update'),
                        })
                      : ''
                  }}
                </IFormCheckboxDescription>
              </IFormCheckboxField>
            </div>
          </li>
        </template>
      </SortableDraggable>
    </ul>
  </ICard>
</template>

<script setup>
import { computed, ref, unref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'

import { useApp } from '@/Core/composables/useApp'

const props = defineProps({
  group: { required: true, type: String },
  view: { required: true, type: String },
  heading: { type: String, required: true },
  subHeading: { type: String, required: true },
  collapseOption: { default: true, type: Boolean },
  lazy: { default: true, type: Boolean },
})

const emit = defineEmits(['deleteRequested', 'updateRequested', 'saved'])

const { t } = useI18n()
const route = useRoute()
const { scriptConfig, resetStoreState } = useApp()

const search = ref(null)
const fieldsLoaded = ref(false)
const fieldsVisible = ref(false)
const fields = ref([])
const saving = ref(false)
const resetting = ref(false)
const fetching = ref(false)

const requestInProgress = computed(
  () => unref(saving) || unref(resetting) || unref(fetching)
)

const filteredFields = computed({
  set(value) {
    fields.value = value.map(field => {
      if (field.isRequired) {
        field[unref(visibilityKey)] = true
      }

      if (!field.isUnique && field.canUnmarkUnique) {
        field.uniqueUnmarked = true
      }

      return field
    })
  },
  get() {
    if (search.value) {
      return fields.value.filter(field =>
        field.label.toLowerCase().includes(search.value.toLowerCase())
      )
    }

    return fields.value
  },
})

const isUpdateView = computed(
  () => props.view === scriptConfig('fields.views.update')
)

const isDetailView = computed(
  () => props.view === scriptConfig('fields.views.detail')
)

const isCreateView = computed(
  () => props.view === scriptConfig('fields.views.create')
)

const visibilityKey = computed(() => {
  if (isCreateView.value) {
    return 'showOnCreation'
  } else if (isUpdateView.value) {
    return 'showOnUpdate'
  } else if (isDetailView.value) {
    return 'showOnDetail'
  }

  return ''
})

function createRequestUri() {
  return '/fields/settings/' + props.group + '/' + props.view
}

function createRequestData() {
  let data = {}

  fields.value.forEach((field, index) => {
    let fieldObject = {
      order: index + 1,
      [unref(visibilityKey)]: field.isRequired
        ? true
        : field[unref(visibilityKey)],
      isRequired: field.isRequired,
    }

    if (field.canUnmarkUnique) {
      fieldObject.uniqueUnmarked = field.uniqueUnmarked || false
    }

    if (props.collapseOption) {
      fieldObject.collapsed = field.collapsed
    }

    data[field.attribute] = fieldObject
  })

  return data
}

function requestEdit(field) {
  emit('updateRequested', field)
}

function requestDelete(id) {
  emit('deleteRequested', id)
}

function submit(userAction) {
  saving.value = true

  Innoclapps.request()
    .post(createRequestUri(), createRequestData(), {
      params: {
        intent: props.view,
      },
    })
    .then(() => {
      resetStoreState()

      if (userAction) {
        Innoclapps.success(t('core::fields.configured'))
        emit('saved')
      }
    })
    .finally(() => (saving.value = false))
}

function toggle() {
  if (props.lazy && fieldsLoaded.value === false) {
    fetch()
  }

  fieldsVisible.value = !fieldsVisible.value
}

function reset() {
  resetting.value = true

  Innoclapps.request()
    .delete(createRequestUri() + '/reset', {
      params: {
        intent: props.view,
      },
    })
    .then(({ data }) => {
      filteredFields.value = data
      resetStoreState()
      Innoclapps.success(t('core::fields.reseted'))
    })
    .finally(() => (resetting.value = false))
}

async function fetch() {
  fetching.value = true

  let { data } = await Innoclapps.request(createRequestUri(), {
    params: {
      intent: props.view,
    },
  })

  fetching.value = false
  filteredFields.value = data
  fieldsLoaded.value = true
}

if (!props.lazy) {
  fetch()
}

if (route.query.view && route.query.view === props.view) {
  toggle()
}

watch(() => props.group, fetch)

defineExpose({ fetch, submit })
</script>
