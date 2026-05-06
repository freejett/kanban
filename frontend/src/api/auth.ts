import { request } from './http'
import type { User } from '../types'

export function register(input: { email: string; password: string; full_name: string }) {
  return request<User>('/auth/register.php', { method: 'POST', body: JSON.stringify(input) })
}

export function login(input: { email: string; password: string }) {
  return request<User>('/auth/login.php', { method: 'POST', body: JSON.stringify(input) })
}

export function me() {
  return request<User>('/auth/me.php')
}

export function logout() {
  return request<{ ok: true }>('/auth/logout.php', { method: 'POST' })
}
