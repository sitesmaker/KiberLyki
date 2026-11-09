import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter } from "react-router"
import './index.css'
import App from './App.tsx'
import { mountPublicIslands } from './public-islands'

const queryClient = new QueryClient()

if (document.getElementById('root')?.hasAttribute('data-server-page')) {
  mountPublicIslands();
} else createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <App />
      </BrowserRouter>
    </QueryClientProvider>
  </StrictMode>,
)
