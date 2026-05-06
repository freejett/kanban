<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useUiStore } from './stores/ui'

const ui = useUiStore()
const { theme } = storeToRefs(ui)

onMounted(() => {
  ui.initTheme()
})
</script>

<template>
  <button class="theme-toggle" @click="ui.toggleTheme()">
    {{ theme === 'light' ? '🌙 Тёмная' : '☀️ Светлая' }}
  </button>
  <RouterView />
</template>
