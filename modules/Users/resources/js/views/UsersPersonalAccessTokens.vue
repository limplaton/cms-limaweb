<template>
  <MainLayout>
    <div class="mx-auto max-w-5xl">
      <ICardHeader>
        <ICardHeading :text="$t('core::api.personal_access_tokens')" />

        <ICardActions>
          <IButton
            v-show="totalTokens > 0"
            variant="primary"
            icon="PlusSolid"
            :text="$t('core::api.create_token')"
            @click="showCreateTokenForm"
          />
        </ICardActions>
      </ICardHeader>

      <ICard :overlay="tokensAreBeingLoaded">
        <div v-if="totalTokens > 0" class="px-6">
          <ITable class="[--gutter:theme(spacing.6)]" bleed>
            <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
              <ITableRow>
                <ITableHeader>
                  {{ $t('core::api.token_name') }}
                </ITableHeader>

                <ITableHeader>
                  {{ $t('core::api.token_last_used') }}
                </ITableHeader>

                <ITableHeader>
                  {{ $t('core::app.created_at') }}
                </ITableHeader>

                <ITableHeader width="8%" />
              </ITableRow>
            </ITableHead>

            <ITableBody>
              <ITableRow v-for="token in tokens" :key="token.id">
                <ITableCell class="font-medium">
                  {{ token.name }}
                </ITableCell>

                <ITableCell>
                  {{
                    token.last_used_at
                      ? localizedDateTime(token.last_used_at)
                      : 'N/A'
                  }}
                </ITableCell>

                <ITableCell>
                  {{ localizedDateTime(token.created_at) }}
                </ITableCell>

                <ITableCell class="text-right">
                  <div class="-my-1.5">
                    <IButton
                      v-i-tooltip="$t('core::api.revoke_token')"
                      icon="Trash"
                      small
                      basic
                      @click="
                        $confirm({
                          message: $t('core::api.token_delete_warning'),
                        }).then(() => revoke(token))
                      "
                    />
                  </div>
                </ITableCell>
              </ITableRow>
            </ITableBody>
          </ITable>
        </div>

        <ICardBody v-else-if="!tokensAreBeingLoaded">
          <IEmptyState
            :title="$t('core::api.no_tokens')"
            :button-text="$t('core::api.create_token')"
            :description="$t('core::api.empty_state.description')"
            @click="showCreateTokenForm"
          />
        </ICardBody>
      </ICard>
    </div>

    <IModal
      v-model:visible="showCreateTokenModal"
      size="sm"
      :ok-disabled="form.busy"
      :ok-text="$t('core::app.create')"
      :title="$t('core::api.create_token')"
      form
      @shown="() => $refs.createTokenNameRef.focus()"
      @submit="create"
    >
      <IFormGroup label-for="name" :label="$t('core::api.token_name')" required>
        <IFormInput
          id="name"
          ref="createTokenNameRef"
          v-model="form.name"
          name="name"
        />

        <IFormError :error="form.getError('name')" />
      </IFormGroup>
    </IModal>

    <IModal
      v-model:visible="showAccessTokenModal"
      size="sm"
      hide-header
      static-backdrop
    >
      <ITextDisplay class="mb-2">
        {{ $t('core::api.personal_access_token') }}
      </ITextDisplay>

      <IText class="text-warning-700 dark:text-warning-600">
        {{ $t('core::api.after_token_created_info') }}
      </IText>

      <IText
        class="mt-5 select-all break-all rounded-lg border border-neutral-300 p-1.5 text-center dark:border-neutral-400"
      >
        {{ plainTextToken }}
      </IText>

      <template #modal-footer="{ cancel }">
        <div class="flex items-center justify-between space-x-2">
          <IButton
            variant="secondary"
            :text="$t('core::app.hide')"
            @click="cancel"
          />

          <IButtonCopy
            v-i-tooltip="$t('core::app.copy')"
            class="mt-1.5"
            :text="plainTextToken"
          />
        </div>
      </template>
    </IModal>
  </MainLayout>
</template>

<script setup>
import { computed, onBeforeMount, ref, shallowReactive } from 'vue'
import { useRouter } from 'vue-router'

import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { useForm } from '@/Core/composables/useForm'

const plainTextToken = ref(null)
const showAccessTokenModal = ref(false)
const showCreateTokenModal = ref(false)
const tokensAreBeingLoaded = ref(false)
const tokens = shallowReactive([])

const { form } = useForm({ name: '' }, { resetOnSuccess: true })
const { localizedDateTime } = useDates()
const { currentUser } = useApp()
const router = useRouter()

const totalTokens = computed(() => tokens.length)

function prepareComponent() {
  getTokens()
}

function getTokens() {
  tokensAreBeingLoaded.value = true

  Innoclapps.request('/personal-access-tokens')
    .then(response => {
      tokens.push(...response.data)
    })
    .finally(() => (tokensAreBeingLoaded.value = false))
}

function showCreateTokenForm() {
  showCreateTokenModal.value = true
}

function create() {
  plainTextToken.value = null

  form.post('/personal-access-tokens').then(response => {
    tokens.push(response.accessToken)
    showAccessToken(response.plainTextToken)
  })
}

function showAccessToken(token) {
  showCreateTokenModal.value = false
  plainTextToken.value = token
  showAccessTokenModal.value = true
}

async function revoke(token) {
  await Innoclapps.request().delete(`/personal-access-tokens/${token.id}`)

  tokens.splice(
    tokens.findIndex(t => t.id === token.id),
    1
  )
}

onBeforeMount(() => {
  if (!currentUser.value.access_api) {
    router.push({ path: '/403' })
  } else {
    prepareComponent()
  }
})
</script>
