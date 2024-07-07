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
        :text="$t('webforms::form.sections.message.message')"
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

        <IButton
          class="block md:opacity-0 md:group-hover:opacity-100"
          icon="Trash"
          basic
          small
          @click="requestSectionRemove"
        />
      </ICardActions>
    </ICardHeader>

    <ICardBody>
      <!-- eslint-disable -->
      <ITextBlock v-show="!editing" class="wysiwyg-text">
        <div v-html="section.message" />
      </ITextBlock>
       <!-- eslint-enable -->

      <template v-if="editing">
        <IFormGroup :label="$t('webforms::form.sections.message.message')">
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

const emit = defineEmits(['updateSectionRequested', 'removeSectionRequested'])

const editing = ref(false)
const message = ref(null)

function requestSectionSave() {
  emit('updateSectionRequested', {
    message: message.value,
  })

  editing.value = false
}

function requestSectionRemove() {
  emit('removeSectionRequested')
}

function setEditingMode() {
  message.value = props.section.message
  editing.value = true
}
</script>
