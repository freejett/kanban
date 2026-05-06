const API_BASE =
  import.meta.env.VITE_API_BASE ??
  (typeof window !== 'undefined' && window.location.hostname === 'localhost'
    ? 'http://localhost:8001/api'
    : '/api')
let csrfToken = ''

export function setCsrfToken(token?: string) {
  csrfToken = token ?? ''
}

export class ApiError extends Error {
  code: number

  constructor(message: string, code = 500) {
    super(message)
    this.code = code
  }
}

export async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`${API_BASE}${path}`, {
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
      ...(init?.headers ?? {}),
    },
    ...init,
  })
  const freshToken = res.headers.get('X-CSRF-Token')
  if (freshToken) csrfToken = freshToken

  const payload = await res.json()
  if (!res.ok || payload?.error) {
    throw new ApiError(payload?.error?.message ?? 'Request failed', payload?.error?.code ?? res.status)
  }

  return payload.data as T
}
