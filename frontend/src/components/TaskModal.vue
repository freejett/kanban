<script setup lang="ts">
import { ref } from 'vue'
import { useTasksStore } from '../stores/tasks'
import { useUiStore } from '../stores/ui'

const title = ref('')
const description = ref('')
const deadline = ref('')
const tasks = useTasksStore()
const ui = useUiStore()

async function submit() {
  if (!title.value.trim()) return
  await tasks.createTask({
    title: title.value,
    description: description.value,
    deadline: deadline.value || null,
  })
  title.value = ''
  description.value = ''
  deadline.value = ''
  ui.closeTaskModal()
}
</script>

<template>
  <div v-if="ui.isTaskModalOpen" class="modal-backdrop">
    <div class="modal">
      <h3>Новая задача</h3>
      <input v-model="title" placeholder="Заголовок" />
      <textarea v-model="description" placeholder="Описание" />
      <input v-model="deadline" type="datetime-local" />
      <div class="row">
        <button @click="submit">Создать</button>
        <button class="ghost" @click="ui.closeTaskModal">Отмена</button>
      </div>
    </div>
  </div>
</template>
