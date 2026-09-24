<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Tableau de bord" 
    page-subtitle="Suivez l'état de vos candidatures"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
      
      <div class="border-t border-gray-100 my-3"></div>

      <SidebarItem to="/candidate/ai-helper" icon="🤖" label="AI Helper" />
      
      <div class="border-t border-gray-100 my-3"></div>

      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
    </template>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-gray-500 mt-4 text-sm">Chargement du tableau de bord...</p>
      </div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-4xl mb-2">⚠️</p>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="fetchDashboard" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all">
        Réessayer
      </button>
    </div>

    <!-- Contenu -->
    <div v-else>
      <!-- Statistiques -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4 mb-6">
        <StatCard 
          label="Candidatures" 
          :value="applicationsCount" 
          icon="📝" 
          icon-bg="bg-blue-50" 
        />
        <StatCard 
          label="En cours" 
          :value="stats.en_cours || 0" 
          icon="🔄" 
          icon-bg="bg-amber-50" 
        />
        <StatCard 
          label="Entretiens" 
          :value="interviewsCount" 
          icon="🗓️" 
          icon-bg="bg-purple-50" 
        />
        <StatCard 
          label="Acceptées" 
          :value="stats.acceptees || 0" 
          icon="🎯" 
          icon-bg="bg-green-50" 
        />
        <StatCard 
          label="Refusées" 
          :value="stats.refusees || 0" 
          icon="❌" 
          icon-bg="bg-red-50" 
        />
      </div>

      <!-- Candidatures récentes -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Mes candidatures récentes</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 text-left">
                <th class="pb-2 font-medium text-gray-500">Offre</th>
                <th class="pb-2 font-medium text-gray-500">Entreprise</th>
                <th class="pb-2 font-medium text-gray-500">Statut</th>
                <th class="pb-2 font-medium text-gray-500">Date</th>
                <th class="pb-2 font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="app in recentApplications" :key="app.id_candidature" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                <td class="py-3 font-medium text-gray-800">{{ app.offre?.titre || app.job || 'N/A' }}</td>
                <td class="py-3 text-gray-600">{{ app.offre?.departement?.entreprise?.nom || app.company || 'N/A' }}</td>
                <td class="py-3">
                  <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusColor(app.statut || app.status)]">
                    {{ formatStatus(app.statut || app.status) }}
                  </span>
                </td>
                <td class="py-3 text-gray-500">{{ formatDate(app.date_candidature || app.date) }}</td>
                <td class="py-3">
                  <router-link to="/candidate/applications" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                    Voir →
                  </router-link>
                </td>
              </tr>
              <tr v-if="recentApplications.length === 0">
                <td colspan="5" class="py-8 text-center text-gray-400">
                  Aucune candidature pour le moment
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Prochain entretien & Offres recommandées -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Prochain entretien -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-3">🗓️ Prochain entretien</h3>
          
          <div v-if="nextInterview" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4">
            <p class="font-semibold text-gray-800">{{ nextInterview.candidature?.offre?.titre || 'Entretien' }}</p>
            <p class="text-sm text-gray-600">{{ nextInterview.candidature?.offre?.departement?.entreprise?.nom || '' }}</p>
            <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-600">
              <span>📅 {{ formatDate(nextInterview.date) }} à {{ nextInterview.heure }}</span>
              <span>📍 {{ formatMode(nextInterview.mode) }}</span>
              <span v-if="nextInterview.recruteur">👤 {{ nextInterview.recruteur }}</span>
            </div>
            <router-link to="/candidate/interviews" class="inline-block mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all">
              Voir les détails
            </router-link>
          </div>

          <div v-else class="bg-gray-50 rounded-xl p-6 text-center">
            <p class="text-4xl mb-2">🗓️</p>
            <p class="text-sm text-gray-500">Aucun entretien planifié</p>
          </div>
        </div>

        <!-- Offres recommandées -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-3">✨ Offres recommandées</h3>
          <div class="space-y-3">
            <div 
              v-for="job in recommendedJobs" 
              :key="job.id_offre" 
              class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all"
            >
              <div>
                <p class="text-sm font-medium text-gray-800">{{ job.titre }}</p>
                <p class="text-xs text-gray-500">{{ job.localisation }}</p>
              </div>
              <router-link to="/candidate/jobs" class="text-xs font-medium text-blue-600 hover:text-blue-700">
                Postuler →
              </router-link>
            </div>
            <div v-if="recommendedJobs.length === 0" class="text-center py-4">
              <p class="text-sm text-gray-400">Aucune recommandation</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Profil Completion & Activité récente -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <!-- Profil Completion -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-3">📊 Complétez votre profil</h3>
          <div class="flex items-center gap-4">
            <div class="relative w-16 h-16 shrink-0">
              <svg class="w-16 h-16" viewBox="0 0 36 36">
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                <path 
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                  fill="none" 
                  stroke="#3b82f6" 
                  stroke-width="3" 
                  :stroke-dasharray="`${profileCompletion} 100`"
                />
              </svg>
              <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-800">
                {{ profileCompletion }}%
              </span>
            </div>
            <div>
              <p class="text-sm text-gray-600">Plus votre profil est complet, plus vous avez de chances d'être repéré.</p>
              <router-link to="/candidate/profile" class="mt-2 inline-block text-sm font-medium text-blue-600 hover:text-blue-700">
                Compléter maintenant →
              </router-link>
            </div>
          </div>
        </div>

        <!-- Activité récente -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-3">🔄 Activité récente</h3>
          <div class="space-y-3">
            <div v-for="(activity, index) in recentActivities" :key="index" class="flex items-start gap-3 text-sm">
              <span class="text-lg shrink-0">{{ activity.icon }}</span>
              <div>
                <p class="text-gray-700">{{ activity.text }}</p>
                <p class="text-xs text-gray-400">{{ activity.time }}</p>
              </div>
            </div>
            <div v-if="recentActivities.length === 0" class="text-center py-4">
              <p class="text-sm text-gray-400">Aucune activité récente</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import StatCard from '../components/StatCard.vue'
