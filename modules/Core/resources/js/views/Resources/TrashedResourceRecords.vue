<template>
  <MainLayout>
    <ResourceTrashedTable :resource-name="$route.params.resourceName" />
  </MainLayout>
</template>

<script setup>
import ResourceTrashedTable from '@/Core/components/ResourceTable/ResourceTrashedTable.vue'
</script>
