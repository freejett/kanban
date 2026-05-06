import { request } from './http'
import type { User, UserRole } from '../types'

export function getUsers(search = '') {
  return request<User[]>(`/users/index.php?search=${encodeURIComponent(search)}`)
}

export function createUser(payload: {
  email: string
  password: string
  full_name: string
  role: UserRole
  color_hex: string
  is_active?: number
}) {
  return request<User>('/users/index.php', { method: 'POST', body: JSON.stringify(payload) })
}

export function updateUser(
  id: number,
  patch: Partial<Pick<User, 'full_name' | 'role' | 'color_hex' | 'is_active'>> & { password?: string },
) {
  return request<User>(`/users/index.php?id=${id}`, { method: 'PATCH', body: JSON.stringify(patch) })
}

export function deleteUser(id: number) {
  return request<{ ok: true }>(`/users/index.php?id=${id}`, { method: 'DELETE' })
}
