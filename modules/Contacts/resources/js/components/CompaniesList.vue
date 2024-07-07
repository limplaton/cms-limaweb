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
      v-for="(company, index) in localCompanies"
      v-show="index <= limit - 1 || showAll"
      :key="company.id"
      class="group flex items-center space-x-2 py-3 last:pb-0"
    >
      <div class="shrink-0">
        <CompanyImage />
      </div>

      <div class="min-w-0 flex-1 truncate">
        <ILink
          class="font-medium"
          :href="company.path"
          :text="company.display_name"
          basic
          @click="
            floatResource({
              resourceName: resourceName,
              resourceId: company.id,
              mode: floatMode,
            })
          "
        />

        <p v-show="company.domain">
          <ILink
            class="opacity-90"
            :href="'http://' + company.domain"
            :text="company.domain"
            basic
          />
        </p>
      </div>

      <div class="block shrink-0 md:hidden md:group-hover:block">
        <div class="flex items-center sm:space-x-1">
          <IButton
            v-if="company.authorizations.view"
            class="hidden sm:inline-flex"
            :text="$t('core::app.view_record')"
            :to="company.path"
            basic
            small
          />

          <IButton
            v-if="company.authorizations.view"
            class="inlnie-flex sm:hidden"
            icon="ChevronRightSolid"
            :to="company.path"
            small
            basic
          />

          <slot name="actions" :company="company" />
        </div>
      </div>
    </li>
  </ul>

  <IText v-show="!hasCompanies" :text="emptyText" />
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import castArray from 'lodash/castArray'
import orderBy from 'lodash/orderBy'

import { useFloatingResourceModal } from '@/Core/composables/useFloatingResourceModal'

import CompanyImage from './CompanyImage.vue'

const props = defineProps({
  companies: { type: [Object, Array], required: true },
  limit: { type: Number, default: 3 },
  emptyText: String,
  title: String,
  floatMode: { type: String, default: 'detail' },
})

const resourceName = Innoclapps.resourceName('companies')

const { t } = useI18n()
const { floatResource } = useFloatingResourceModal()

const showAll = ref(false)

const localCompanies = computed(() =>
  orderBy(castArray(props.companies), company => new Date(company.created_at), [
    'asc',
  ])
)

const total = computed(() => localCompanies.value.length)

const hasCompanies = computed(() => total.value > 0)

const cardTitle = computed(
  () => props.title || t('contacts::company.companies')
)
</script>
