<template>
  <section
    class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-5 shadow-sm dark:border-neutral-500/30 dark:bg-neutral-800/60"
  >
    <ITextDisplay
      class="mb-2"
      :text="$t('core::app.password_generator.heading')"
    />

    <div
      class="relative mb-8 flex h-16 w-full items-center justify-center rounded-lg border border-neutral-200 bg-white p-3 text-center dark:border-neutral-600 dark:bg-neutral-700"
    >
      <ITextDark class="mr-10 select-all">
        {{ password }}
      </ITextDark>

      <IButton icon="Refresh" basic small @click="generatePassword" />

      <IButtonCopy
        v-i-tooltip="$t('core::app.copy')"
        class="ml-1"
        :text="password"
        :success-message="$t('core::app.password_generator.copied')"
        basic
        small
      />
    </div>

    <div class="mb-10">
      <div class="flex justify-between">
        <IFormLabel v-t="'core::app.password_generator.strength'" />

        <ITextDark
          :text="$t('core::app.password_generator.' + strength.text)"
        />
      </div>

      <input
        v-model="strength.score"
        type="range"
        class="input-score pointer-events-none h-2 w-full appearance-none overflow-hidden rounded-lg focus:outline-none"
        min="0"
        max="100"
        :class="{
          'bg-danger-400': strength.text === 'weak',
          'bg-warning-300': strength.text === 'average',
          'bg-success-400':
            strength.text === 'strong' || strength.text === 'secure',
        }"
      />
    </div>

    <div class="mb-3">
      <div class="flex justify-between">
        <IFormLabel v-t="'core::app.password_generator.length'" />

        <ITextDark :text="settings.length" />
      </div>

      <input
        v-model="settings.length"
        type="range"
        class="range-slider h-2 w-full appearance-none overflow-hidden rounded-lg bg-primary-200 focus:outline-none"
        min="6"
        :max="settings.maxLength"
      />
    </div>

    <div class="mb-3">
      <div class="flex justify-between">
        <IFormLabel v-t="'core::app.password_generator.digits'" />

        <ITextDark :text="settings.digits" />
      </div>

      <input
        v-model="settings.digits"
        type="range"
        class="range-slider h-2 w-full appearance-none overflow-hidden rounded-lg bg-primary-200 focus:outline-none"
        min="0"
        :max="settings.maxDigits"
      />
    </div>

    <div class="mb-3">
      <div class="flex justify-between">
        <IFormLabel v-t="'core::app.password_generator.symbols'" />

        <ITextDark :text="settings.symbols" />
      </div>

      <input
        v-model="settings.symbols"
        type="range"
        class="range-slider h-2 w-full appearance-none overflow-hidden rounded-lg bg-primary-200 focus:outline-none"
        min="0"
        :max="settings.maxSymbols"
      />
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'

const password = ref('')

const settings = ref({
  maxLength: 64,
  maxDigits: 10,
  maxSymbols: 10,
  length: 12,
  digits: 4,
  symbols: 2,
  ambiguous: true,
})

watch(() => settings.value.length, generatePassword)
watch(() => settings.value.digits, generatePassword)
watch(() => settings.value.symbols, generatePassword)

