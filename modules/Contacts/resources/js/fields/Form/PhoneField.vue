<template>
  <BaseFormField
    v-slot="{ fieldId }"
    :resource-name="resourceName"
    :field="field"
    :value="modelValue"
    :is-floating="isFloating"
  >
    <FormFieldGroup
      class="multiple"
      :field="field"
      :label="field.label"
      :field-id="fieldId"
      :validation-errors="validationErrors"
    >
      <IFormGroup
        v-for="(phone, index) in phones"
        :key="index"
        class="relative"
      >
        <IFormInput
          v-model="phones[index].number"
          class="pl-32 pr-12 sm:pl-28 sm:pr-11"
          :name="field.attribute + '.' + index + '.number'"
          :debounce="checksForDuplicates"
          @input="searchDuplicateRecord(index, phones[index].number)"
        />

        <div class="absolute left-1 top-1 z-20 flex">
          <IDropdown adaptive-width>
            <IDropdownButton
              class="w-28 sm:w-24"
              :icon="phone.type == 'mobile' ? 'DeviceMobile' : 'Phone'"
              basic
              small
            >
              <span class="truncate">
                {{
                  phones[index].type
                    ? field.types[phones[index].type]
                    : $t('contacts::fields.phone.types.type')
                }}
              </span>
            </IDropdownButton>

            <IDropdownMenu>
              <IDropdownItem
                v-for="(typeLabel, id) in field.types"
                :key="id"
                :text="typeLabel"
                @click="phones[index].type = id"
              />
            </IDropdownMenu>
          </IDropdown>
        </div>

        <div class="absolute right-1.5 top-1.5 z-20 flex sm:top-1">
          <IButton icon="XSolid" basic small @click="removePhone(index)" />
        </div>

        <IFormError
          v-if="
            validationErrors &&
            validationErrors.array &&
            validationErrors.array[field.attribute + '.' + index + '.number']
          "
          :error="
            validationErrors.array[field.attribute + '.' + index + '.number']
          "
        />

        <IAlert
          v-if="duplicates[index]"
          v-slot="{ variant }"
          class="mt-1"
          dismissible
          @dismissed="duplicates[index] = null"
        >
          <IAlertBody>
            <I18nT
              scope="global"
              :keypath="field.checkDuplicatesWith.lang_keypath"
            >
              <template #display_name>
                <span
                  class="font-medium"
                  v-text="duplicates[index].display_name"
                />
              </template>
            </I18nT>
          </IAlertBody>

          <IAlertActions>
            <IButton
              target="_blank"
              rel="noopener noreferrer"
              icon="ExternalLink"
              :href="duplicates[index].path"
              :variant="variant"
              :text="$t('core::app.view_record')"
              ghost
            />
          </IAlertActions>
        </IAlert>
      </IFormGroup>

      <div class="text-right">
        <ILink :text="$t('contacts::fields.phone.add')" @click="newPhone" />
      </div>
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import omit from 'lodash/omit'

import { defineFormDataObjectUniqueId } from '@/Core/composables/useForm'
import FormFieldGroup from '@/Core/fields/FormFieldGroup.vue'

const props = defineProps({
  field: { type: Object, required: true },
  modelValue: { type: Array, default: () => [] },
  resourceName: String,
  resourceId: [String, Number],
  validationErrors: Object,
  isFloating: Boolean,
})

const emit = defineEmits(['update:modelValue', 'setInitialValue'])

const phones = ref([])
const duplicates = ref({})

const callingPrefix = computed(() => props.field.callingPrefix)
const totalPhones = computed(() => phones.value.length)

watch(totalPhones, newVal => {
  if (newVal === 0) newPhone()
})

watch(
  phones,
  newVal =>
    updateModelValue(
      newVal
        .map(phone =>
          defineFormDataObjectUniqueId(omit(phone, 'id'), phone._formUniqueId)
        )
        .filter(phone => Boolean(phone.number))
        .filter(phone => {
          return (
            !callingPrefix.value ||
            phone.number.trim() !== callingPrefix.value.trim()
          )
        })
    ),
  { deep: true }
)

function updateModelValue(value) {
  emit('update:modelValue', value)
}

function setInitialValue() {
  let initialValue = props.field.value || []

  emit('setInitialValue', initialValue.map(toNumber))
}

function removePhone(index) {
  duplicates.value[index] = null
  phones.value.splice(index, 1)

  if (totalPhones.value === 0) newPhone()
}

function newPhone() {
  phones.value.push(
    defineFormDataObjectUniqueId({
      number: callingPrefix.value || '',
      type: props.field.type,
    })
  )
}

function toNumber(phone) {
  return {
    number: phone.number,
    type: phone.type,
  }
}

function prepareField() {
  phones.value = (props.field.value || []).map(toNumber)

  phones.value.forEach(defineFormDataObjectUniqueId)

  setInitialValue()

  if (phones.value.length === 0) newPhone()
}

async function searchDuplicateRecord(index, number) {
  await nextTick()

  if (
    !checksForDuplicates.value ||
    !number ||
    (callingPrefix.value && callingPrefix.value === number)
  ) {
    duplicates.value[index] = null

    return
  }

  duplicates.value[index] = await makeDuplicateCheckRequest(number)
}

const checksForDuplicates = computed(
  () =>
    !props.resourceId &&
    props.field.checkDuplicatesWith &&
    Object.keys(props.field.checkDuplicatesWith).length > 0
)

async function makeDuplicateCheckRequest(query) {
  const { data } = await Innoclapps.request(
    props.field.checkDuplicatesWith.url,
    {
      params: {
        q: query,
        ...props.field.checkDuplicatesWith.params,
      },
    }
  )

  return data.length > 0 ? data[0] : null
}

prepareField()
</script>
