<!-- eslint-disable vue/no-v-html -->
<template>
  <div class="h-full max-h-full w-full bg-white">
    <component :is="navigationComponent" v-bind="navigationComponentAttributes">
      <template #actions>
        <IButton
          class="shrink-0"
          icon="DocumentDownload"
          :href="publicUrl + '/pdf?output=download'"
        >
          <span
            v-t="'documents::document.download_pdf'"
            class="hidden sm:block"
          />
        </IButton>

        <IButton
          v-if="status !== 'accepted'"
          class="shrink-0"
          variant="success"
          :icon="!requiresSignature ? 'Check' : ''"
          :disabled="documentBeingAccepted"
          :loading="documentBeingAccepted"
          @click="accept"
        >
          <svg
            v-if="requiresSignature"
            xmlns="http://www.w3.org/2000/svg"
            data-slot="icon"
            viewBox="0 0 640 512"
            class="fill-white"
          >
            <path
              d="M192 128c0-17.7 14.3-32 32-32s32 14.3 32 32v7.8c0 27.7-2.4 55.3-7.1 82.5l-84.4 25.3c-40.6 12.2-68.4 49.6-68.4 92v71.9c0 40 32.5 72.5 72.5 72.5c26 0 50-13.9 62.9-36.5l13.9-24.3c26.8-47 46.5-97.7 58.4-150.5l94.4-28.3-12.5 37.5c-3.3 9.8-1.6 20.5 4.4 28.8s15.7 13.3 26 13.3H544c17.7 0 32-14.3 32-32s-14.3-32-32-32H460.4l18-53.9c3.8-11.3 .9-23.8-7.4-32.4s-20.7-11.8-32.2-8.4L316.4 198.1c2.4-20.7 3.6-41.4 3.6-62.3V128c0-53-43-96-96-96s-96 43-96 96v32c0 17.7 14.3 32 32 32s32-14.3 32-32V128zm-9.2 177l49-14.7c-10.4 33.8-24.5 66.4-42.1 97.2l-13.9 24.3c-1.5 2.6-4.3 4.3-7.4 4.3c-4.7 0-8.5-3.8-8.5-8.5V335.6c0-14.1 9.3-26.6 22.8-30.7zM24 368c-13.3 0-24 10.7-24 24s10.7 24 24 24H64.3c-.2-2.8-.3-5.6-.3-8.5V368H24zm592 48c13.3 0 24-10.7 24-24s-10.7-24-24-24H305.9c-6.7 16.3-14.2 32.3-22.3 48H616z"
            />
          </svg>

          {{
            requiresSignature
              ? $t('documents::document.sign')
              : $t('documents::document.accept')
          }}
        </IButton>
      </template>

      <!-- Content -->
      <template #content>
        <IAlert v-if="$route.query.accepted" class="mb-6" variant="success">
          <IAlertBody>
            <div v-html="acceptThankYouMessage" />
          </IAlertBody>
        </IAlert>

        <IAlert v-else-if="$route.query.signed" class="mb-6" variant="success">
          <IAlertBody>
            <div v-html="signThankYouMessage" />
          </IAlertBody>
        </IAlert>

        <div
          class="contentbuilder prose prose-sm prose-neutral relative max-w-none"
          v-html="content"
        />
      </template>
    </component>

    <IModal
      id="signModal"
      v-model:visible="signModalVisible"
      size="sm"
      ok-text="Sign"
      :ok-disabled="form.busy"
      :title="
        signerVerified
          ? $t('documents::document.sign')
          : $t('documents::document.signers.confirm_email')
      "
      :hide-footer="!signerVerified"
      static-backdrop
      form
      @submit="sign"
    >
      <div v-show="!signerVerified" class="relative pb-3">
        <IFormInput
          v-model="form.email"
          type="email"
          class="pr-28"
          :placeholder="$t('documents::document.signers.enter_email')"
          @keyup.enter="confirmEmailAddress"
        />

        <div class="shrink-0">
          <IButton
            v-show="form.email"
            class="absolute right-1.5 top-1.5 sm:top-1"
            :loading="signerVerificationInProgress"
            :disabled="signerVerificationInProgress"
            :text="$t('core::app.confirm')"
            small
            basic
            @click="confirmEmailAddress"
          />
        </div>
      </div>

      <IFormGroup
        v-show="signerVerified"
        label-for="emailAddress"
        :label="$t('documents::document.signers.signer_email')"
      >
        <IFormInput
          id="emailAddress"
          v-model="form.email"
          type="email"
          :disabled="true"
          :placeholder="$t('documents::document.signers.enter_email')"
        />

        <IFormError :error="form.getError('email')" />
      </IFormGroup>

      <IAlert v-if="signerVerificationFailed" variant="warning" class="mt-3">
        <IAlertBody>
          {{ $t('documents::document.signature.verification_failed') }}
        </IAlertBody>
      </IAlert>

      <IFormGroup
        v-show="signerVerified"
        label-for="signature"
        :label="$t('documents::document.signature.accept_name')"
      >
        <IFormInput
          id="signature"
          v-model="form.signature"
          class="font-signature text-[1.3rem] sm:text-[1.2rem]"
        />

        <IFormError :error="form.getError('signature')" />
      </IFormGroup>

      <IText
        v-show="signerVerified"
        class="mb-3"
        :text="
          signatureBoundText
            .replace('{{ signerName }}', signerName)
            .replace('{{signerName}}', signatureBoundText)
        "
      />
    </IModal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

