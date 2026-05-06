import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: '../public_html',
    emptyOutDir: false,
  },
  server: {
    proxy: {
      '/api': 'http://localhost:8001',
    },
  },
})
