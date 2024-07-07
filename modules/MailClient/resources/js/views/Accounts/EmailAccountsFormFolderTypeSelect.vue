<template>
  <!-- We will add custom option.parent_id in the label as
              if the folder name is duplicated the ICustomSelect addon won't work properly
              because ICustomSelect determines uniquness via the label.
              In thi case, we will provide custom function for getOptionLabel and will format
              the actual labels separtely via slots -->
  <IFormGroup :label="label" :label-for="field" :required="required">
    <ICustomSelect
      v-model="model"
      :clearable="false"
      :input-id="field"
      :options="folders"
      :option-label="
        option => '--' + option.parent_id + '--' + option.display_name
      "
      :reduce="folder => folder.id"
    >
      <template #option="option">
        {{ option.display_name.replace('--' + option.parent_id + '--', '') }}
      </template>

      <template #selected-option="option">
        {{ option.display_name.replace('--' + option.parent_id + '--', '') }}
      </template>
    </ICustomSelect>

    <IFormError :error="form.getError(field)" />
  </IFormGroup>
</template>

<script setup>
defineProps(['form', 'field', 'folders', 'label', 'required'])
const model = defineModel()
</script>
