<template>
  <div v-show="visible" class="mx-auto max-w-3xl">
    <ITextDisplay
      class="mb-3"
      :text="$t('documents::document.sections.signature')"
    />

    <RadioGroup v-model="form.requires_signature">
      <RadioGroupLabel class="sr-only">
        {{ $t('documents::document.sections.signature') }}
      </RadioGroupLabel>

      <div class="-space-y-px rounded-md bg-white dark:bg-neutral-900">
        <RadioGroupOption
          v-for="(setting, settingIdx) in signatureOptions"
          :key="setting.name"
          v-slot="{ checked, active }"
          as="template"
          :value="setting.value"
        >
          <div
            :class="[
              'relative flex cursor-pointer border p-4 focus:outline-none',
              document.status === 'accepted'
                ? 'pointer-events-none opacity-70'
                : '',
              settingIdx === 0 ? 'rounded-tl-md rounded-tr-md' : '',
              settingIdx === signatureOptions.length - 1
                ? 'rounded-bl-md rounded-br-md'
                : '',
              checked
                ? 'z-10 border-primary-700/10 bg-primary-50 text-primary-700 dark:border-primary-400/20 dark:bg-primary-400/10'
                : 'border-neutral-200 bg-white dark:border-neutral-500/30 dark:bg-neutral-800',
            ]"
          >
            <span
              aria-hidden="true"
              :class="[
                checked
                  ? 'border-transparent bg-primary-600'
                  : 'border-neutral-300 bg-white',
                active ? 'ring-2 ring-primary-500 ring-offset-2' : '',
                'mt-0.5 flex size-4 shrink-0 cursor-pointer items-center justify-center rounded-full border',
              ]"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-white" />
            </span>

            <span class="ml-3 flex flex-col">
              <RadioGroupLabel
                as="span"
                :class="[
                  checked
                    ? 'text-primary-800 dark:text-primary-400'
                    : 'text-neutral-900 dark:text-neutral-200',
                  'block text-base font-medium sm:text-sm',
                ]"
              >
                {{ setting.name }}
              </RadioGroupLabel>

              <RadioGroupDescription
                as="span"
                :class="[
                  checked
                    ? 'text-primary-700 dark:text-primary-300'
                    : 'text-neutral-500 dark:text-neutral-400',
                  'block text-base/6 sm:text-sm/6',
                ]"
              >
                {{ setting.description }}
              </RadioGroupDescription>
            </span>
          </div>
        </RadioGroupOption>
      </div>
    </RadioGroup>

    <div v-show="form.requires_signature">
      <ITextDisplay
        class="mb-3 mt-6"
        :text="$t('documents::document.signers.document_signers')"
      />

      <ITableOuter>
        <ITable>
          <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
            <ITableRow>
              <ITableHeader>
                {{ $t('documents::document.signers.signer_name') }}
              </ITableHeader>

              <ITableHeader>
                {{ $t('documents::document.signers.signer_email') }}
              </ITableHeader>

              <ITableHeader class="text-center">
                <span
                  v-t="'documents::document.signers.is_signed'"
                  class="hidden sm:block"
                />
              </ITableHeader>

              <ITableHeader />
            </ITableRow>
          </ITableHead>

          <ITableBody>
            <ITableRow v-if="form.signers.length === 0">
              <ITableCell colspan="4" class="align-middle">
                <IText class="ml-2">
                  {{ $t('documents::document.signers.no_signers') }}
                </IText>
              </ITableCell>
            </ITableRow>

            <template
              v-for="(signer, index) in form.signers"
              :key="'signer-' + index"
            >
              <ITableRow v-if="signer.signed_at">
                <ITableCell colspan="4" class="!p-0">
                  <div
                    class="bg-success-50 px-5 py-2 text-success-500 dark:bg-success-600/20"
                  >
                    <p>
                      <span class="font-medium">
                        {{ $t('documents::document.signature.signature') }}:
                      </span>

                      <span
                        class="font-signature text-[1.4rem] text-success-700"
                        v-text="signer.signature"
                      />
                    </p>

                    <div class="mt-1 inline-flex flex-row space-x-2">
                      <p>
                        <span class="font-medium">
                          {{ $t('documents::document.signature.signed_on') }}:
                        </span>

                        <span v-text="localizedDateTime(signer.signed_at)" />
                      </p>

                      <p>
                        <span class="font-medium">
                          {{ $t('documents::document.signature.sign_ip') }}:
                        </span>

                        <span v-text="signer.sign_ip" />
                      </p>
                    </div>
                  </div>
                </ITableCell>
              </ITableRow>

              <ITableRow>
                <ITableCell class="align-top">
                  <IFormInput
                    ref="signerNameInputRef"
                    v-model="signer.name"
                    :placeholder="
                      $t('documents::document.signers.enter_full_name')
                    "
                    :disabled="document.status === 'accepted'"
                  />

                  <IFormError
                    :error="form.getError('signers.' + index + '.name')"
                  />
                </ITableCell>

                <ITableCell class="align-top">
                  <IFormInput
                    v-model="signer.email"
                    type="email"
                    :disabled="document.status === 'accepted'"
                    :placeholder="$t('documents::document.signers.enter_email')"
                    @keyup.enter="insertEmptySigner"
                  />

                  <IFormError
                    :error="form.getError('signers.' + index + '.email')"
                  />
                </ITableCell>

                <ITableCell class="align-middle">
                  <span class="mt-1.5 inline-flex min-w-full justify-center">
                    <span
                      v-i-tooltip="
                        signer.signed_at
                          ? localizedDateTime(signer.signed_at)
                          : null
                      "
                      :class="[
                        'inline-block size-4 rounded-full',
                        signer.signed_at ? 'bg-success-400' : 'bg-danger-400',
                      ]"
                    />
                  </span>
                </ITableCell>

                <ITableCell class="align-middle">
                  <IButton
                    class="mt-0.5"
                    icon="XSolid"
                    basic
                    @click="removeSigner(index)"
                  />
                </ITableCell>
              </ITableRow>
            </template>
          </ITableBody>
        </ITable>
      </ITableOuter>

      <ILink
        v-show="!emptySignersExists && document.status !== 'accepted'"
        class="mt-3 inline-block font-medium"
        @click="insertEmptySigner"
      >
        &plus; {{ $t('documents::document.signers.add') }}
      </ILink>
    </div>
  </div>
