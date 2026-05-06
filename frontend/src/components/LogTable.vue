<script setup lang="ts">
import type { TaskLog } from '../types'

defineProps<{ logs: TaskLog[] }>()
</script>

<template>
  <table class="log-table">
    <thead>
      <tr>
        <th>Дата</th>
        <th>Задача</th>
        <th>Действие</th>
        <th>Старое</th>
        <th>Новое</th>
        <th>Кто</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="log in logs" :key="log.id">
        <td>{{ new Date(log.created_at).toLocaleString() }}</td>
        <td>{{ log.task_title }}</td>
        <td>{{ log.action }}</td>
        <td>{{ log.old_value ?? '-' }}</td>
        <td>{{ log.new_value ?? '-' }}</td>
        <td>{{ log.user_name }}</td>
      </tr>
    </tbody>
  </table>
</template>
