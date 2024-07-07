<template>
  <IFormGroup>
    <div class="-mb-1.5 flex">
      <IFormLabel
        class="mt-1.5 grow text-neutral-900 dark:text-neutral-100"
        :label="$t('core::fields.options')"
        required
      />

      <IButton
        v-show="!addingOptionsViaText"
        v-i-tooltip="$t('core::app.add_another')"
        icon="PlusSolid"
        basic
        small
        @click="newOptionAndFocus"
      />
    </div>

    <div
      v-if="options && options.length === 0 && !addingOptionsViaText"
      class="mt-2"
    >
      <IAlert>
        <IAlertBody>
          <I18nT
            scope="global"
            tag="div"
            class="flex items-center"
            :keypath="'core::fields.custom.create_option_icon'"
          >
            <template #icon>
              <Icon
                icon="PlusSolid"
                class="mx-1 size-5 cursor-pointer"
                @click="newOptionAndFocus"
              />
            </template>
          </I18nT>

          <ILink
            class="underline underline-offset-4"
            variant="info"
            :text="$t('core::fields.custom.or_add_options_via_text')"
            @click="initiateOptionsViaText"
          />
        </IAlertBody>
      </IAlert>
    </div>

    <div v-show="addingOptionsViaText">
      <IFormTextarea
        ref="optionsTextareaRef"
        v-model="textOptions"
        class="mt-3.5"
        :placeholder="$t('core::fields.custom.text_options_each_on_new_line')"
      />

      <div
        class="mt-1 flex items-center justify-end divide-x divide-neutral-200 text-right text-base/6 sm:text-sm/6"
      >
        <ILink
          :text="$t('core::fields.custom.convert_text_to_options')"
          @click="convertTextToOptions"
        />

        <ILink
          class="ml-2 pl-2"
          :text="$t('core::app.cancel')"
          @click="cancelAddOptionsViaText"
        />
      </div>
    </div>

    <SortableDraggable
      v-bind="$draggable.common"
      handle=".option-draggable-handle"
      :model-value="options"
      :item-key="(item, index) => index"
      @update:model-value="emitUpdateEvent($event)"
      @sort="setDisplayOrder"
    >
      <template #item="{ element, index }">
        <div class="relative mt-3">
          <div class="group -mx-6 px-6">
            <div
              class="option-draggable-handle absolute -left-5 top-2 z-20 hidden focus-within:block group-hover:block hover:block"
            >
              <Icon
                icon="Selector"
                class="size-5 cursor-move text-neutral-400"
              />
            </div>

            <div class="relative">
              <ITextBlock
                v-if="element.id"
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                :text="$t('core::app.id') + ': ' + element.id"
              />

              <IFormInput
                ref="optionNameRef"
                :model-value="element.name"
                :class="['!pr-20', { '!pl-14': element.id }]"
                @update:model-value="updateOption(index, 'name', $event)"
                @keydown.enter.prevent.stop="newOptionAndFocus"
              />

              <IPopover :offset="0">
                <IPopoverButton
                  icon="ColorSwatch"
                  class="absolute right-9 top-1.5 sm:top-1"
                  :style="{ color: element.swatch_color }"
                  basic
                  small
                />

                <IPopoverPanel class="w-56">
                  <IPopoverBody>
                    <IColorSwatch
                      :model-value="element.swatch_color"
                      :swatches="$scriptConfig('favourite_colors')"
                      @update:model-value="
                        updateOption(index, 'swatch_color', $event)
                      "
                    />
                  </IPopoverBody>
                </IPopoverPanel>
              </IPopover>

              <IButton
                icon="XSolid"
                class="absolute right-1.5 top-1.5 sm:top-1"
                basic
                small
                @click="removeOption(index)"
              />
            </div>
          </div>
        </div>
      </template>
    </SortableDraggable>

    <IFormError :error="form.getError('options')" />
  </IFormGroup>
</template>

<script setup>
import { nextTick, ref, toRaw } from 'vue'
import cloneDeep from 'lodash/cloneDeep'

const props = defineProps({
  form: { required: true, type: Object },
  options: { required: true },
})

const emit = defineEmits(['update:options'])

const optionNameRef = ref(null)
const optionsTextareaRef = ref(null)
const addingOptionsViaText = ref(false)
const textOptions = ref('')

function toRawOptions() {
  return cloneDeep(toRaw(props.options || []))
}

/** Set the display order of the options based at the current sorting */
function setDisplayOrder() {
  emitUpdateEvent(
    toRawOptions().map((option, index) => {
      option.display_order = index + 1

      return option
    })
  )
}

function initiateOptionsViaText() {
  addingOptionsViaText.value = true
  nextTick(optionsTextareaRef.value.focus)
}

function updateOption(index, key, value) {
  let options = toRawOptions()
  options[index][key] = value
  emitUpdateEvent(options)
}

function newOptionAndFocus() {
  newOption()

  // Focus the last option
  nextTick(() => {
    optionNameRef.value.focus()
  })
}

function newOption(name = null) {
  emitUpdateEvent([
    ...props.options,
    {
      name,
      display_order: props.options.length + 1,
      swatch_color: null,
    },
  ])
}

function cancelAddOptionsViaText() {
  addingOptionsViaText.value = false
}

function convertTextToOptions() {
  if (!textOptions.value) {
    return
  }

  let options = toRawOptions()

  textOptions.value
    .split('\n')
    .filter(option => option)
    .forEach(option =>
      options.push({
        name: option,
        display_order: options.length + 1,
        swatch_color: null,
      })
    )

  emitUpdateEvent(options)
  cancelAddOptionsViaText()
}

async function removeOption(index) {
  if (props.options[index].id) {
    await Innoclapps.confirm()
  }

  let options = toRawOptions()
  options.splice(index, 1)
  emitUpdateEvent(options)
}

function emitUpdateEvent(options) {
  emit('update:options', options)
}
</script>
