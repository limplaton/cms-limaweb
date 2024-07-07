<template>
  <div class="flex items-center justify-between">
    <div class="flex flex-1 justify-between sm:hidden">
      <IButton
        class="gap-x-4"
        icon="ArrowLeft"
        :disabled="!hasPreviousPage || loading"
        :text="$t('pagination.previous')"
        basic
        @click="$emit('goToPrevious')"
      />

      <IButton
        class="gap-x-4"
        :disabled="!hasNextPage || loading"
        basic
        @click="$emit('goToNext')"
      >
        {{ $t('pagination.next') }}
        <Icon icon="ArrowRight" />
      </IButton>
    </div>

    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <IText
        :text="
          $t('core::table.info', {
            from: from,
            to: to,
            total: total,
          })
        "
      />

      <nav class="flex items-center gap-x-2" aria-label="Pagination">
        <template v-if="renderLinks">
          <IButton
            aria-label="Previous page"
            class="min-w-9"
            icon="ChevronLeftSolid"
            :disabled="!hasPreviousPage || loading"
            basic
            @click="$emit('goToPrevious')"
          />

          <div class="flex items-center gap-x-2">
            <template v-for="(page, index) in links" :key="index">
              <div
                v-if="page === '...'"
                aria-hidden="true"
                class="w-[2.25rem] select-none text-center text-sm/6 font-semibold text-neutral-900 dark:text-white"
                v-text="'…'"
              />

              <IButton
                v-else
                aria-current="page"
                class="min-w-9"
                :aria-label="'Page ' + page"
                :text="page"
                :active="isCurrentPageCheck(page)"
                :disabled="loading"
                basic
                @click="
                  isCurrentPageCheck(page) ? undefined : $emit('goToPage', page)
                "
              />
            </template>
          </div>

          <IButton
            aria-label="Next page"
            class="min-w-9"
            icon="ChevronRightSolid"
            :disabled="!hasNextPage || loading"
            basic
            @click="$emit('goToNext')"
          />
        </template>
      </nav>
    </div>
  </div>
</template>

<script setup>
defineProps({
  loading: { type: Boolean, required: false },
  isCurrentPageCheck: { type: Function, required: true },
  renderLinks: { type: Boolean, required: true },
  links: { type: Array },

  hasNextPage: { type: Boolean, required: true },
  hasPreviousPage: { type: Boolean, required: true },
  from: { type: Number, required: true },
  to: { type: Number, required: true },
  total: { type: Number, required: true },
})

defineEmits(['goToPrevious', 'goToNext', 'goToPage'])
</script>
