<template>
  <MainLayout>
    <ResourceImport :resource-name="$route.params.resourceName" />
  </MainLayout>
</template>

<script setup>
import ResourceImport from '@/Core/components/Import/ResourceImport.vue'
</script>