</template>

<!-- eslint-disable vue/no-mutating-props -->
<script setup>
import { computed, nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  RadioGroup,
  RadioGroupDescription,
  RadioGroupLabel,
  RadioGroupOption,
} from '@headlessui/vue'

import { useDates } from '@/Core/composables/useDates'
import { isBlank } from '@/Core/utils'

import propsDefinition from './formSectionProps'

const props = defineProps(propsDefinition)

const { t } = useI18n()

const { localizedDateTime } = useDates()

const signerNameInputRef = ref(null)

const signatureOptions = [
  {
    name: t('documents::document.signature.no_signature'),
    description: t('documents::document.signature.no_signature_description'),
    value: false,
  },
  {
    name: t('documents::document.signature.e_signature'),
    description: t('documents::document.signature.e_signature_description'),
    value: true,
  },
]

const emptySignersExists = computed(
  () =>
    props.form.signers.filter(
      signer => isBlank(signer.name) || isBlank(signer.email)
    ).length > 0
)

/**
 * Insert empty signer
 */
function insertEmptySigner() {
  props.form.signers.push({
    name: '',
    email: '',
    send_email: true,
  })

  nextTick(() => {
    signerNameInputRef.value[props.form.signers.length - 1].focus()
  })
}

/**
 * Remove signer
 */
function removeSigner(index) {
  props.form.signers.splice(index, 1)
}
</script>
