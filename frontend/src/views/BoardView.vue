<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import FilterBar from '../components/FilterBar.vue'
import KanbanBoard from '../components/KanbanBoard.vue'
import TaskModal from '../components/TaskModal.vue'
import DeleteTaskModal from '../components/DeleteTaskModal.vue'
import { useAuthStore } from '../stores/auth'
import { useTasksStore } from '../stores/tasks'
import { useUiStore } from '../stores/ui'

const auth = useAuthStore()
const tasks = useTasksStore()
const ui = useUiStore()
const router = useRouter()

onMounted(() => {
  tasks.fetchTasks()
  tasks.fetchUsers()
})
</script>

<template>
  <main class="shell">
    <header class="topbar">
      <h2>Планирование</h2>
      <div class="row">
        <button @click="ui.openTaskModal()">+ Задача</button>
        <button class="ghost" @click="ui.toggleTheme()">{{ ui.theme === 'light' ? '🌙 Тёмная' : '☀️ Светлая' }}</button>
        <button class="ghost" @click="router.push('/admin/logs')" v-if="auth.isAdmin">Логи</button>
        <button class="ghost" @click="router.push('/admin/users')" v-if="auth.isAdmin">Пользователи</button>
        <button class="ghost" @click="auth.logout().then(() => router.push('/login'))">Выход</button>
      </div>
    </header>

    <FilterBar />
    <KanbanBoard />
    <TaskModal />
    <DeleteTaskModal />
  </main>
</template>
