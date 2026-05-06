import { ref } from 'vue'
import { defineStore } from 'pinia'

type ThemeMode = 'light' | 'dark'

export const useUiStore = defineStore('ui', () => {
  const isTaskModalOpen = ref(false)
  const theme = ref<ThemeMode>('light')

  function applyTheme(mode: ThemeMode) {
    theme.value = mode
    if (typeof document !== 'undefined') {
      document.documentElement.setAttribute('data-theme', mode)
    }
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('kanban-theme', mode)
    }
  }

  function initTheme() {
    if (typeof window === 'undefined') return
    const saved = localStorage.getItem('kanban-theme')
    if (saved === 'light' || saved === 'dark') {
      applyTheme(saved)
      return
    }
    const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    applyTheme(preferred)
  }

  function toggleTheme() {
    applyTheme(theme.value === 'light' ? 'dark' : 'light')
  }

  function openTaskModal() {
    isTaskModalOpen.value = true
  }
  function closeTaskModal() {
    isTaskModalOpen.value = false
  }
  return { isTaskModalOpen, theme, initTheme, toggleTheme, openTaskModal, closeTaskModal }
})
