<template>
  <MainLayout no-padding>
    <div class="mt-20 flex flex-col flex-wrap content-center">
      <div class="flex flex-col items-center">
        <Icon icon="ShieldExclamation" class="h-20 w-20 text-primary-300" />

        <h1
          class="mb-2 mt-6 text-3xl font-bold text-neutral-900 dark:text-white"
        >
          403!
        </h1>

        <h2 class="text-2xl text-neutral-700 dark:text-neutral-100">
          Hang on...
        </h2>
      </div>

      <div class="flex flex-col justify-center">
        <p class="mt-5 text-center text-neutral-700 dark:text-neutral-200">
          {{
            $route.query.message ||
            'You are not authorized to perform this action or view this page.'
          }}
        </p>

        <div class="mt-5 text-center">
          <IButton to="/dashboard" variant="primary"> Go to dashboard </IButton>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup></script>
