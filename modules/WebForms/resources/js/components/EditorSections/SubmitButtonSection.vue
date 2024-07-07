<template>
  <ICard
    class="group"
    :class="[
      '[&>[data-slot=header]]:py-3',
      {
        'border border-primary-400 dark:border-primary-500': editing,
        'border transition duration-75 hover:border-primary-400 dark:hover:border-primary-500':
          !editing,
      },
    ]"
  >
    <ICardHeader>
      <ICardHeading
        class="text-sm/6"
        :text="$t('webforms::form.sections.submit.button')"
      />

      <ICardActions>
        <IButton
          icon="PencilAlt"
          :class="[
            !editing
              ? 'opacity-100 md:opacity-0 md:group-hover:opacity-100'
              : 'opacity-0',
          ]"
          basic
          small
          @click="setEditingMode"
        />
      </ICardActions>
    </ICardHeader>

    <ICardBody>
      <ITextDark v-show="!editing" :text="section.text" />

      <div v-if="editing">
        <IFormGroup
          label-for="text"
          :label="$t('webforms::form.sections.submit.button_text')"
        >
          <IFormInput id="text" v-model="text" />
        </IFormGroup>

        <IFormGroup v-show="$scriptConfig('reCaptcha.configured')">
          <IFormCheckboxField>
            <IFormCheckbox v-model:checked="spamProtected" />

            <IFormCheckboxLabel
              :text="$t('webforms::form.sections.submit.spam_protected')"
            />
          </IFormCheckboxField>
        </IFormGroup>

        <IFormGroup>
          <IFormCheckboxField>
            <IFormCheckbox v-model:checked="privacyPolicyAcceptIsRequired" />

            <IFormCheckboxLabel
              :text="
                $t('webforms::form.sections.submit.require_privacy_policy')
              "
            />
          </IFormCheckboxField>
        </IFormGroup>

        <IFormGroup
          v-show="privacyPolicyAcceptIsRequired"
          label-for="privacy_policy_url"
          :label="$t('webforms::form.sections.submit.privacy_policy_url')"
        >
          <IFormInput id="privacy_policy_url" v-model="privacyPolicyUrl" />
        </IFormGroup>

        <div class="space-x-2 text-right">
          <IButton
            variant="secondary"
            :text="$t('core::app.cancel')"
            @click="editing = false"
          />

          <IButton
            variant="primary"
            :text="$t('core::app.save')"
            @click="requestSectionSave"
          />
        </div>
      </div>
    </ICardBody>
  </ICard>
</template>

<script setup>
import { ref } from 'vue'

import { useApp } from '@/Core/composables/useApp'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  index: { type: Number },
  form: { type: Object, required: true },
  section: { required: true, type: Object },
})

const emit = defineEmits(['updateSectionRequested'])

const { scriptConfig } = useApp()

const editing = ref(false)
const text = ref(null)
const spamProtected = ref(false)
const privacyPolicyAcceptIsRequired = ref(false)
const privacyPolicyUrl = ref(scriptConfig('privacyPolicyUrl'))

function requestSectionSave() {
  emit('updateSectionRequested', {
    text: text.value,
    spamProtected: spamProtected.value,
    privacyPolicyAcceptIsRequired: privacyPolicyAcceptIsRequired.value,
    privacyPolicyUrl: privacyPolicyUrl.value,
  })

  editing.value = false
}

function setEditingMode() {
  text.value = props.section.text
  spamProtected.value = props.section.spamProtected

  privacyPolicyAcceptIsRequired.value =
    props.section.privacyPolicyAcceptIsRequired
  privacyPolicyUrl.value = props.section.privacyPolicyUrl

  editing.value = true
}
</script>
