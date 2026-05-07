export type UserRole = 'user' | 'admin'
export type TaskStatus = 'todo' | 'in_progress' | 'done' | 'archived'

export interface User {
  id: number
  email: string
  full_name: string
  role: UserRole
  color_hex: string
  is_active: number
  csrf_token?: string
}

export interface Task {
  id: number
  title: string
  description: string
  status: TaskStatus
  assigned_to: number | null
  assigned_user?: User | null
  created_by: number
  deadline: string | null
  created_at: string
  updated_at: string
}

export interface TaskLog {
  id: number
  task_id: number
  changed_by: number
  action: string
  old_value: string | null
  new_value: string | null
  created_at: string
  user_name: string
  task_title: string
}
