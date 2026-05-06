import { request } from './http'
import type { Task } from '../types'

export function getTasks(query = '') {
  return request<Task[]>(`/tasks/index.php${query ? `?${query}` : ''}`)
}

export function getUsers(search = '') {
  return request<Array<{ id: number; full_name: string; email: string }>>(
    `/users/index.php?search=${encodeURIComponent(search)}`,
  )
}

export function createTask(input: { title: string; description?: string; deadline?: string | null }) {
  return request<Task>('/tasks/index.php', { method: 'POST', body: JSON.stringify(input) })
}

export function patchTask(
  id: number,
  patch: Partial<Pick<Task, 'status' | 'assigned_to' | 'deadline' | 'title' | 'description'>>,
) {
  return request<Task>(`/tasks/update.php?id=${id}`, { method: 'PATCH', body: JSON.stringify(patch) })
}

export function deleteTask(id: number) {
  return request<{ ok: true }>(`/tasks/delete.php?id=${id}`, { method: 'DELETE' })
}
