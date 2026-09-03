import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import api from '@/services/api'
import { dashboardPathForRole, useAuthStore } from '@/stores/auth'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('authentication store', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('stores the bearer token and authenticated user after login', async () => {
    const utilisateur = { id_user: 1, role: 'candidat' }
    api.post.mockResolvedValueOnce({
      data: { token: 'candidate-token', utilisateur },
    })

    const auth = useAuthStore()
    const credentials = {
      email: 'candidat@airs.ma',
      password: 'Password123',
      device_name: 'navigateur-web',
    }

    await expect(auth.connexion(credentials)).resolves.toEqual(utilisateur)
    expect(api.post).toHaveBeenCalledWith('/auth/connexion', credentials)
    expect(auth.token).toBe('candidate-token')
    expect(auth.utilisateur).toEqual(utilisateur)
    expect(localStorage.getItem('token')).toBe('candidate-token')
  })

  it('restores the user associated with an existing token', async () => {
    localStorage.setItem('token', 'existing-token')
    const utilisateur = { id_user: 2, role: 'recruteur' }
    api.get.mockResolvedValueOnce({ data: { utilisateur } })

    const auth = useAuthStore()

    await expect(auth.chargerUtilisateur()).resolves.toEqual(utilisateur)
    expect(api.get).toHaveBeenCalledWith('/auth/moi')
    expect(auth.utilisateur).toEqual(utilisateur)
  })

  it('uses the separate candidate and recruiter registration endpoints', async () => {
    api.post
      .mockResolvedValueOnce({
        data: { token: 'candidate-token', utilisateur: { role: 'candidat' } },
      })
      .mockResolvedValueOnce({
        data: { token: 'recruiter-token', utilisateur: { role: 'recruteur' } },
      })

    const auth = useAuthStore()
    const candidat = { email: 'candidate@example.com' }
    const recruteur = { email: 'recruiter@example.com', entreprise: { nom: 'AIRS' } }

    await auth.inscriptionCandidat(candidat)
    await auth.inscriptionRecruteur(recruteur)

    expect(api.post).toHaveBeenNthCalledWith(1, '/auth/inscription/candidat', candidat)
    expect(api.post).toHaveBeenNthCalledWith(2, '/auth/inscription/recruteur', recruteur)
  })

  it('revokes the current token and clears the local session on logout', async () => {
    api.post.mockResolvedValueOnce({ data: { message: 'ok' } })

    const auth = useAuthStore()
    auth.enregistrerSession({
      token: 'active-token',
      utilisateur: { role: 'administrateur' },
    })

    await auth.deconnexion()

    expect(api.post).toHaveBeenCalledWith('/auth/deconnexion')
    expect(auth.token).toBeNull()
    expect(auth.utilisateur).toBeNull()
    expect(localStorage.getItem('token')).toBeNull()
  })

  it('maps backend role values to their frontend dashboards', () => {
    expect(dashboardPathForRole('administrateur')).toBe('/admin')
    expect(dashboardPathForRole('recruteur')).toBe('/recruiter')
    expect(dashboardPathForRole('candidat')).toBe('/candidate')
  })
})
