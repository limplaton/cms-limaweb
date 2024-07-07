<template>
  <BaseCard :card="card" @retrieved="prepareComponent($event.card)">
    <div
      v-if="hasData"
      class="overflow-hidden border-y border-neutral-900/5 bg-white px-6 dark:border-white/10 dark:bg-neutral-900"
    >
      <ITable
        :id="tableId"
        ref="tableRef"
        max-height="450px"
        class="[--gutter:theme(spacing.6)]"
        sticky
        bleed
      >
        <ITableHead>
          <ITableRow
            class="[&>th]:sticky [&>th]:top-0 [&>th]:z-10 [&>th]:bg-opacity-75 [&>th]:backdrop-blur-sm [&>th]:backdrop-filter"
          >
            <ITableHeader
              v-for="field in fields"
              :key="'th-' + field.key"
              ref="thRefs"
              :class="[
                'bg-neutral-50 dark:bg-neutral-500/10',
                field.isStacked ? 'hidden' : '',
              ]"
            >
              {{ field.label }}
            </ITableHeader>
          </ITableRow>
        </ITableHead>

        <ITableBody>
          <ITableRow
            v-for="item in mutableCard.value"
            :key="item[mutableCard.primaryKey]"
          >
            <ITableCell
              v-for="field in fields"
              :key="'td-' + field.key"
              :class="{
                'font-medium': field.key === fields[0].key,
                hidden: field.isStacked,
              }"
            >
              <ILink
                v-if="card.floatingResource && field.key === fields[0].key"
                :text="item[field.key]"
                @click="
                  floatResource({
                    resourceName: card.floatingResource.resourceName,
                    resourceId: item.id,
                    mode: card.floatingResource.mode,
                  })
                "
              />

              <ILink
                v-else-if="field.key === fields[0].key && item.path"
                :to="item.path"
                :text="item[field.key]"
              />

              <span v-else>
                {{
                  field.formatter
                    ? field.formatter(item[field.key], field.key, item)
                    : item[field.key]
                }}
              </span>

              <template v-if="field.key === fields[0].key">
                <p
                  v-for="stackedField in stackedFields"
                  :key="'stacked-' + stackedField.key"
                  class="flex items-center font-normal"
                >
                  <span
                    class="mr-1 font-medium text-neutral-800 dark:text-neutral-100"
                  >
                    {{ stackedField.label }}:
                  </span>

                  <span class="text-neutral-700 dark:text-neutral-300">
                    {{
                      stackedField.formatter
                        ? stackedField.formatter(
                            item[stackedField.key],
                            stackedField.key,
                            item
                          )
                        : item[stackedField.key]
                    }}
                  </span>
                </p>
              </template>
            </ITableCell>
          </ITableRow>
        </ITableBody>
      </ITable>
    </div>

    <IText v-else class="pb-16 pt-12 text-center" :text="emptyText" />
  </BaseCard>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventListener } from '@vueuse/core'
import get from 'lodash/get'

import { useDates } from '@/Core/composables/useDates'
import { useFloatingResourceModal } from '@/Core/composables/useFloatingResourceModal'
import { useResponsiveTable } from '@/Core/composables/useResponsiveTable'
import { randomString } from '@/Core/utils'

import BaseCard from './BaseCard.vue'

const props = defineProps({
  card: Object,
  stackable: { type: Boolean, default: true },
})

const tableId = randomString()

const { isColumnVisible } = useResponsiveTable()
const { localizeIfDate } = useDates()
const { floatResource } = useFloatingResourceModal()

const { t } = useI18n()

const tableRef = ref(null)
const thRefs = ref([])

const mutableCard = ref({})

const stackedFields = computed(() =>
  mutableCard.value.fields.filter(field => field.isStacked)
)

const fields = computed(() => {
  return mutableCard.value.fields.map(field => {
    field.formatter = (value, key, item) => {
      return localizeIfDate(value, get(item, key))
    }

    return field
  })
})

const emptyText = computed(
  () => mutableCard.value.emptyText || t('core::app.not_enough_data')
)

const hasData = computed(() => mutableCard.value.value.length > 0)

function prepareComponent(card) {
  mutableCard.value = card
}

function stackColumns() {
  fields.value.forEach((field, idx) => {
    if (idx > 0 && thRefs.value[idx]) {
      mutableCard.value.fields[idx].isStacked = !isColumnVisible(
        // el
        thRefs.value[idx].$el,
        tableRef.value.$wrapperEl
      )
    }
  })
}

prepareComponent(props.card)

if (props.stackable) {
  useEventListener(window, 'resize', stackColumns)
}

onMounted(() => {
  props.stackable && nextTick(stackColumns)
})
</script>