const strength = computed(() => {
  let count = {
    excess: 0,
    upperCase: 0,
    numbers: 0,
    symbols: 0,
  }

  let weight = {
    excess: 3,
    upperCase: 4,
    numbers: 5,
    symbols: 5,
    combo: 0,
    flatLower: 0,
    flatNumber: 0,
  }

  let strength = {
    text: '',
    score: 0,
  }

  let baseScore = 30

  for (let i = 0; i < password.value.length; i++) {
    if (password.value.charAt(i).match(/[A-Z]/g)) {
      count.upperCase++
    }

    if (password.value.charAt(i).match(/[0-9]/g)) {
      count.numbers++
    }

    if (password.value.charAt(i).match(/(.*[!,@,#,$,%,^,&,*,?,_,~])/)) {
      count.symbols++
    }
  }

  count.excess = password.value.length - 6

  if (count.upperCase && count.numbers && count.symbols) {
    weight.combo = 25
  } else if (
    (count.upperCase && count.numbers) ||
    (count.upperCase && count.symbols) ||
    (count.numbers && count.symbols)
  ) {
    weight.combo = 15
  }

  if (password.value.match(/^[\sa-z]+$/)) {
    weight.flatLower = -30
  }

  if (password.value.match(/^[\s0-9]+$/)) {
    weight.flatNumber = -50
  }

  let score =
    baseScore +
    count.excess * weight.excess +
    count.upperCase * weight.upperCase +
    count.numbers * weight.numbers +
    count.symbols * weight.symbols +
    weight.combo +
    weight.flatLower +
    weight.flatNumber

  if (score < 30) {
    strength.text = 'weak'
    strength.score = 10

    return strength
  } else if (score >= 30 && score < 75) {
    strength.text = 'average'
    strength.score = 40

    return strength
  } else if (score >= 75 && score < 150) {
    strength.text = 'strong'
    strength.score = 75

    return strength
  } else {
    strength.text = 'secure'
    strength.score = 100

    return strength
  }
})

function generatePassword() {
  let lettersSetArray = [
    'a',
    'b',
    'c',
    'd',
    'e',
    'f',
    'g',
    'h',
    'i',
    'j',
    'k',
    'l',
    'm',
    'n',
    'o',
    'p',
    'q',
    'r',
    's',
    't',
    'u',
    'v',
    'w',
    'x',
    'y',
    'z',
  ]

  let symbolsSetArray = [
    '=',
    '+',
    '-',
    '^',
    '?',
    '!',
    '%',
    '&',
    '*',
    '$',
    '#',
    '^',
    '@',
    '|',
  ]

  let passwordArray = []
  let digitsPositionArray = []

  // first, fill the password array with letters, uppercase and lowecase
  for (let i = 0; i < settings.value.length; i++) {
    // get an array for all indexes of the password array
    digitsPositionArray.push(i)

    let upperCase = Math.round(Math.random() * 1)

    if (upperCase === 0) {
      passwordArray[i] =
        lettersSetArray[
          Math.floor(Math.random() * lettersSetArray.length)
        ].toUpperCase()
    } else {
      passwordArray[i] =
        lettersSetArray[Math.floor(Math.random() * lettersSetArray.length)]
    }
  }

  // Add digits to password
  for (let i = 0; i < settings.value.digits; i++) {
    let digit = Math.round(Math.random() * 9)

    let numberIndex =
      digitsPositionArray[
        Math.floor(Math.random() * digitsPositionArray.length)
      ]

    passwordArray[numberIndex] = digit

    /* remove position from digitsPositionArray so we make sure to the have the exact number of digits in our password
                    since without this step, numbers may override other numbers */

    let j = digitsPositionArray.indexOf(numberIndex)

    if (i != -1) {
      digitsPositionArray.splice(j, 1)
    }
  }

  // add special charachters "symbols"
  for (let i = 0; i < settings.value.symbols; i++) {
    let symbol =
      symbolsSetArray[Math.floor(Math.random() * symbolsSetArray.length)]

    let symbolIndex =
      digitsPositionArray[
        Math.floor(Math.random() * digitsPositionArray.length)
      ]

    passwordArray[symbolIndex] = symbol

    /* remove position from digitsPositionArray so we make sure to the have the exact number of digits in our password
                    since without this step, numbers may override other numbers */

    let j = digitsPositionArray.indexOf(symbolIndex)

    if (i != -1) {
      digitsPositionArray.splice(j, 1)
    }
  }
  password.value = passwordArray.join('')
}

onMounted(() => {
  generatePassword()
})
</script>

<style scoped>
input[type='range'].input-score::-webkit-slider-thumb {
  width: 15px;
  -webkit-appearance: none;
  appearance: none;
  height: 15px;
  cursor: ew-resize;
  background: rgba(var(--color-neutral-700), 50%);
  border-radius: 50%;
}

input[type='range'].range-slider::-webkit-slider-thumb {
  width: 15px;
  -webkit-appearance: none;
  appearance: none;
  height: 15px;
  cursor: ew-resize;
  background: rgb(var(--color-primary-500));
  box-shadow: -405px 0 0 400px rgb(var(--color-primary-400));
  border-radius: 50%;
}
</style>
