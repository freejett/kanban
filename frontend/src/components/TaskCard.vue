<script setup lang="ts">
import { Pencil, Trash2 } from 'lucide-vue-next'
import type { Task } from '../types'

const props = defineProps<{ task: Task }>()
const emit = defineEmits<{ move: [id: number, status: Task['status']]; edit: [task: Task]; remove: [task: Task] }>()

function deadlineBadge(deadline: string | null) {
  if (!deadline) return 'badge-gray'
  const diff = new Date(deadline).getTime() - Date.now()
  if (diff < 24 * 60 * 60 * 1000) return 'badge-red'
  if (diff < 72 * 60 * 60 * 1000) return 'badge-yellow'
  return 'badge-gray'
}
</script>

<template>
  <article class="task-card" :style="{ '--task-accent': task.assigned_user?.color_hex ?? '#94A3B8' }">
    <h4>{{ task.title }}</h4>
    <p class="desc">{{ task.description }}</p>
    <span class="badge" :class="deadlineBadge(task.deadline)">
      {{ task.deadline ? new Date(task.deadline).toLocaleString() : 'Без дедлайна' }}
    </span>
    <div class="mobile-move">
      <button class="ghost icon-button" @click="emit('edit', props.task)" aria-label="Редактировать задачу" title="Редактировать">
        <Pencil :size="16" />
      </button>
      <button class="ghost icon-button" @click="emit('remove', props.task)" aria-label="Удалить задачу" title="Удалить">
        <Trash2 :size="16" />
      </button>
      <select @change="emit('move', props.task.id, ($event.target as HTMLSelectElement).value as Task['status'])">
        <option value="">Переместить...</option>
        <option value="todo">To Do</option>
        <option value="in_progress">In Progress</option>
        <option value="done">Done</option>
      </select>
    </div>
  </article>
</template>
