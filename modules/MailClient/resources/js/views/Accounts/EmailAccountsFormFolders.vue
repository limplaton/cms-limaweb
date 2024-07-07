<template>
  <EmailAccountsFormFolder
    v-for="(folder, index) in folders"
    :key="index + folder.name"
    :folder="folder"
    :index="index"
    :folders="folders"
  />
</template>

<script setup>
import EmailAccountsFormFolder from './EmailAccountsFormFolder.vue'

defineProps({ folders: Array })
</script>
