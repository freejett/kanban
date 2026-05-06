import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import * as authApi from '../api/auth'
import type { User } from '../types'
import { setCsrfToken } from '../api/http'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => Boolean(user.value))
  const isAdmin = computed(() => user.value?.role === 'admin')

  async function fetchMe() {
    try {
      user.value = await authApi.me()
      setCsrfToken(user.value?.csrf_token)
    } catch {
      user.value = null
      // keep CSRF token obtained from response headers even when user is unauthorized
    }
  }

  async function login(email: string, password: string) {
    loading.value = true
    try {
      user.value = await authApi.login({ email, password })
      setCsrfToken(user.value?.csrf_token)
    } finally {
      loading.value = false
    }
  }

  async function register(email: string, password: string, fullName: string) {
    loading.value = true
    try {
      user.value = await authApi.register({ email, password, full_name: fullName })
      setCsrfToken(user.value?.csrf_token)
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    await authApi.logout()
    user.value = null
    setCsrfToken('')
  }

  return { user, loading, isAuthenticated, isAdmin, fetchMe, login, register, logout }
})
