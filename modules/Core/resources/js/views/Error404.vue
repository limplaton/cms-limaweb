<template>
  <MainLayout no-padding>
    <div class="mt-20 flex flex-col flex-wrap content-center">
      <div class="flex flex-col items-center">
        <Icon icon="QuestionMarkCircle" class="h-20 w-20 text-primary-300" />

        <h1
          class="mb-2 mt-6 text-3xl font-bold text-neutral-900 dark:text-white"
        >
          Oops!
        </h1>

        <h2 class="text-2xl text-neutral-700 dark:text-neutral-100">
          Page not found
        </h2>
      </div>

      <div class="flex flex-col justify-center">
        <p class="mt-5 text-neutral-700 dark:text-neutral-300">
          The page you are trying to view does not exists or you don't have
          permissions to access it.
        </p>

        <div class="mt-5 text-center">
          <IButton variant="primary" :to="{ name: 'dashboard' }">
            Go to dashboard
          </IButton>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
