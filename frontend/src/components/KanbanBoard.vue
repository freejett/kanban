<script setup lang="ts">
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { useTasksStore } from '../stores/tasks'
import { useUiStore } from '../stores/ui'
import TaskCard from './TaskCard.vue'
import type { Task, TaskStatus } from '../types'

const store = useTasksStore()
const ui = useUiStore()

const columns = [
  { status: 'todo', title: 'К выполнению' },
  { status: 'in_progress', title: 'В работе' },
  { status: 'done', title: 'Готово' },
  { status: 'archived', title: 'Архив' },
] as const

const visibleColumns = computed(() =>
  columns.filter((column) => column.status !== 'archived' || store.statusFilter === 'archived'),
)

const grouped = computed(() =>
  visibleColumns.value.map((c) => ({
    ...c,
    tasks: store.filteredTasks.filter((t) => t.status === c.status),
  })),
)

function onDragEnd(event: { item?: HTMLElement }, toStatus: TaskStatus) {
  const rawId = event.item?.dataset.taskId
  const id = rawId ? Number(rawId) : 0
  if (id > 0) store.syncTask(id, { status: toStatus })
}

function moveTask(id: number, status: TaskStatus) {
  if (status) store.syncTask(id, { status })
}

function editTask(task: Task) {
  ui.openTaskModal(task)
}

function removeTask(task: Task) {
  ui.openDeleteModal(task)
}
</script>

<template>
  <div class="board">
    <section v-for="column in grouped" :key="column.status" class="column">
      <h3>{{ column.title }}</h3>
      <VueDraggable
        class="dropzone"
        :model-value="column.tasks"
        group="tasks"
        item-key="id"
        @end="(e) => onDragEnd(e, column.status)"
      >
        <TaskCard
          v-for="task in column.tasks"
          :key="task.id"
          :task="task"
          @move="(id, status) => moveTask(id, status)"
          @edit="(task) => editTask(task)"
          @remove="(task) => removeTask(task)"
        />
      </VueDraggable>
    </section>
  </div>
</template>
