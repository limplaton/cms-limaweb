<template>
  <div>
    <ITextDisplay class="mb-3 inline-flex items-center">
      Share via link
      <IButtonCopy
        v-i-tooltip="$t('core::app.copy')"
        class="ml-3"
        :text="form.public_url"
        :success-message="$t('core::app.copied')"
      />
    </ITextDisplay>

    <div
      class="select-all rounded-lg border border-neutral-200 bg-neutral-100 px-3 py-1.5 text-neutral-900 dark:border-neutral-500/30 dark:bg-neutral-800 dark:text-neutral-200"
    >
      {{ form.public_url }}
    </div>

    <ITextDisplay class="mb-3 mt-8">
      Embed the form on your Website
    </ITextDisplay>

    <ITextBlock>
      <ul class="ml-4 list-disc leading-7">
        <li>Copy the code snippet below</li>

        <li>
          Paste the code right where you want the form to appear in your
          template or CMS editor
        </li>
      </ul>
    </ITextBlock>

    <IText class="mb-2 mt-4">
      <em class="font-italic"
        >* When entering the snippet to your CMS, make sure you are in
        <span class="font-medium">"editing mode"</span>.
      </em>
    </IText>

    <IText class="mb-2 mt-4">
      <em class="font-italic">
        * You must place the iframe snippet on a website that uses the same
        protocol like your installation, for example, if the current
        installation uses <span class="font-medium">https://</span> URL
        protocol, you will need to add the iframe in a website that uses
        <span class="font-medium">https://</span>
        URL protocol,
        <span class="font-medium">
          adding https URL iframe on a non-https url, will prevent the form from
          loading.
        </span>
      </em>
    </IText>

    <ITextDisplay class="mb-3 mt-8"> Snippet code </ITextDisplay>

    <div
      class="mt-3 select-all rounded-lg border border-neutral-200 bg-neutral-100 px-2 py-1.5 text-neutral-900 dark:border-neutral-500/30 dark:bg-neutral-800 dark:text-neutral-200"
    >
      {{ embedText }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps(['form'])

// the "allow-popups allow-popups-to-escape-sandbox" sandbox values are
// to allow links to open in new tab, for example links added in message or introduction sections.

const embedText = computed(() => {
  return `<iframe src="${props.form.public_url}?e=true" frameborder="0" width="700" height="500" sandbox="allow-top-navigation allow-scripts allow-forms allow-same-origin allow-popups allow-popups-to-escape-sandbox"></iframe>`
})
</script>
