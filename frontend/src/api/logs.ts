import { request } from './http'
import type { TaskLog } from '../types'

export function getLogs(page = 1, limit = 20) {
  return request<{ items: TaskLog[]; total: number }>(`/logs/index.php?page=${page}&limit=${limit}`)
}
