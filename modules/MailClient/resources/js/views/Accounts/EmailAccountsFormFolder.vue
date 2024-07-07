<!-- eslint-disable vue/no-mutating-props -->
<template>
  <IFormCheckboxField>
    <IFormCheckbox
      v-model:checked="folders[index].syncable"
      :disabled="!folder.selectable"
    />

    <IFormCheckboxLabel :text="folder.display_name" />
  </IFormCheckboxField>

  <template v-if="folder.children">
    <div
      v-for="(child, childIndex) in folder.children"
      :key="childIndex + child.name"
      class="ml-6"
    >
      <EmailAccountsFormFolder
        :folder="child"
        :index="childIndex"
        :folders="folder.children"
      />
    </div>
  </template>
</template>

<script setup>
defineOptions({
  name: 'EmailAccountsFormFolder',
})

defineProps({
  folder: { required: true, type: Object },
  folders: { required: true, type: Array },
  index: { required: true, type: Number },
})
</script>
