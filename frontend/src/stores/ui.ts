import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { Task } from '../types'

type ThemeMode = 'light' | 'dark'

export const useUiStore = defineStore('ui', () => {
  const isTaskModalOpen = ref(false)
  const isDeleteModalOpen = ref(false)
  const editingTask = ref<Task | null>(null)
  const deletingTask = ref<Task | null>(null)
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

  function openTaskModal(task: Task | null = null) {
    editingTask.value = task
    isTaskModalOpen.value = true
  }
  function closeTaskModal() {
    editingTask.value = null
    isTaskModalOpen.value = false
  }
  function openDeleteModal(task: Task) {
    deletingTask.value = task
    isDeleteModalOpen.value = true
  }
  function closeDeleteModal() {
    deletingTask.value = null
    isDeleteModalOpen.value = false
  }
  return {
    isTaskModalOpen,
    isDeleteModalOpen,
    editingTask,
    deletingTask,
    theme,
    initTheme,
    toggleTheme,
    openTaskModal,
    closeTaskModal,
    openDeleteModal,
    closeDeleteModal,
  }
})
