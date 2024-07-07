<template>
  <div
    class="rounded-lg border border-neutral-200 px-5 py-4 shadow-sm dark:border-neutral-500/30"
  >
    <div class="flex flex-col sm:flex-row sm:items-center">
      <div class="flex items-center">
        <div class="mt-1.5 self-start">
          <span
            class="inline-flex size-6 items-center justify-center rounded-full p-1"
            :class="[
              account.requires_auth ? 'bg-danger-500' : 'bg-success-500',
            ]"
          >
            <Icon
              class="size-5 text-white"
              :icon="account.requires_auth ? 'XSolid' : 'Check'"
            />
          </span>
        </div>

        <div class="ml-1 sm:ml-2">
          <span
            class="text-sm font-medium text-neutral-700 dark:text-neutral-200"
            v-text="account.email"
          />

          <br />

          <ITextBlock
            v-if="account.requires_auth"
            class="text-warning-700 dark:text-warning-600"
          >
            {{ $t('core::oauth.requires_authentication') }}
          </ITextBlock>

          <slot name="after-name" />
        </div>
      </div>

      <div class="ml-0 mt-2 shrink-0 grow sm:ml-auto sm:mt-0 sm:grow-0">
        <div class="flex items-center sm:justify-center">
          <IButton
            v-show="showReconnectLink"
            :to="{ name: 'oauth-accounts', query: { reconnect: account.id } }"
            :text="$t('core::oauth.re_authenticate')"
            basic
          />

          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  account: { type: Object },
  withReconnectLink: { type: Boolean, default: true },
})

const route = useRoute()

const showReconnectLink = computed(
  () => route.name !== 'oauth-accounts' && props.withReconnectLink
)
</script>
