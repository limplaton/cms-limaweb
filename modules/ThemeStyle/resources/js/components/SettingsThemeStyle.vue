<template>
  <ICardHeader>
    <ICardHeading :text="$t('themestyle::style.theme_style')" />
  </ICardHeader>

  <ICard
    as="form"
    :overlay="!componentReady"
    @submit.prevent="saveThemeStyle(colors)"
  >
    <ICardBody>
      <div
        v-for="(options, color) in colors"
        :key="color"
        :class="!canCustomize(color) ? 'hidden' : 'mb-14 last:mb-0'"
      >
        <div class="mb-2 items-center md:flex">
          <div class="mb-4 grow text-left sm:mb-0">
            <ITextDisplay>
              {{ color.charAt(0).toUpperCase() + color.slice(1) }}
            </ITextDisplay>
          </div>

          <div class="flex items-center justify-center space-x-3">
            <ILink
              v-if="!isEqual(defaultColors[color], colors[color])"
              :text="$t('core::app.reset')"
              :class="['mr-3', resetting ? 'pointer-events-none' : '']"
              @click="reset(color)"
            />

            <div class="relative">
              <label
                v-t="'themestyle::style.lightness_maximum'"
                for="lMax"
                class="inline-block bg-white px-1 text-xs font-medium text-neutral-900 dark:bg-neutral-900 dark:text-neutral-300 sm:absolute sm:-top-2.5 sm:left-2"
              />

              <input
                id="lMax"
                v-model="options.lMax"
                type="number"
                class="block w-full rounded-md border-0 py-1 text-base/6 text-neutral-900 shadow-sm ring-1 ring-inset ring-neutral-300 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-neutral-900 dark:text-neutral-200 dark:ring-neutral-700 dark:focus:ring-primary-500 sm:text-sm/6"
                @input="generatePalette(color)"
              />
            </div>

            <div class="relative">
              <label
                v-t="'themestyle::style.lightness_minimum'"
                for="lMin"
                class="inline-block bg-white px-1 text-xs font-medium text-neutral-900 dark:bg-neutral-900 dark:text-neutral-300 sm:absolute sm:-top-2.5 sm:left-2"
              />

              <input
                id="lMin"
                v-model="options.lMin"
                type="number"
                class="block w-full rounded-md border-0 py-1 text-base/6 text-neutral-900 shadow-sm ring-1 ring-inset ring-neutral-300 placeholder:text-neutral-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-neutral-900 dark:text-neutral-200 dark:ring-neutral-700 dark:focus:ring-primary-500 sm:text-sm/6"
                @input="generatePalette(color)"
              />
            </div>

            <IDropdown>
              <IDropdownButton
                class="self-end sm:self-auto"
                :text="options.valueStop"
                basic
              />

              <IDropdownMenu>
                <IDropdownItem
                  v-for="shade in shades"
                  :key="shade"
                  :text="shade"
                  :active="shade === options.valueStop"
                  condensed
                  @click="(options.valueStop = shade), generatePalette(color)"
                />
              </IDropdownMenu>
            </IDropdown>

            <input
              type="color"
              class="-ml-0.5 size-8 shrink-0 cursor-pointer appearance-none self-end border-0 bg-white p-0 outline-none sm:self-auto [&::-webkit-color-swatch]:rounded"
              :value="options.hex"
              @input="generatePalette(color, $event.target.value)"
            />
          </div>
        </div>

        <div
          class="flex flex-col justify-between space-y-1 overflow-hidden md:flex-row md:space-x-1 md:space-y-0"
        >
          <div
            v-for="(swatch, index) in options.swatches"
            :key="index"
            class="relative"
          >
            <div
              v-if="swatch.stop === options.valueStop"
              class="absolute left-1/2 top-10 hidden h-2 -translate-x-1/2 transform items-center justify-center md:flex"
            >
              <div
                class="-mt-2 size-2 rounded-full shadow"
                :style="{ backgroundColor: getContrast(swatch.hex) }"
              />
            </div>

            <Swatch
              v-model:hex="swatch.hex"
              :swatch="swatch"
              :color="color"
              @update:hex="updateUI()"
            />
          </div>
        </div>
      </div>
    </ICardBody>

    <ICardFooter class="text-right">
      <IButton
        type="submit"
        variant="primary"
        :disabled="form.busy"
        :text="$t('core::app.save')"
      />
    </ICardFooter>
  </ICard>
</template>

<script setup>
import { reactive, ref, toRaw } from 'vue'
import { whenever } from '@vueuse/core'
import each from 'lodash/each'
import isEqual from 'lodash/isEqual'

import { useSettings } from '@/Core/composables/useSettings'
import { debounce, getContrast, hexToTailwindColor } from '@/Core/utils'

import defaultVarsString from '../../../../../resources/css/variables.css?inline'
import {
  DEFAULT_PALETTE_CONFIG,
  DEFAULT_STOP,
  SHADES as shades,
} from '../constants'
import { createSwatches } from '../createSwatches'
import { rgbToHex } from '../helpers'

