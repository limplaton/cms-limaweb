<template>
  <ICustomSelect
    label="name"
    :class="simple ? '[&_.toggle-icon]:size-5' : ''"
    :model-value="modelValue"
    :multiple="true"
    :options="options"
    :reduce="tag => tag.name"
    :simple="simple"
    :filterable="!showForm"
    :placeholder="simple ? $t('core::tags.search') : undefined"
    :toggle-icon="toggleIcon"
    :list-wrapper-class="simple ? 'min-w-[340px]' : undefined"
    :list-class="showForm ? 'hidden' : undefined"
    :reorderable="$gate.isSuperAdmin()"
    @update:model-value="emitUpdateModelValue"
    @update:draggable="handleTagsReordered"
  >
    <template #option-actions="{ index }">
      <IButton
        v-if="$gate.isSuperAdmin()"
        icon="Pencil"
        basic
        small
        @click="prepareEdit(options[index])"
      />
    </template>

    <template v-if="showForm" #header>
      <ITextBlockDark
        class="border-b border-neutral-200 px-5 py-2.5 font-medium dark:border-neutral-500/30"
        :text="
          tagBeingCreated ? $t('core::tags.new_tag') : $t('core::tags.edit_tag')
        "
      />

      <div class="p-5">
        <IFormGroup
          label-for="tag_name"
          :label="$t('core::tags.tag_name')"
          required
        >
          <IFormInput
            id="tag_name"
            v-model="tagForm.name"
            name="tag_name"
            @keydown.enter="save"
          />

          <IFormError :error="tagForm.getError('name')" />
        </IFormGroup>

        <IFormLabel class="mb-1" :label="$t('core::tags.color')" required />

        <IColorSwatch v-model="tagForm.swatch_color" :swatches="swatches" />

        <IFormError :error="tagForm.getError('swatch_color')" />
      </div>

      <div
        class="flex items-center border-t border-neutral-200 px-5 py-2 dark:border-neutral-500/30"
      >
        <IButton
          v-if="!tagBeingCreated && $gate.isSuperAdmin()"
          icon="Trash"
          :confirm-text="$t('core::app.confirm')"
          basic
          confirmable
          @confirmed="deleteTag(tagBeingEdited)"
        />

        <div class="ml-auto flex items-center space-x-2">
          <IButton :text="$t('core::app.cancel')" basic @click="hideForm" />

          <IButton
            variant="primary"
            :text="$t('core::app.save')"
            :loading="tagForm.busy"
            :disabled="tagForm.busy"
            @click="save"
          />
        </div>
      </div>
    </template>

    <template v-if="$gate.isSuperAdmin()" #footer>
      <div
        v-show="!showForm"
        class="border-t border-neutral-200 px-4 py-2 hover:bg-neutral-50 dark:border-neutral-500/30 dark:hover:bg-neutral-700"
      >
        <ILink
          v-show="!showForm"
          class="text-base sm:text-sm"
          @click="tagBeingCreated = true"
        >
          &plus; {{ $t('core::tags.add_new') }}
        </ILink>
      </div>
    </template>
  </ICustomSelect>
</template>

<script setup>
import { computed, ref, toRaw } from 'vue'

import { useApp } from '../composables/useApp'
import { useForm } from '../composables/useForm'
import { useTags } from '../composables/useTags'

const props = defineProps({
  modelValue: Array,
  type: String,
  simple: Boolean,
})

const emit = defineEmits(['update:modelValue'])

const { scriptConfig } = useApp()

const {
  tagsByDisplayOrder,
  findTagsByType,
  removeTag,
  setTag,
  findTagById,
  setTags,
  addTag,
} = useTags()

const options = computed(() => {
  if (props.type) return findTagsByType(props.type)

  return tagsByDisplayOrder.value
})

const swatches = scriptConfig('favourite_colors').slice(0, -2)

const { form: tagForm } = useForm(
  {
    name: '',
    swatch_color: swatches[1],
  },
  {
    resetOnSuccess: true,
  }
)

const tagBeingCreated = ref(false)
const tagBeingEdited = ref(null)

const toggleIcon = computed(() => {
  if (!props.simple) {
    return 'Selector'
  }

  if (!props.modelValue || props.modelValue.length === 0) {
    return 'Tag'
  }

  return ''
})

const showForm = computed(
  () => tagBeingCreated.value || Boolean(tagBeingEdited.value)
)

function save() {
  tagBeingCreated.value ? createTag() : updateTag()
}

function prepareEdit(tag) {
  tagBeingEdited.value = tag.id
  tagForm.fill('name', tag.name)
  tagForm.fill('swatch_color', tag.swatch_color)
}

function handleTagsReordered(tags) {
  setTags(
    tags.map((tag, idx) => ({
      ...tag,
      display_order: idx + 1,
    }))
  )

  Innoclapps.request().post(
    '/tags/order',
    tags.map((tag, index) => ({
      id: tag.id,
      display_order: index + 1,
    }))
  )
}

function hideForm() {
  tagBeingCreated.value = false
  tagBeingEdited.value = null
  tagForm.reset()
}

function createTag() {
  tagForm.post(`/tags${props.type ? `/${props.type}` : ''}`).then(tag => {
    if (findTagById(tag.id)) {
      setTag(tag.id, tag)
    } else {
      addTag(tag)
    }
    hideForm()
  })
}

function updateTag() {
  const oldTagName = findTagById(tagBeingEdited.value).name

  tagForm.put(`/tags/${tagBeingEdited.value}`).then(tag => {
    setTag(tag.id, tag)

    let tagInValueIndex = props.modelValue.findIndex(
      t => (t?.name || t) === oldTagName
    )

    if (tagInValueIndex !== -1) {
      let oldTagValue = toRaw(props.modelValue[tagInValueIndex])

      if (typeof oldTagValue === 'string') {
        oldTagValue = tag.name
      } else {
        oldTagValue.name = tag.name
      }

      let newModelValue = toRaw(props.modelValue)
      newModelValue[tagInValueIndex] = oldTagValue
      emitUpdateModelValue(newModelValue)
    }
    hideForm()
  })
}

function emitUpdateModelValue(data) {
  emit(
    'update:modelValue',
    data.map(tag => {
      if (typeof tag === 'string') {
        return tag
      }

      return tag.name
    })
  )
}

async function deleteTag(id) {
  let tag = findTagById(id)
  await Innoclapps.request().delete(`/tags/${id}`)

  let tagInValueIndex = props.modelValue.findIndex(
    t => (t?.name || t) === tag.name
  )

  if (tagInValueIndex !== -1) {
    emitUpdateModelValue(
      props.modelValue.filter(t => (t?.name || t) !== tag.name)
    )
  }
  removeTag(id)
  hideForm()
}
</script>
