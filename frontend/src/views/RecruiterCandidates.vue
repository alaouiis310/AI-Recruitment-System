<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Candidats" 
    page-subtitle="Gérez tous vos candidats"
  >
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
    </template>

    <template #header-actions>
      <button @click="exporterCsv" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition-all">
        Exporter CSV
      </button>
    </template>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-gray-500 mt-4 text-sm">Chargement des candidatures...</p>
      </div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-4xl mb-2">⚠️</p>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="fetchCandidates" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else>
      <!-- Barre de recherche -->
      <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="flex-1 relative">
          <input 
            v-model="searchQuery" 
            @input="debouncedSearch"
            type="text" 
            placeholder="Rechercher un candidat..." 
            class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
          >
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
        </div>
        <select 
          v-model="selectedStatus"
          @change="fetchCandidates"
          class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm"
        >
          <option value="">Tous les statuts</option>
          <option value="en_attente">En attente</option>
          <option value="en_cours">En cours</option>
          <option value="preselectionnee">Présélectionnée</option>
          <option value="acceptee">Acceptée</option>
          <option value="refusee">Refusée</option>
        </select>
      </div>

      <!-- Liste des candidats -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-left">
                <th class="px-6 py-3 font-medium text-gray-500">Candidat</th>
                <th class="px-6 py-3 font-medium text-gray-500">Poste</th>
                <th class="px-6 py-3 font-medium text-gray-500">Score IA</th>
                <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
                <th class="px-6 py-3 font-medium text-gray-500">Date</th>
                <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="app in applications" 
                :key="app.id_candidature" 
                class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="getAvatar(app)" 
                      alt="Avatar" 
                      class="w-9 h-9 rounded-full border-2 border-blue-100 object-cover"
                    >
                    <div>
                      <p class="font-medium text-gray-800">
                        {{ app.candidat?.utilisateur?.prenom }} {{ app.candidat?.utilisateur?.nom }}
                      </p>
                      <p class="text-xs text-gray-500">{{ app.candidat?.utilisateur?.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600">
                  {{ app.offre?.titre || 'N/A' }}
                </td>
                <td class="px-6 py-4">
                  <div v-if="app.score_final != null" class="flex items-center gap-2">
                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                      <div 
                        class="h-1.5 rounded-full" 
                        :style="{ 
                          width: app.score_final + '%', 
                          background: getScoreColor(app.score_final) 
                        }"
                      ></div>
                    </div>
                    <span 
                      class="text-xs font-medium" 
                      :class="getScoreTextColor(app.score_final)"
                    >
                      {{ Math.round(app.score_final) }}%
                    </span>
                  </div>
                  <span v-else class="text-xs text-gray-400">Non analysé</span>
                </td>
                <td class="px-6 py-4">
                  <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(app.statut)]">
                    {{ formatStatus(app.statut) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-500">
                  {{ formatDate(app.date_candidature) }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <button 
                      v-if="prochainStatut(app)"
                      @click="advanceStatus(app)"
                      class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                    >
                      Avancer
                    </button>
                    <button
                      v-if="peutRefuser(app)"
                      @click="refuser(app)"
                      class="text-red-500 hover:text-red-700 text-sm font-medium"
                    >
                      Refuser
                    </button>
                    <span v-if="app.statut_definitif" class="text-xs text-gray-400">Décision prise</span>

                  </div>
                </td>
              </tr>
              <tr v-if="applications.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                  Aucune candidature
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const route = useRoute()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Recruteur'
})

const loading = ref(true)
const error = ref('')
const applications = ref([])
const searchQuery = ref('')
const selectedStatus = ref('')

const myJobsCount = ref(0)
const candidatesCount = computed(() => applications.value.length)
const messagesCount = ref(0)

let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchCandidates(), 400)
}

const getAvatar = (app) => {
  const name = `${app.candidat?.utilisateur?.prenom || ''}+${app.candidat?.utilisateur?.nom || ''}`.trim() || 'User'
  return `https://ui-avatars.com/api/?name=${name}&background=2563eb&color=fff&size=36`
}

