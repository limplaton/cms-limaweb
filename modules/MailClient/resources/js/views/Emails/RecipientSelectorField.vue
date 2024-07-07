<template>
  <div class="flex items-center" :class="type">
    <div class="mr-1 min-w-14">
      <IFormLabel :label="label" :for="type" />
    </div>

    <div class="recipient grow">
      <ICustomSelect
        ref="selectRef"
        v-bind="$attrs"
        label="address"
        :input-id="type"
        :options="options"
        :filterable="false"
        :clearable="false"
        :taggable="true"
        :multiple="true"
        :filter-by="selectFilterBy"
        :option-key="
          option =>
            String(
              option.address + option.id + option.resourceName + option.name
            )
        "
        :create-option-provider="
          option => ({
            name: '',
            address: option,
          })
        "
        :option-comparator-provider="
          (a, b, defaultComparator) => {
            // For invalid addresses handler
            if (typeof a == 'string' && typeof b === 'object') {
              return a === b.address
            } else if (typeof b == 'string' && typeof a === 'object') {
              return b === a.address
            }

            return defaultComparator(a) === defaultComparator(b)
          }
        "
        :display-new-options-last="true"
        :placeholder="$t('mailclient::inbox.search_recipients')"
        :debounce="400"
        @option-selected="handleRecipientSelected"
        @search="searchRecipients"
      >
        <!-- Searched emails -->
        <template #option="option">
          <span class="flex justify-between">
            <span class="mr-2 truncate">
              {{ option.name }} {{ option.address }}
            </span>

            <Icon
              v-if="option.resourceName"
              class="size-5"
              :icon="
                option.resourceName === 'contacts' ? 'User' : 'OfficeBuilding'
              "
            />
          </span>
        </template>
        <!-- Selected -->
        <template
          #selected-option-container="{ option, disabled, deselect, multiple }"
        >
          <span
            :key="option.index"
            :class="[
              'mr-2 inline-flex rounded-md bg-neutral-100 px-2 dark:bg-neutral-500 dark:text-white',
              {
                'border border-danger-500': !validateAddress(option.address),
              },
            ]"
          >
            <span v-if="!option.name" v-text="option.address" />

            <span v-else v-i-tooltip="option.address" v-text="option.name" />

            <button
              v-if="multiple"
              type="button"
              class="ml-1 text-neutral-400 hover:text-neutral-600 dark:text-neutral-200 dark:hover:text-neutral-400"
              title="Remove recipient"
              aria-label="Remove recipient"
              :disabled="disabled"
              @click.prevent.stop="removeRecipient(deselect, option)"
            >
              <Icon icon="XSolid" class="size-4" />
            </button>
          </span>
        </template>
      </ICustomSelect>

      <IFormError :error="form.getError(type)" />
    </div>

    <slot name="after" />
  </div>
</template>

<script setup>
import { ref, shallowRef } from 'vue'
import validator from 'email-validator'

import { CancelToken } from '@/Core/services/HTTP'

defineOptions({ inheritAttrs: false })

defineProps({
  label: String,
  type: { type: String, required: true },
  form: { required: true },
})

const emit = defineEmits(['recipientRemoved', 'recipientSelected'])

const selectRef = ref(null)
const options = shallowRef([])

let cancelToken = null

// Allow non matching addresse to be shown
// based on the searched name (display_name)
function selectFilterBy(option, label, search) {
  return (
    (label || '').toLowerCase().indexOf(search.toLowerCase()) > -1 ||
    (option.name || '').toLowerCase().indexOf(search.toLowerCase()) > -1
  )
}

function handleRecipientSelected(records) {
  emit('recipientSelected', records)
}

function removeRecipient(deselect, option) {
  deselect(option)
  emit('recipientRemoved', option)
}

function focus() {
  selectRef.value.focus()
}

function searchRecipients(q, loading) {
  if (!q) {
    return
  }

  if (cancelToken) {
    cancelToken()
  }

  loading(true)

  Innoclapps.request('/search/email-address', getSearchRequestConfig(q))
    .then(({ data }) => {
      if (data) {
        let opts = []
        data.forEach(result => opts.push(...result.data))
        options.value = opts
      }
    })
    .finally(() => loading(false))
}

function getSearchRequestConfig(q) {
  return {
    params: {
      q: q,
    },
    cancelToken: new CancelToken(token => (cancelToken = token)),
  }
}

function validateAddress(address) {
  return validator.validate(address)
}

defineExpose({ focus })
</script>

<style scoped>
::v-deep(.cs__search::-webkit-search-cancel-button) {
  display: none !important;
}

::v-deep(.cs__search::-webkit-search-decoration),
::v-deep(.cs__search::-webkit-search-results-button),
::v-deep(.cs__search::-webkit-search-results-decoration),
::v-deep(.cs__search::-ms-clear) {
  display: none !important;
}

::v-deep(.cs__search),
::v-deep(.cs__search:focus) {
  appearance: none !important;
}
</style>
