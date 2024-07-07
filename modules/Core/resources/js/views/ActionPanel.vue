<template>
  <div class="pt-8">
    <div class="mx-auto max-w-3xl">
      <div class="bg-white shadow dark:bg-neutral-900 sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <div class="sm:flex sm:items-start sm:justify-between">
            <div>
              <ITextDisplay :text="title" />

              <IText class="mt-2 max-w-xl" :text="description" />
            </div>

            <div
              class="mt-5 sm:ml-6 sm:mt-1 sm:flex sm:shrink-0 sm:items-center"
            >
              <form @submit.prevent="execute">
                <IButton
                  type="submit"
                  variant="primary"
                  :loading="executing"
                  :disabled="executing"
                  :text="buttonText"
                />
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

import { useApp } from '../composables/useApp'

const props = defineProps({
  buttonText: { default: 'Update', type: String },
  title: String,
  description: String,
  redirectTo: String,
  method: { type: String, default: 'post' },
  action: { type: String, required: true },
})

const { appUrl } = useApp()

const executing = ref(false)

function execute() {
  executing.value = true

  Innoclapps.request({
    method: props.method,
    url: props.action,
  })
    .then(() => {
      if (props.redirectTo) {
        window.location.href = props.redirectTo
      } else {
        window.location.href = appUrl
      }
    })
    .finally(() => (executing.value = false))
}
</script>
