import { defineStore } from 'pinia'
import api from '@/services/api'

export const dashboardPathForRole = (role) => ({
  administrateur: '/admin',
  recruteur: '/recruiter',
  candidat: '/candidate',
})[role] || '/'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token'),
    utilisateur: null,
  }),

  getters: {
    estConnecte: (state) => Boolean(state.token),
    // Alias lu par les vues : l'API nomme l'objet « utilisateur ».
    user: (state) => state.utilisateur,
  },

  actions: {
    enregistrerSession(data) {
      this.token = data.token
      this.utilisateur = data.utilisateur
      localStorage.setItem('token', data.token)
      localStorage.removeItem('user')
    },

    effacerSession() {
      this.token = null
      this.utilisateur = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },

    async connexion(identifiants) {
      const { data } = await api.post('/auth/connexion', identifiants)
      this.enregistrerSession(data)
      return data.utilisateur
    },

    async inscriptionCandidat(donnees) {
      const { data } = await api.post('/auth/inscription/candidat', donnees)
      this.enregistrerSession(data)
      return data.utilisateur
    },

    async inscriptionRecruteur(donnees) {
      const { data } = await api.post('/auth/inscription/recruteur', donnees)
      this.enregistrerSession(data)
      return data.utilisateur
    },

    async chargerUtilisateur() {
      if (!this.token) return null

      try {
        const { data } = await api.get('/auth/moi')
        this.utilisateur = data.utilisateur
        return data.utilisateur
      } catch (error) {
        this.effacerSession()
        throw error
      }
    },

    async deconnexion() {
      try {
        if (this.token) {
          await api.post('/auth/deconnexion')
        }
      } finally {
        this.effacerSession()
      }
    },
  },
})
