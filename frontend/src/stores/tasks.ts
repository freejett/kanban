import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import * as tasksApi from '../api/tasks'
import * as usersApi from '../api/users'
import type { Task, TaskStatus, User } from '../types'

export const useTasksStore = defineStore('tasks', () => {
  const tasks = ref<Task[]>([])
  const loading = ref(false)
  const statusFilter = ref<TaskStatus | ''>('')
  const assigneeFilter = ref<number | ''>('')
  const searchQuery = ref('')
  const users = ref<User[]>([])

  function withAssignee(task: Task): Task {
    if (task.assigned_user) return task
    if (task.assigned_to == null) return { ...task, assigned_user: null }
    const assignedUser = users.value.find((u) => u.id === task.assigned_to) ?? null
    return { ...task, assigned_user: assignedUser }
  }

  const filteredTasks = computed(() =>
    tasks.value.filter((t) => {
      const matchStatus = !statusFilter.value || t.status === statusFilter.value
      const matchAssignee = !assigneeFilter.value || t.assigned_to === assigneeFilter.value
      const matchSearch =
        !searchQuery.value || t.title.toLowerCase().includes(searchQuery.value.toLowerCase())
      return matchStatus && matchAssignee && matchSearch
    }),
  )

  async function fetchTasks() {
    loading.value = true
    try {
      const loaded = await tasksApi.getTasks()
      tasks.value = loaded.map(withAssignee)
    } finally {
      loading.value = false
    }
  }

  async function fetchUsers(search = '') {
    users.value = await usersApi.getUsers(search)
    tasks.value = tasks.value.map(withAssignee)
  }

  async function createTask(payload: {
    title: string
    description?: string
    deadline?: string | null
    status?: TaskStatus
    assigned_to?: number | null
  }) {
    const item = await tasksApi.createTask(payload)
    tasks.value.unshift(withAssignee(item))
  }

  async function syncTask(
    id: number,
    patch: Partial<Pick<Task, 'status' | 'assigned_to' | 'deadline' | 'title' | 'description'>>,
  ) {
    const prev = tasks.value.find((t) => t.id === id)
    const snapshot = prev ? { ...prev } : null
    if (prev) {
      Object.assign(prev, patch)
      if (Object.prototype.hasOwnProperty.call(patch, 'assigned_to')) {
        prev.assigned_user = users.value.find((u) => u.id === prev.assigned_to) ?? null
      }
    }
    try {
      const updated = await tasksApi.patchTask(id, patch)
      const idx = tasks.value.findIndex((t) => t.id === id)
      if (idx >= 0) tasks.value[idx] = withAssignee(updated)
    } catch (e) {
      if (snapshot) {
        const idx = tasks.value.findIndex((t) => t.id === id)
        if (idx >= 0) tasks.value[idx] = snapshot
      }
      throw e
    }
  }

  async function deleteTask(id: number) {
    await tasksApi.deleteTask(id)
    tasks.value = tasks.value.filter((t) => t.id !== id)
  }

  return {
    tasks,
    filteredTasks,
    loading,
    statusFilter,
    assigneeFilter,
    users,
    searchQuery,
    fetchTasks,
    fetchUsers,
    createTask,
    syncTask,
    deleteTask,
  }
})
