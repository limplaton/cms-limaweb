<template>
  <div class="mb-2 flex items-center justify-between">
    <ITextDisplay>
      {{ cardTitle }}

      <IText
        v-show="total > 0"
        class="ml-0.5 inline"
        :text="'(' + total + ')'"
      />
    </ITextDisplay>

    <div class="flex items-center space-x-2">
      <ILink
        v-if="total > limit"
        class="shrink-0 text-sm sm:text-xs"
        :text="!showAll ? $t('core::app.show_all') : $t('core::app.show_less')"
        @click="showAll = !showAll"
      />

      <ITextSmall
        v-if="total > limit"
        v-show="!showAll"
        v-t="{ path: 'core::app.has_more', args: { count: total - limit } }"
      />

      <slot name="top-actions" />
    </div>
  </div>

  <ul class="divide-y divide-neutral-200 dark:divide-neutral-500/30">
    <li
      v-for="(deal, index) in iterable"
      v-show="index <= limit - 1 || showAll"
      :key="deal.id"
      class="group flex items-center space-x-2 py-3 last:pb-0"
    >
      <IBadge
        class="shrink-0"
        :variant="
          deal.status === 'won'
            ? 'success'
            : deal.status === 'lost'
              ? 'danger'
              : 'neutral'
        "
        :text="$t('deals::deal.status.' + deal.status)"
      />

      <div class="min-w-0 flex-1">
        <div class="max-w-44 truncate sm:max-w-full">
          <ILink
            class="font-medium"
            :href="deal.path"
            :text="deal.display_name"
            basic
            @click="
              floatResource({
                resourceName: resourceName,
                resourceId: deal.id,
                mode: floatMode,
              })
            "
          />

          <div class="sm:flex sm:space-x-2">
            <IText :text="deal.stage.name" />

            <span
              v-if="Object.hasOwn(deal, 'amount')"
              class="-mt-0.5 hidden sm:inline"
            >
              &ndash;
            </span>

            <IText
              v-if="Object.hasOwn(deal, 'amount')"
              :text="formatMoney(deal.amount)"
            />
          </div>
        </div>
      </div>

      <div class="block shrink-0 md:hidden md:group-hover:block">
        <div class="flex items-center sm:space-x-1">
          <IButton
            v-if="deal.authorizations.view"
            class="hidden sm:inline-flex"
            :text="$t('core::app.view_record')"
            :to="deal.path"
            basic
            small
          />

          <IButton
            v-if="deal.authorizations.view"
            class="inlnie-flex sm:hidden"
            icon="ChevronRightSolid"
            :to="deal.path"
            small
            basic
          />

          <slot name="actions" :deal="deal" />
        </div>
      </div>
    </li>
  </ul>

  <IText v-show="!hasDeals" :text="emptyText" />
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import castArray from 'lodash/castArray'
import orderBy from 'lodash/orderBy'

import { useAccounting } from '@/Core/composables/useAccounting'
import { useFloatingResourceModal } from '@/Core/composables/useFloatingResourceModal'

const props = defineProps({
  deals: { type: [Object, Array], required: true },
  limit: { type: Number, default: 3 },
  emptyText: String,
  title: String,
  floatMode: { type: String, default: 'detail' },
})

const resourceName = Innoclapps.resourceName('deals')

const { t } = useI18n()
const { floatResource } = useFloatingResourceModal()
const { formatMoney } = useAccounting()

const showAll = ref(false)

const localDeals = computed(() => castArray(props.deals))

const wonSorted = computed(() =>
  orderBy(
    localDeals.value.filter(deal => deal.status === 'won'),
    deal => new Date(deal.won_date),
    'desc'
  )
)

const lostSorted = computed(() =>
  orderBy(
    localDeals.value.filter(deal => deal.status === 'lost'),
    deal => new Date(deal.lost_date),
    'desc'
  )
)

const openSorted = computed(() =>
  orderBy(
    localDeals.value.filter(deal => deal.status === 'open'),
    deal => new Date(deal.created_at)
  )
)

const iterable = computed(() => [
  ...openSorted.value,
  ...lostSorted.value,
  ...wonSorted.value,
])

const total = computed(() => localDeals.value.length)

const hasDeals = computed(() => total.value > 0)

const cardTitle = computed(() => props.title || t('deals::deal.deals'))
</script>
