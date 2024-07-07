<!-- eslint-disable vue/no-v-html -->
<template>
  <div
    dusk="web-form"
    :class="['m-auto', { 'w-full': isEmbedded, 'max-w-2xl': !isEmbedded }]"
    :style="{ '--primary-contrast': getContrast(primaryColor) }"
  >
    <img v-if="logo" class="mx-auto mb-3 mt-10 h-12 w-auto" :src="logo" />

    <ICard
      :class="[
        'm-4 sm:m-8 sm:p-3',
        {
          'my-5': !isEmbedded,
          'rounded-none shadow-none': isEmbedded,
        },
      ]"
    >
      <ICardBody>
        <!--     <div
            id="testDiv"
            class="flex text-white space-x-2 rounded-md items-center"
          ></div> -->
        <div v-if="showSuccessMessage">
          <h4
            class="text-lg text-neutral-800"
            v-text="submitData.success_title"
          />

          <div
            v-show="submitData.success_message"
            class="wysiwyg-text"
            v-html="submitData.success_message"
          />
        </div>

        <IAlert v-else-if="!hasDefinedSections" variant="warning">
          <IAlertBody>
            {{ $t('webforms::form.no_sections') }}
          </IAlertBody>
        </IAlert>

        <form v-else novalidate="true" @submit.prevent="submit">
          <component
            :is="fieldComponents[section.type]"
            v-for="(section, index) in filteredSections"
            :key="index"
            :form="form"
            :section="section"
            @fill-form-attribute="form.fill($event.attribute, $event.value)"
            @set-form-attribute="form.set($event.attribute, $event.value)"
          />
        </form>
      </ICardBody>
    </ICard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { useForm } from '@/Core/composables/useForm'
import { useResourceFields } from '@/Core/composables/useResourceFields'
import {
  getContrast,
  hexToTailwindColor,
  lightenDarkenColor,
} from '@/Core/utils'

import FieldSection from '../components/DisplaySections/FieldSection.vue'
import FileSection from '../components/DisplaySections/FileSection.vue'
import IntroductionSection from '../components/DisplaySections/IntroductionSection.vue'
import MessageSection from '../components/DisplaySections/MessageSection.vue'
import SubmitButtonSection from '../components/DisplaySections/SubmitButtonSection.vue'

const props = defineProps({
  sections: { required: true, type: Array },
  styles: { required: true, type: Object },
  submitData: { required: true, type: Object },
  publicUrl: { required: true, type: String },
  logo: String,
})

const fieldComponents = {
  'field-section': FieldSection,
  'file-section': FileSection,
  'introduction-section': IntroductionSection,
  'message-section': MessageSection,
  'submit-button-section': SubmitButtonSection,
}

const showSuccessMessage = ref(false)

const route = useRoute()

const { findField } = useResourceFields(
  props.sections
    .filter(section => isFieldSection(section) && section.field)
    .map(section => section.field)
)

const { form } = useForm()

// Set the attributes to the form from file sections
props.sections.filter(isFileSection).forEach(section => {
  form.set(section.requestAttribute, section.multiple ? [] : null)
})

/**
 * Get the sections filter with the missing fields and mapped with their actual fields
 */
const filteredSections = computed(() => {
  return props.sections
    .map(section => {
      if (isFieldSection(section)) {
        section.field = findField(section.requestAttribute)
      }

      return section
    })
    .filter(section => {
      // We will check if any fields are not found (removed)
      if (!isFieldSection(section)) {
        return true
      }

      // Field removed?
      if (!section.field) {
        return false
      }

      return true
    })
})

/**
 * Indicates if the form has sections defined.
 */
const hasDefinedSections = computed(() => filteredSections.value.length > 0)

/**
 * Get the form body background form
 */
const bgColor = computed(() =>
  Object.hasOwn(route.query, 'bgColor')
    ? route.query.bgColor
    : props.styles.background_color
)

/**
 * Get the form primary form
 */
const primaryColor = computed(() =>
  Object.hasOwn(route.query, 'primaryColor')
    ? '#' + route.query.primaryColor
    : props.styles.primary_color
)

/**
 * Check whether the form is embedded in an iframe
 */
const isEmbedded = computed(() => route.query.e === 'true')

/**
 * Check whether the given section is field section
 */
function isFieldSection(section) {
  return section.type === 'field-section'
}

/**
 * Check whether the given section is file section
 */
function isFileSection(section) {
  return section.type === 'file-section'
}

/**
 * Submit the form
 */
function submit() {
  form.post(props.publicUrl).then(() => {
    if (props.submitData.action === 'redirect') {
      if (window.top) {
        window.top.location.href = props.submitData.success_redirect_url
      } else {
        window.location.href = props.submitData.success_redirect_url
      }
    } else {
      showSuccessMessage.value = true
    }
  })
}

// https://codepen.io/yonatankra/pen/POvYoG
// https://css-tricks.com/snippets/javascript/lighten-darken-color/
let originalStyles = new WeakMap() //  or a plain object storing ids

let nativeSupport = (function () {
  let bodyStyles = window.getComputedStyle(document.body)
  let fooBar = bodyStyles.getPropertyValue('--color-primary-50') // some variable from CSS

  return !!fooBar
})()

// Based on https://gist.github.com/tmanderson/98bbd05899995fd35443
function processCSSVariables(input) {
  let styles = Array.prototype.slice.call(
      document.querySelectorAll('style'),
      0
    ),
    defRE = /(\-\-[-\w]+)\:\s*(.*?)\;/g,
    overwrites = input || {}

  if (nativeSupport) {
    Object.keys(overwrites).forEach(function (property) {
      document.body.style.setProperty('--' + property, overwrites[property])
    })

    return
  }

  function refRE(name) {
    return new RegExp('var\\(\s*' + name + '\s*\\)', 'gmi')
  }

  styles.forEach(function (styleElement) {
    let content =
        originalStyles[styleElement] ||
        (originalStyles[styleElement] = styleElement.textContent),
      vars

    while ((vars = defRE.exec(content))) {
      content = content.replace(
        refRE(vars[1]),
        overwrites[vars[1].substr(2)] || vars[2]
      )
    }

    styleElement.textContent = content
  })
}

let c = i => {
  try {
    return hexToTailwindColor(lightenDarkenColor(primaryColor.value, i))
  } catch (err) {
    // When error is thrown because the color is too light or dark and in
    // this case, the hex won't be correct the hexToTailwindColor function will
    // throw an error for the hex, to be sure, just use the primary color
    return hexToTailwindColor(primaryColor.value)
  }
}

processCSSVariables({
  'color-primary-50': c(100),
  'color-primary-100': c(80),
  'color-primary-200': c(60),
  'color-primary-300': c(40),
  'color-primary-400': c(20),
  'color-primary-500': c(10),
  'color-primary-600': c(0),
  'color-primary-700': c(-10),
  'color-primary-800': c(-20),
  'color-primary-900': c(-30),
})

onMounted(() => {
  document.body.style.backgroundColor = bgColor.value
  document.getElementById('app').style.backgroundColor = bgColor.value
  // Colors test code
  /*let htmlTest = ''
      ;[50, 100, 200, 300, 400, 500, 600, 700, 800, 900].forEach(key => {
        htmlTest += `<div class="size-10 rounded mb-4 text-center pt-2 bg-primary-${key}">${key}</div>`
      })
      document.getElementById('testDiv').innerHTML = htmlTest*/
})
</script>
