<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const fullName = ref('')
const email = ref('')
const password = ref('')
const error = ref('')
const auth = useAuthStore()
const router = useRouter()

async function onSubmit() {
  error.value = ''
  try {
    await auth.register(email.value, password.value, fullName.value)
    await router.push('/')
  } catch (e: any) {
    error.value = e.message
  }
}
</script>

<template>
  <main class="auth-shell">
    <form class="card" @submit.prevent="onSubmit">
      <h1>Регистрация</h1>
      <input v-model="fullName" placeholder="Имя" required />
      <input v-model="email" type="email" placeholder="Email" required />
      <input v-model="password" type="password" placeholder="Пароль" required />
      <p class="error">{{ error }}</p>
      <button :disabled="auth.loading">Создать аккаунт</button>
      <RouterLink to="/login">Уже есть аккаунт</RouterLink>
    </form>
  </main>
</template>
