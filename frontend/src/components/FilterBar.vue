<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useTasksStore } from '../stores/tasks'
import { onMounted } from 'vue'

const store = useTasksStore()
const { statusFilter, searchQuery, assigneeFilter, users } = storeToRefs(store)
onMounted(() => {
  store.fetchUsers()
})
</script>

<template>
  <div class="filters">
    <input v-model="searchQuery" placeholder="Поиск по задачам" />
    <select v-model="statusFilter">
      <option value="">Все статусы</option>
      <option value="todo">К выполнению</option>
      <option value="in_progress">В работе</option>
      <option value="done">Готово</option>
      <option value="archived">Архив</option>
    </select>
    <select v-model.number="assigneeFilter">
      <option value="">Все исполнители</option>
      <option v-for="u in users" :key="u.id" :value="u.id">{{ u.full_name }}</option>
    </select>
  </div>
</template>
