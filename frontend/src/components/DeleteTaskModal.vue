<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue'
import { X } from 'lucide-vue-next'
import { useTasksStore } from '../stores/tasks'
import { useUiStore } from '../stores/ui'

const ui = useUiStore()
const tasks = useTasksStore()

function onEsc(event: KeyboardEvent) {
  if (event.key === 'Escape' && ui.isDeleteModalOpen) ui.closeDeleteModal()
}

async function confirmDelete() {
  if (!ui.deletingTask) return
  await tasks.deleteTask(ui.deletingTask.id)
  ui.closeDeleteModal()
}

watch(
  () => ui.isDeleteModalOpen,
  (open) => {
    if (open) window.addEventListener('keydown', onEsc)
    else window.removeEventListener('keydown', onEsc)
  },
)

onBeforeUnmount(() => window.removeEventListener('keydown', onEsc))
</script>

<template>
  <div v-if="ui.isDeleteModalOpen" class="modal-backdrop">
    <div class="modal">
      <div class="modal-header">
        <h3>Удалить задачу?</h3>
        <button class="ghost close-button" @click="ui.closeDeleteModal()" aria-label="Закрыть модальное окно">
          <X :size="16" />
        </button>
      </div>
      <p>Действие нельзя отменить: <strong>{{ ui.deletingTask?.title }}</strong></p>
      <div class="row">
        <button class="danger" @click="confirmDelete">Удалить</button>
        <button class="ghost" @click="ui.closeDeleteModal()">Отмена</button>
      </div>
    </div>
  </div>
</template>
