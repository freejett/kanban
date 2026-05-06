<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
import { useTasksStore } from '../stores/tasks'
import { useUiStore } from '../stores/ui'

const title = ref('')
const description = ref('')
const deadline = ref('')
const status = ref<'todo' | 'in_progress' | 'done'>('todo')
const assignee = ref<number | ''>('')
const tasks = useTasksStore()
const ui = useUiStore()
const isEditMode = computed(() => Boolean(ui.editingTask))

function onEsc(event: KeyboardEvent) {
  if (event.key === 'Escape' && ui.isTaskModalOpen) {
    ui.closeTaskModal()
  }
}

async function submit() {
  if (!title.value.trim()) return
  if (ui.editingTask) {
    await tasks.syncTask(ui.editingTask.id, {
      title: title.value,
      description: description.value,
      deadline: deadline.value || null,
      status: status.value,
      assigned_to: assignee.value === '' ? null : Number(assignee.value),
    })
  } else {
    await tasks.createTask({
      title: title.value,
      description: description.value,
      deadline: deadline.value || null,
    })
  }
  title.value = ''
  description.value = ''
  deadline.value = ''
  status.value = 'todo'
  assignee.value = ''
  ui.closeTaskModal()
}

watch(
  () => ui.editingTask,
  (task) => {
    if (!task) return
    title.value = task.title
    description.value = task.description
    deadline.value = task.deadline ? task.deadline.slice(0, 16) : ''
    status.value = task.status
    assignee.value = task.assigned_to ?? ''
  },
)

watch(
  () => ui.isTaskModalOpen,
  (open) => {
    if (open) {
      window.addEventListener('keydown', onEsc)
    } else {
      window.removeEventListener('keydown', onEsc)
    }
    if (!open || ui.editingTask) return
    title.value = ''
    description.value = ''
    deadline.value = ''
    status.value = 'todo'
    assignee.value = ''
  },
)

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onEsc)
})
</script>

<template>
  <div v-if="ui.isTaskModalOpen" class="modal-backdrop">
    <div class="modal">
      <div class="modal-header">
        <h3>{{ isEditMode ? 'Редактировать задачу' : 'Новая задача' }}</h3>
        <button class="ghost close-button" @click="ui.closeTaskModal" aria-label="Закрыть модальное окно">
          <X :size="16" />
        </button>
      </div>
      <input v-model="title" placeholder="Заголовок" />
      <textarea v-model="description" placeholder="Описание" />
      <input v-model="deadline" type="datetime-local" />
      <select v-model="status">
        <option value="todo">To Do</option>
        <option value="in_progress">In Progress</option>
        <option value="done">Done</option>
      </select>
      <select v-model.number="assignee">
        <option value="">Без исполнителя</option>
        <option v-for="u in tasks.users" :key="u.id" :value="u.id">{{ u.full_name }}</option>
      </select>
      <div class="row">
        <button @click="submit">{{ isEditMode ? 'Сохранить' : 'Создать' }}</button>
        <button class="ghost" @click="ui.closeTaskModal">Отмена</button>
      </div>
    </div>
  </div>
</template>