import SidebarItem from '../components/SidebarItem.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

// ✅ Store d'authentification
const authStore = useAuthStore()

// ✅ Nom de l'utilisateur
const userName = computed(() => {
  const user = authStore.user
  if (user?.prenom) {
    return `${user.prenom} ${user.nom || ''}`.trim()
  }
  return 'Candidat'
})

// ✅ État réactif
const loading = ref(true)
const error = ref('')
const stats = ref({})
const recentApplications = ref([])
const recommendedJobs = ref([])
const nextInterview = ref(null)
const recentActivities = ref([])
const profileCompletion = ref(0)

// ✅ Compteurs pour les badges du menu
const applicationsCount = ref(0)
const jobsCount = ref(0)
const interviewsCount = ref(0)
const savedCount = ref(0)

// ✅ Récupérer les données du tableau de bord
const fetchDashboard = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/candidat/tableau-de-bord')
    const data = response.data

    // Statistiques
    stats.value = data.statistiques || data.stats || {}
    applicationsCount.value = stats.value.candidatures || 0
    interviewsCount.value = stats.value.entretiens_a_venir || 0

    // Candidatures récentes
    recentApplications.value = data.candidatures_recentes || data.candidatures || []

    // Offres recommandées
    recommendedJobs.value = data.offres_recentes || []

    // Prochain entretien
    nextInterview.value = data.entretiens_a_venir?.[0] || null

    // Activité récente
    recentActivities.value = data.activite_recente || []

    // Complétion du profil
    // Taux de complétion calculé sur les champs réels du profil candidat.
    const profil = authStore.user?.profil_candidat
    if (profil) {
      const champs = ['telephone', 'adresse', 'date_naissance', 'diplome', 'cv_pdf', 'photo', 'github', 'linkedin']
      profileCompletion.value = Math.round(champs.filter(c => profil[c]).length / champs.length * 100)
    }

  } catch (err) {
    console.error('Erreur dashboard:', err)

    if (err.response?.status === 401) {
      error.value = 'Session expirée. Veuillez vous reconnecter.'
    } else if (err.response?.status === 403) {
      error.value = 'Accès non autorisé.'
    } else if (err.code === 'ERR_NETWORK') {
      error.value = 'Impossible de contacter le serveur. Vérifiez que le backend est démarré.'
    } else {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des données.'
    }
  } finally {
    loading.value = false
  }
}

// ✅ Utilitaires d'affichage
const getStatusColor = (status) => {
  const colors = {
    'en_attente': 'bg-blue-100 text-blue-700',
    'En attente': 'bg-blue-100 text-blue-700',
    'en_cours': 'bg-amber-100 text-amber-700',
    'En cours': 'bg-amber-100 text-amber-700',
    'preselectionnee': 'bg-purple-100 text-purple-700',
    'Présélectionnée': 'bg-purple-100 text-purple-700',
    'entretien': 'bg-purple-100 text-purple-700',
    'Entretien': 'bg-purple-100 text-purple-700',
    'acceptee': 'bg-green-100 text-green-700',
    'Acceptée': 'bg-green-100 text-green-700',
    'offre_recue': 'bg-green-100 text-green-700',
    'Offre reçue': 'bg-green-100 text-green-700',
    'refusee': 'bg-red-100 text-red-700',
    'Refusée': 'bg-red-100 text-red-700',
  }
  return colors[status] || 'bg-gray-100 text-gray-700'
}

const formatStatus = (status) => {
  if (!status) return 'N/A'
  const labels = {
    'en_attente': 'En attente',
    'en_cours': 'En cours',
    'preselectionnee': 'Présélectionnée',
    'entretien': 'Entretien',
    'acceptee': 'Acceptée',
    'offre_recue': 'Offre reçue',
    'refusee': 'Refusée',
  }
  return labels[status] || status
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    })
  } catch {
    return date
  }
}

const formatMode = (mode) => {
  const modes = {
    'presentiel': 'Présentiel',
    'visio': 'Visioconférence',
    'telephone': 'Téléphone'
  }
  return modes[mode] || mode || 'N/A'
}

// ✅ Charger au montage
onMounted(() => {
  fetchDashboard()
})
</script>