const fetchCandidates = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {}
    if (searchQuery.value) params.recherche = searchQuery.value
    if (selectedStatus.value) params.statut = selectedStatus.value
    if (route.query.offre) params.id_offre = route.query.offre

    const response = await api.get('/recruteur/candidatures', { params })
    applications.value = response.data.data || response.data.candidatures || response.data || []
  } catch (err) {
    console.error('Erreur candidatures:', err)
    if (err.response?.status === 401) {
      error.value = 'Session expirée.'
    } else if (err.code === 'ERR_NETWORK') {
      error.value = 'Impossible de contacter le serveur.'
    } else {
      error.value = err.response?.data?.message || 'Erreur lors du chargement.'
    }
  } finally {
    loading.value = false
  }
}

// RG32 : les transitions autorisées viennent de l'API (statuts_possibles),
// le cycle de vie n'est pas réimplémenté ici.
const prochainStatut = (app) =>
  (app.statuts_possibles || []).find(s => s.valeur !== 'refusee')?.valeur

const peutRefuser = (app) =>
  (app.statuts_possibles || []).some(s => s.valeur === 'refusee')

const changerStatut = async (app, newStatus) => {
  if (!confirm(`Faire passer le statut à "${formatStatus(newStatus)}" ?`)) return

  try {
    const response = await api.patch(`/recruteur/candidatures/${app.id_candidature}/statut`, {
      statut: newStatus,
    })
    // La réponse porte le nouveau statut et les transitions encore possibles.
    Object.assign(app, response.data.candidature)
  } catch (err) {
    alert('❌ ' + (err.response?.data?.message || 'Erreur lors de la mise à jour.'))
  }
}

const advanceStatus = (app) => {
  const newStatus = prochainStatut(app)
  if (!newStatus) {
    alert('Ce dossier est déjà finalisé.')
    return
  }
  changerStatut(app, newStatus)
}

const refuser = (app) => changerStatut(app, 'refusee')

const formatStatus = (status) => {
  const labels = {
    'en_attente': 'En attente',
    'en_cours': 'En cours',
    'preselectionnee': 'Présélectionnée',
    'entretien': 'Entretien',
    'acceptee': 'Acceptée',
    'refusee': 'Refusée',
  }
  return labels[status] || status || 'N/A'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    })
  } catch { return date }
}

const getStatusBadge = (status) => {
  const badges = {
    'en_attente': 'bg-blue-100 text-blue-700',
    'en_cours': 'bg-amber-100 text-amber-700',
    'preselectionnee': 'bg-indigo-100 text-indigo-700',
    'entretien': 'bg-purple-100 text-purple-700',
    'acceptee': 'bg-green-100 text-green-700',
    'refusee': 'bg-red-100 text-red-700',
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}

const getScoreColor = (score) => {
  if (score >= 80) return '#10b981'
  if (score >= 60) return '#f59e0b'
  return '#ef4444'
}

const getScoreTextColor = (score) => {
  if (score >= 80) return 'text-green-600'
  if (score >= 60) return 'text-amber-600'
  return 'text-red-600'
}

onMounted(() => {
  fetchCandidates()
})

// Export CSV des candidatures affichées (séparateur « ; » pour Excel en français).
const exporterCsv = () => {
  const echapper = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`
  const lignes = [
    ['Candidat', 'Email', 'Offre', 'Score IA', 'Statut', 'Date'],
    ...applications.value.map(a => [
      a.candidat?.utilisateur?.nom_complet,
      a.candidat?.utilisateur?.email,
      a.offre?.titre,
      a.score_final ?? '',
      a.statut_libelle,
      a.date_candidature,
    ]),
  ].map(l => l.map(echapper).join(';'))
  const blob = new Blob(['\ufeff' + lignes.join('\r\n')], { type: 'text/csv;charset=utf-8' })
  const lien = document.createElement('a')
  lien.href = URL.createObjectURL(blob)
  lien.download = 'candidatures.csv'
  lien.click()
  URL.revokeObjectURL(lien.href)
}
</script>