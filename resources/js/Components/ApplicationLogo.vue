<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const appName = computed(() => page.props.appName)
</script>

<template>
  <div class="flex items-center justify-center" style="height: 10vh;">
    <img 
      src="/storage/web_images/hsb1v2.jpg" 
      alt="Logo"
      class="h-20 w-auto object-contain"
    />
  </div>
  <!-- <div class="flex items-center" style="text-align:center;">
    <img 
      src="/storage/web_images/hsb1v2.jpg" 
      alt="Logo"
      class="h-20 w-auto object-contain"
    /> -->

    <!-- <img 
      src="/storage/web_images/logo2.png" 
      alt="Logo"
      class="h-10 w-auto object-contain"
    /> -->

    <!-- <span class="text-xl font-bold tracking-wide text-gray-800">
      {{ appName }}
    </span> -->
  <!-- </div> -->
</template>
