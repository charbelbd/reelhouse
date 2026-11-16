import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const gateway = process.env.GATEWAY_URL || 'http://localhost:8080'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': gateway,
      '/chat-api': gateway,
      '/media': gateway,
      '/app': { target: gateway.replace('http', 'ws'), ws: true },
    },
  },
})
