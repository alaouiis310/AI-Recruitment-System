import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'

import originalRouter from '@/router'
import { useAuthStore, dashboardPathForRole } from '@/stores/auth'

// Mock de l'API : aucun appel HTTP dans les tests unitaires
vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

// Conversion : meta.role (admin/recruiter/candidate) -> role backend
const backendRoleForRouteRole = {
  admin: 'administrateur',
  recruiter: 'recruteur',
  candidate: 'candidat',
}

describe('router guards', () => {
  let testRouter

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()

    // Clone du router avec un historique memoire + replay du guard
    testRouter = createRouter({
      history: createMemoryHistory(),
      routes: originalRouter.options.routes,
    })

    testRouter.beforeEach(async (to) => {
      const auth = useAuthStore()

      if (to.meta.public) {
        if (!auth.estConnecte) return true
        try {
          const utilisateur = auth.utilisateur || (await auth.chargerUtilisateur())
          return dashboardPathForRole(utilisateur.role)
        } catch {
          return true
        }
      }

      if (to.meta.requiresAuth && !auth.estConnecte) {
        return { name: 'home', query: { redirect: to.fullPath } }
      }

      if (to.meta.requiresAuth && !auth.utilisateur) {
        try {
          await auth.chargerUtilisateur()
        } catch {
          return { name: 'home' }
        }
      }

      const requiredRole = backendRoleForRouteRole[to.meta.role]
      if (requiredRole && auth.utilisateur?.role !== requiredRole) {
        return dashboardPathForRole(auth.utilisateur?.role)
      }

      return true
    })
  })

  // -----------------------------------------------------------------
  // Route publique
  // -----------------------------------------------------------------

  it('autorise un visiteur non connecte a voir la page de connexion', async () => {
    await testRouter.push('/')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/')
  })

  // -----------------------------------------------------------------
  // Routes protegees sans authentification -> redirection vers /
  // -----------------------------------------------------------------

  it('redirige un visiteur non authentifie depuis /admin', async () => {
    await testRouter.push('/admin')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/')
  })

  it('redirige un visiteur non authentifie depuis /candidate', async () => {
    await testRouter.push('/candidate')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/')
  })

  it('redirige un visiteur non authentifie depuis /recruiter', async () => {
    await testRouter.push('/recruiter')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/')
  })

  // -----------------------------------------------------------------
  // Acces avec le bon role
  // -----------------------------------------------------------------

  it('autorise un administrateur a acceder a /admin', async () => {
    const auth = useAuthStore()
    auth.enregistrerSession({
      token: 'fake-token',
      utilisateur: { role: 'administrateur' },
    })

    await testRouter.push('/admin')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/admin')
  })

  it('autorise un recruteur a acceder a /recruiter', async () => {
    const auth = useAuthStore()
    auth.enregistrerSession({
      token: 'fake-token',
      utilisateur: { role: 'recruteur' },
    })

    await testRouter.push('/recruiter')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/recruiter')
  })

  it('autorise un candidat a acceder a /candidate', async () => {
    const auth = useAuthStore()
    auth.enregistrerSession({
      token: 'fake-token',
      utilisateur: { role: 'candidat' },
    })

    await testRouter.push('/candidate')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/candidate')
  })

  // -----------------------------------------------------------------
  // Mauvais role -> redirection vers le bon dashboard
  // -----------------------------------------------------------------

  it('redirige un candidat vers /candidate quand il tente /admin', async () => {
    const auth = useAuthStore()
    auth.enregistrerSession({
      token: 'fake-token',
      utilisateur: { role: 'candidat' },
    })

    await testRouter.push('/admin')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/candidate')
  })

  it('redirige un recruteur vers /recruiter quand il tente /admin', async () => {
    const auth = useAuthStore()
    auth.enregistrerSession({
      token: 'fake-token',
      utilisateur: { role: 'recruteur' },
    })

    await testRouter.push('/admin')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/recruiter')
  })

  it('redirige un administrateur vers /admin quand il tente /candidate', async () => {
    const auth = useAuthStore()
    auth.enregistrerSession({
      token: 'fake-token',
      utilisateur: { role: 'administrateur' },
    })

    await testRouter.push('/candidate')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/admin')
  })

  // -----------------------------------------------------------------
  // Redirection explicite (RG44)
  // -----------------------------------------------------------------

  it('redirige /recruiter/messages vers /recruiter/notifications', async () => {
    const auth = useAuthStore()
    auth.enregistrerSession({
      token: 'fake-token',
      utilisateur: { role: 'recruteur' },
    })

    await testRouter.push('/recruiter/messages')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/recruiter/notifications')
  })

  // -----------------------------------------------------------------
  // Sous-routes protegees
  // -----------------------------------------------------------------

  it('redirige un visiteur non authentifie depuis /candidate/jobs', async () => {
    await testRouter.push('/candidate/jobs')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/')
  })

  it('autorise un candidat a acceder a /candidate/jobs', async () => {
    const auth = useAuthStore()
    auth.enregistrerSession({
      token: 'fake-token',
      utilisateur: { role: 'candidat' },
    })

    await testRouter.push('/candidate/jobs')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.path).toBe('/candidate/jobs')
  })
})