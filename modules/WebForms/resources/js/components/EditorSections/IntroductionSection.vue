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
        :text="$t('webforms::form.sections.introduction.introduction')"
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
      <div v-show="!editing">
        <ITextDisplay :text="section.title" />

        <ITextBlock class="wysiwyg-text">
          <!-- eslint-disable -->
          <div v-html="section.message" />
          <!-- eslint-enable -->
        </ITextBlock>
      </div>

      <template v-if="editing">
        <IFormGroup
          label-for="title"
          :label="$t('webforms::form.sections.introduction.title')"
        >
          <IFormInput id="title" v-model="title" />
        </IFormGroup>

        <IFormGroup :label="$t('webforms::form.sections.introduction.message')">
          <Editor v-model="message" :with-image="false" minimal />
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
      </template>
    </ICardBody>
  </ICard>
</template>

<script setup>
import { ref } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  index: { type: Number },
  form: { type: Object, required: true },
  section: { required: true, type: Object },
})

const emit = defineEmits(['updateSectionRequested'])

const editing = ref(false)
const title = ref(null)
const message = ref(null)

function requestSectionSave() {
  emit('updateSectionRequested', {
    title: title.value,
    message: message.value,
  })

  editing.value = false
}

function setEditingMode() {
  title.value = props.section.title
  message.value = props.section.message
  editing.value = true
}
</script>
