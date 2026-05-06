<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { createUser, deleteUser, getUsers, updateUser } from '../api/users'
import type { User, UserRole } from '../types'

const router = useRouter()
const users = ref<User[]>([])
const search = ref('')
const error = ref('')
const isCreateModalOpen = ref(false)

const form = ref({
  email: '',
  password: '',
  full_name: '',
  role: 'user' as UserRole,
  color_hex: '#3B82F6',
})

const draft = ref<Record<number, { full_name: string; role: UserRole; color_hex: string; password: string }>>({})

function ensureDraft(user: User) {
  if (!draft.value[user.id]) {
    draft.value[user.id] = {
      full_name: user.full_name,
      role: user.role,
      color_hex: user.color_hex,
      password: '',
    }
  }
}

async function load() {
  users.value = await getUsers(search.value)
  users.value.forEach(ensureDraft)
}

async function submitCreate() {
  error.value = ''
  try {
    const created = await createUser(form.value)
    users.value.unshift(created)
    ensureDraft(created)
    form.value = { email: '', password: '', full_name: '', role: 'user', color_hex: '#3B82F6' }
    isCreateModalOpen.value = false
  } catch (e: any) {
    error.value = e.message
  }
}

async function saveUser(user: User) {
  error.value = ''
  const data = draft.value[user.id]
  if (!data) return
  try {
    const updated = await updateUser(user.id, {
      full_name: data.full_name,
      role: data.role,
      color_hex: data.color_hex,
      ...(data.password ? { password: data.password } : {}),
    })
    const idx = users.value.findIndex((u) => u.id === user.id)
    if (idx >= 0) users.value[idx] = updated
    draft.value[user.id].password = ''
  } catch (e: any) {
    error.value = e.message
  }
}

async function removeUser(user: User) {
  if (!confirm(`Удалить пользователя ${user.email}?`)) return
  error.value = ''
  try {
    await deleteUser(user.id)
    users.value = users.value.filter((u) => u.id !== user.id)
    delete draft.value[user.id]
  } catch (e: any) {
    error.value = e.message
  }
}

function onEsc(event: KeyboardEvent) {
  if (event.key === 'Escape' && isCreateModalOpen.value) isCreateModalOpen.value = false
}

watch(isCreateModalOpen, (open) => {
  if (open) window.addEventListener('keydown', onEsc)
  else window.removeEventListener('keydown', onEsc)
})

onBeforeUnmount(() => window.removeEventListener('keydown', onEsc))

onMounted(load)
</script>

<template>
  <main class="shell">
    <header class="topbar">
      <h2>Пользователи</h2>
      <div class="row">
        <button class="ghost" @click="router.push('/admin/logs')">Логи</button>
        <button class="ghost" @click="router.push('/')">Назад</button>
      </div>
    </header>

    <p class="error">{{ error }}</p>

    <section class="card full-width">
      <div class="row">
        <h3>Список пользователей</h3>
        <div class="row">
          <button @click="isCreateModalOpen = true">Добавить пользователя</button>
          <input v-model="search" placeholder="Поиск" />
          <button class="ghost" @click="load">Искать</button>
        </div>
      </div>
      <table class="log-table">
        <thead>
          <tr>
            <th>Email</th>
            <th>Имя</th>
            <th>Роль</th>
            <th>Цвет</th>
            <th>Новый пароль</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>{{ user.email }}</td>
            <td><input v-model="draft[user.id].full_name" /></td>
            <td>
              <select v-model="draft[user.id].role">
                <option value="user">user</option>
                <option value="admin">admin</option>
              </select>
            </td>
            <td><input v-model="draft[user.id].color_hex" /></td>
            <td><input v-model="draft[user.id].password" type="password" placeholder="Опц." /></td>
            <td class="row">
              <button class="ghost" @click="saveUser(user)">Сохранить</button>
              <button class="danger" @click="removeUser(user)">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <div v-if="isCreateModalOpen" class="modal-backdrop">
      <div class="modal">
        <div class="modal-header">
          <h3>Создать пользователя</h3>
          <button class="ghost close-button" @click="isCreateModalOpen = false" aria-label="Закрыть модальное окно">
            <X :size="16" />
          </button>
        </div>
        <div class="row">
          <input v-model="form.email" type="email" placeholder="Email" />
          <input v-model="form.password" type="password" placeholder="Пароль" />
        </div>
        <div class="row">
          <input v-model="form.full_name" placeholder="Имя" />
          <select v-model="form.role">
            <option value="user">user</option>
            <option value="admin">admin</option>
          </select>
          <input v-model="form.color_hex" placeholder="#3B82F6" />
        </div>
        <div class="row">
          <button @click="submitCreate">Создать</button>
          <button class="ghost" @click="isCreateModalOpen = false">Отмена</button>
        </div>
      </div>
    </div>
  </main>
</template>
