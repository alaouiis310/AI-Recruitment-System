<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Mes candidatures" 
    page-subtitle="Suivez l'état de vos candidatures"
  >
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
    </template>

    <template #header-actions>
      <router-link 
        to="/candidate/jobs" 
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md"
      >
        + Nouvelle candidature
      </router-link>
    </template>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-gray-500 mt-4 text-sm">Chargement de vos candidatures...</p>
      </div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-4xl mb-2">⚠️</p>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="fetchApplications" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all">
        Réessayer
      </button>
    </div>

    <!-- Contenu -->
    <div v-else>
      <!-- Filtres -->
      <div class="flex flex-wrap items-center gap-3 mb-6">
        <button 
          v-for="filter in filters" 
          :key="filter.value"
          @click="activeFilter = filter.value"
          :class="[
            'px-4 py-2 rounded-xl text-sm font-medium transition-all',
            activeFilter === filter.value 
              ? 'bg-blue-600 text-white shadow-sm' 
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          {{ filter.label }}
          <span class="ml-1 text-xs opacity-70">({{ filter.count }})</span>
        </button>
      </div>

      <!-- Liste -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-left">
                <th class="px-6 py-3 font-medium text-gray-500">Offre</th>
                <th class="px-6 py-3 font-medium text-gray-500">Entreprise</th>
                <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
                <th class="px-6 py-3 font-medium text-gray-500">Date</th>
                <th class="px-6 py-3 font-medium text-gray-500">Score IA</th>
                <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="app in filteredApplications" 
                :key="app.id" 
                class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-6 py-4 font-medium text-gray-800">
                  {{ app.offre?.titre || 'N/A' }}
                </td>
                <td class="px-6 py-4 text-gray-600">
                  {{ app.offre?.entreprise?.nom || 'N/A' }}
                </td>
                <td class="px-6 py-4">
                  <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusColor(app.statut)]">
                    {{ formatStatus(app.statut) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-500">
                  {{ formatDate(app.date_candidature) }}
                </td>
                <td class="px-6 py-4">
                  <div v-if="app.analyse_ia?.score_matching" class="flex items-center gap-2">
                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                      <div 
                        class="h-1.5 rounded-full" 
                        :style="{ 
                          width: app.analyse_ia.score_matching + '%', 
                          background: getScoreColor(app.analyse_ia.score_matching) 
                        }"
                      ></div>
                    </div>
                    <span 
                      class="text-xs font-medium" 
                      :class="getScoreTextColor(app.analyse_ia.score_matching)"
                    >
                      {{ Math.round(app.analyse_ia.score_matching) }}%
                    </span>
                  </div>
                  <span v-else class="text-xs text-gray-400">Non analysé</span>
                </td>
                <td class="px-6 py-4">
                  <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                    Voir →
                  </button>
                </td>
              </tr>
              <tr v-if="filteredApplications.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                  Aucune candidature pour le moment
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Résumé -->
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-sm">
          <p class="text-gray-500">
            Affichage de {{ filteredApplications.length }} candidature(s)
          </p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Candidat'
})

const loading = ref(true)
const error = ref('')
const applications = ref([])
const activeFilter = ref('all')

const applicationsCount = computed(() => applications.value.length)
const jobsCount = ref(0)
const interviewsCount = computed(() => 
  applications.value.filter(a => a.statut === 'entretien').length
)
const savedCount = ref(0)

const filters = computed(() => [
  { label: 'Toutes', value: 'all', count: applications.value.length },
  { label: 'En attente', value: 'en_attente', count: applications.value.filter(a => a.statut === 'en_attente').length },
  { label: 'En cours', value: 'en_cours', count: applications.value.filter(a => a.statut === 'en_cours').length },
  { label: 'Entretien', value: 'entretien', count: applications.value.filter(a => a.statut === 'entretien').length },
  { label: 'Acceptée', value: 'acceptee', count: applications.value.filter(a => a.statut === 'acceptee').length },
  { label: 'Refusée', value: 'refusee', count: applications.value.filter(a => a.statut === 'refusee').length },
])

const filteredApplications = computed(() => {
  if (activeFilter.value === 'all') return applications.value
  return applications.value.filter(app => app.statut === activeFilter.value)
})

const fetchApplications = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/candidat/candidatures')
    applications.value = response.data.data || response.data.candidatures || response.data || []
  } catch (err) {
    console.error('Erreur candidatures:', err)
    if (err.response?.status === 401) {
      error.value = 'Session expirée. Veuillez vous reconnecter.'
    } else if (err.code === 'ERR_NETWORK') {
      error.value = 'Impossible de contacter le serveur.'
    } else {
      error.value = err.response?.data?.message || 'Erreur lors du chargement.'
    }
  } finally {
    loading.value = false
  }
}

const getStatusColor = (status) => {
  const colors = {
    'en_attente': 'bg-blue-100 text-blue-700',
    'en_cours': 'bg-amber-100 text-amber-700',
    'preselectionnee': 'bg-purple-100 text-purple-700',
    'entretien': 'bg-purple-100 text-purple-700',
    'acceptee': 'bg-green-100 text-green-700',
    'refusee': 'bg-red-100 text-red-700',
  }
  return colors[status] || 'bg-gray-100 text-gray-700'
}

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
  fetchApplications()
})
</script>