<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const password = ref('')
const error = ref('')
const auth = useAuthStore()
const router = useRouter()

async function onSubmit() {
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    await router.push('/')
  } catch (e: any) {
    error.value = e.message
  }
}
</script>

<template>
  <main class="auth-shell">
    <form class="card" @submit.prevent="onSubmit">
      <h1>Вход</h1>
      <input v-model="email" type="email" placeholder="Email" required />
      <input v-model="password" type="password" placeholder="Пароль" required />
      <p class="error">{{ error }}</p>
      <button :disabled="auth.loading">Войти</button>
      <RouterLink to="/register">Регистрация</RouterLink>
    </form>
  </main>
</template>
