import { ref } from 'vue'
import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', () => {
  const isTaskModalOpen = ref(false)
  function openTaskModal() {
    isTaskModalOpen.value = true
  }
  function closeTaskModal() {
    isTaskModalOpen.value = false
  }
  return { isTaskModalOpen, openTaskModal, closeTaskModal }
})
