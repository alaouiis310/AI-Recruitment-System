import { describe, it, expect, beforeEach } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'

// Note : adapter selon votre vrai routeur (import depuis '@/router')
import { routes } from '@/router'

describe('router guards', () => {
  let router

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    router = createRouter({ history: createMemoryHistory(), routes })
  })

  it('redirige un visiteur non authentifie vers la page de connexion', async () => {
    await router.push('/admin')
    await router.isReady()
    expect(router.currentRoute.value.path).toBe('/')
  })

  it('autorise l acces au dashboard admin quand connecte en admin', async () => {
    localStorage.setItem('token', 'fake')
    localStorage.setItem('role', 'administrateur')
    await router.push('/admin')
    await router.isReady()
    expect(router.currentRoute.value.path).toBe('/admin')
  })
})