import { useForm } from '@/Core/composables/useForm'

import PublicViewNavbar from './DocumentsPublicViewNavbar.vue'
import PublicViewSidebar from './DocumentsPublicViewSidebar.vue'

const props = defineProps([
  'requiresSignature',
  'title',
  'content',
  'uuid',
  'status',
  'publicUrl',
  'signatureBoundText',
  'acceptThankYouMessage',
  'signThankYouMessage',
  'navigationBackgroundColor',
  'brandName',
  'logo',
  'viewType',
  'navigation',
  'navigationHeadingTagName',
])

// TODO, show brand success message and send email as well?
const signerName = ref(null)
const signerVerified = ref(false)
const signerVerificationFailed = ref(false)
const signerVerificationInProgress = ref(false)
const documentBeingAccepted = ref(false)
const signModalVisible = ref(false)

const { form } = useForm({
  email: null,
  signature: null,
})

const navigationComponent = computed(() =>
  props.viewType === 'nav-top' ? PublicViewNavbar : PublicViewSidebar
)

const navigationComponentAttributes = computed(() => {
  let attributes = {
    publicUrl: props.publicUrl,
    brandName: props.brandName,
    logo: props.logo,
    backgroundColor: props.navigationBackgroundColor,
  }

  if (props.viewType !== 'nav-top') {
    attributes.navigation = props.navigation
    attributes.navigationHeadingTagName = props.navigationHeadingTagName
    attributes.full = props.viewType === 'nav-left-full-width'
  }

  return attributes
})

/**
 * Accept the document without a signature
 */
function accept() {
  if (props.requiresSignature) {
    signModalVisible.value = true
  } else {
    documentBeingAccepted.value = true

    Innoclapps.request()
      .post(`/d/${props.uuid}/accept`)
      .then(() => (window.location.href = `${props.publicUrl}?accepted=1`))
      .finally(() => (documentBeingAccepted.value = false))
  }
}

/**
 * Make an HTTP sign request
 */
function sign() {
  form
    .post(`/d/${props.uuid}/sign`)
    .then(() => (window.location.href = `${props.publicUrl}?signed=1`))
}

/**
 * Confirm the signer email address
 */
function confirmEmailAddress() {
  signerVerificationFailed.value = false
  signerVerificationInProgress.value = true

  Innoclapps.request()
    .post(`/d/${props.uuid}/validate`, {
      email: form.email,
    })
    .then(({ data }) => {
      if (typeof data === 'object') {
        signerName.value = data.name
        signerVerified.value = true
      } else {
        signerVerificationFailed.value = true
      }
    })
    .finally(() => (signerVerificationInProgress.value = false))
}
</script>

<style lang="scss">
@import './resources/scss/contenteditable.scss?inline';
</style>
