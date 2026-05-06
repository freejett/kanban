<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import LogTable from '../components/LogTable.vue'
import { getLogs } from '../api/logs'
import type { TaskLog } from '../types'

const logs = ref<TaskLog[]>([])
const page = ref(1)
const total = ref(0)
const router = useRouter()

async function load() {
  const res = await getLogs(page.value, 20)
  logs.value = res.items
  total.value = res.total
}

onMounted(load)
</script>

<template>
  <main class="shell">
    <header class="topbar">
      <h2>Логи изменений</h2>
      <div class="row">
        <button class="ghost" @click="router.push('/admin/users')">Пользователи</button>
        <button class="ghost" @click="router.push('/')">Назад</button>
      </div>
    </header>
    <LogTable :logs="logs" />
    <div class="row">
      <button class="ghost" :disabled="page <= 1" @click="page -= 1; load()">Пред.</button>
      <span>Страница {{ page }} / {{ Math.max(1, Math.ceil(total / 20)) }}</span>
      <button class="ghost" :disabled="page >= Math.ceil(total / 20)" @click="page += 1; load()">След.</button>
    </div>
  </main>
</template>