import Swatch from './SettingsThemeStyleSwatch.vue'

const colorTypes = []
const defaultVars = {}
const excludedShades = [0, 950, 1000]

parseDefaultVars()

const defaultColors = {}
const colors = reactive({})
const resetting = ref(false)

colorTypes.forEach(color => {
  colors[color] = getDefaultConfig(color)
  defaultColors[color] = getDefaultConfig(color)
})

const {
  form,
  submit,
  isReady: componentReady,
  originalSettings,
} = useSettings()

whenever(componentReady, () => {
  if (originalSettings.value.theme_style) {
    setColors(originalSettings.value.theme_style)
  }
})

function reset(color) {
  resetting.value = true
  let nonReactiveColors = structuredClone(toRaw(colors))
  delete nonReactiveColors[color]

  saveThemeStyle(nonReactiveColors, () => {
    colors[color] = getDefaultConfig(color)
    updateUI()
    resetting.value = false
  })
}

function setColors(colorsJsonString) {
  let themeStyle = JSON.parse(colorsJsonString)

  each(themeStyle, (options, color) => {
    colors[color] = options
  })
}

function saveThemeStyle(newColors, callback = null) {
  let nonReactiveColors = structuredClone(toRaw(newColors))

  each(nonReactiveColors, (options, color) => {
    if (isEqual(defaultColors[color], options)) {
      delete nonReactiveColors[color]
    }
  })

  let now = new Date()

  form.theme_style = JSON.stringify(nonReactiveColors)

  form.theme_style_modified_at = Date.UTC(
    now.getUTCFullYear(),
    now.getUTCMonth(),
    now.getUTCDate(),
    now.getUTCHours(),
    now.getUTCMinutes(),
    now.getUTCSeconds(),
    now.getUTCMilliseconds()
  )

  submit(callback)
}

function updateUI() {
  each(colors, (options, color) => {
    options.swatches.forEach(swatch => {
      let property = `--color-${color}-${swatch.stop}`

      document.documentElement.style.setProperty(
        property,
        hexToTailwindColor(swatch.hex),
        'important'
      )
    })
  })
}

function getDefaultConfig(color) {
  return {
    valueStop: DEFAULT_STOP,
    lMax: DEFAULT_PALETTE_CONFIG.lMax,
    lMin: DEFAULT_PALETTE_CONFIG.lMin,
    hex: defaultVars[color][DEFAULT_STOP].hex,
    swatches: shades
      .filter(shade => !excludedShades.includes(shade))
      .map(shade => ({
        stop: shade,
        hex: defaultVars[color][shade].hex,
      })),
  }
}

const generatePalette = debounce(function (color, hex) {
  let colorConfig = colors[color]

  if (!hex) {
    hex = colorConfig.hex
  }

  colors[color].hex = hex

  const paletteConfig = Object.assign({}, DEFAULT_PALETTE_CONFIG, {
    value: hex.substring(1),
    valueStop: colorConfig.valueStop,
    lMax: colorConfig.lMax,
    lMin: colorConfig.lMin,
  })

  const palette = createSwatches(paletteConfig).filter(
    swatch => !excludedShades.includes(swatch.stop)
  )

  colors[color].swatches = palette.map(p => ({
    hex: p.hex,
    stop: p.stop,
  }))

  updateUI()
}, 300)

function parseDefaultVars() {
  const regex = /--color-([a-z]+)-([0-9]+):\s[0-9]+,\s[0-9]+,\s[0-9]+/gm
  let m

  while ((m = regex.exec(defaultVarsString)) !== null) {
    // This is necessary to avoid infinite loops with zero-width matches
    if (m.index === regex.lastIndex) {
      regex.lastIndex++
    }

    let rgbVar = m[0]
    let colorType = m[1].trim()
    let shade = m[2]

    if (colorTypes.indexOf(colorType) === -1) {
      colorTypes.push(m[1])
    }

    if (!Object.hasOwn(defaultVars, colorType)) {
      defaultVars[colorType] = {}
    }

    if (!Object.hasOwn(defaultVars[colorType], shade)) {
      defaultVars[colorType][shade] = []
    }

    const rgbArray = rgbVar
      .replaceAll(',', '')
      .split(' ')
      .map(c => c.trim())

    defaultVars[colorType][shade] = {
      rgb: rgbVar,
      hex: rgbToHex(rgbArray[1], rgbArray[2], rgbArray[3]),
    }
  }
}

function canCustomize(color) {
  // The "neutral" color should not be customized, as it's too deep
  // into the design, using for dark colors layout backgrounds etc...
  // and it's hard to dynamically generate proper colors via the generator.
  // the original checks are for previous versions in case the user already customized this color
  // this will ensure the user can reset or change the original, after reset, the customization option
  // won't be available.
  return (
    color != 'neutral' ||
    (originalSettings.value.theme_style &&
      originalSettings.value.theme_style.indexOf('neutral') !== -1)
  )
}
</script>
