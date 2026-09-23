<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Tableau de bord" 
    page-subtitle="Vue d'ensemble de vos recrutements"
  >
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <div class="border-t border-gray-100 my-3"></div>
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
      <div class="border-t border-gray-100 my-3"></div>
      <SidebarItem to="/recruiter/profile" icon="👤" label="Mon profil" />
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
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
        <StatCard 
          label="Nouvelles candidatures" 
          :value="stats.en_attente || 0" 
          icon="📥" 
          icon-bg="bg-blue-50" 
          trend="+5" 
          trend-label="cette semaine" 
        />
        <StatCard 
          label="Entretiens à venir" 
          :value="stats.entretiens_a_venir || 0" 
          icon="🗓️" 
          icon-bg="bg-purple-50" 
          trend="+2" 
          trend-label="cette semaine" 
        />
        <StatCard 
          label="Shortlist" 
          :value="stats.preselectionnees || 0" 
          icon="⭐" 
          icon-bg="bg-amber-50" 
          trend="+3" 
          trend-label="nouveaux" 
        />
        <StatCard 
          label="Embauchés" 
          :value="stats.acceptees || 0" 
          icon="🎯" 
          icon-bg="bg-green-50" 
          trend="+1" 
          trend-label="ce mois" 
        />
      </div>

      <!-- Pipeline & Offres -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Pipeline -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-4">Pipeline de recrutement</h3>
          <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
            <div v-for="stage in pipelineStages" :key="stage.label" class="text-center">
              <div class="text-2xl font-bold text-gray-800">{{ stage.count }}</div>
              <div class="text-xs text-gray-500">{{ stage.label }}</div>
              <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                <div class="h-1.5 rounded-full" :style="{ width: stage.percentage + '%', background: stage.color }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Candidatures récentes -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-3">Dernières candidatures</h3>
          <div class="space-y-3">
            <div 
              v-for="app in recentApplications" 
              :key="app.id_candidature" 
              class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
            >
              <div>
                <p class="text-sm font-medium text-gray-800">
                  {{ app.candidat?.utilisateur?.prenom }} {{ app.candidat?.utilisateur?.nom }}
                </p>
                <p class="text-xs text-gray-500">{{ app.offre?.titre }}</p>
              </div>
              <span 
                v-if="app.score_final != null"
                class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-lg"
              >
                {{ Math.round(app.score_final) }}%
              </span>
            </div>
            <p v-if="recentApplications.length === 0" class="text-center text-sm text-gray-400 py-3">
              Aucune candidature
            </p>
          </div>
          <router-link to="/recruiter/candidates" class="block w-full mt-3 text-center text-sm text-blue-600 font-medium hover:text-blue-700">
            Voir toutes les candidatures →
          </router-link>
        </div>
      </div>

      <!-- Offres & Tâches -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Mes offres récentes -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-3">💼 Mes offres récentes</h3>
          <div class="space-y-3">
            <div 
              v-for="job in recentJobs" 
              :key="job.id_offre" 
              class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
            >
              <div>
                <p class="text-sm font-medium text-gray-800">{{ job.titre }}</p>
                <p class="text-xs text-gray-500">{{ job.localisation }}</p>
              </div>
              <span class="text-xs font-medium text-gray-500">{{ job.nombre_candidatures || 0 }} cand.</span>
            </div>
            <p v-if="recentJobs.length === 0" class="text-center text-sm text-gray-400 py-3">
              Aucune offre publiée
            </p>
          </div>
          <router-link to="/recruiter/jobs" class="block w-full mt-3 text-center text-sm text-blue-600 font-medium hover:text-blue-700">
            Voir toutes mes offres →
          </router-link>
        </div>

        <!-- Entretiens à venir -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-3">🗓️ Prochains entretiens</h3>
          <div class="space-y-3">
            <div 
              v-for="interview in upcomingInterviews" 
              :key="interview.id_entretien" 
              class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl"
            >
              <div class="text-center shrink-0">
                <p class="text-lg font-bold text-blue-600">
                  {{ new Date(interview.date).getDate() }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ new Date(interview.date).toLocaleDateString('fr-FR', { month: 'short' }) }}
                </p>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">
                  {{ interview.candidature?.offre?.titre || 'Entretien' }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ interview.heure }} • {{ formatMode(interview.mode) }}
                </p>
              </div>
            </div>
            <p v-if="upcomingInterviews.length === 0" class="text-center text-sm text-gray-400 py-3">
              Aucun entretien planifié
            </p>
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

const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Recruteur'
})

const loading = ref(true)
const error = ref('')
const stats = ref({})
const recentApplications = ref([])
const recentJobs = ref([])
const upcomingInterviews = ref([])

const myJobsCount = ref(0)
const candidatesCount = ref(0)
const messagesCount = ref(0)

const pipelineStages = computed(() => {
  const s = stats.value
  const total = (s.nouvelles_candidatures || 0) + (s.en_cours || 0) + (s.entretiens_a_venir || 0) + (s.offres || 0) + (s.embauches || 0)
  const max = total || 1
  return [
    { label: 'Nouveaux', count: s.nouvelles_candidatures || 0, percentage: ((s.nouvelles_candidatures || 0) / max) * 100, color: '#3b82f6' },
    { label: 'En cours', count: s.en_cours || 0, percentage: ((s.en_cours || 0) / max) * 100, color: '#60a5fa' },
    { label: 'Entretien', count: s.entretiens_a_venir || 0, percentage: ((s.entretiens_a_venir || 0) / max) * 100, color: '#f59e0b' },
    { label: 'Offre', count: s.offres || 0, percentage: ((s.offres || 0) / max) * 100, color: '#10b981' },
    { label: 'Embauchés', count: s.embauches || 0, percentage: ((s.embauches || 0) / max) * 100, color: '#8b5cf6' },
  ]
})

const fetchDashboard = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/recruteur/tableau-de-bord')
    const data = response.data

    stats.value = data.statistiques || data.stats || {}
    recentApplications.value = data.candidatures_recentes || []
    recentJobs.value = data.offres_recentes || []
    upcomingInterviews.value = data.entretiens_a_venir || []

    myJobsCount.value = stats.value.offres || recentJobs.value.length
    candidatesCount.value = stats.value.candidatures || recentApplications.value.length

  } catch (err) {
    console.error('Erreur dashboard:', err)
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

const formatMode = (mode) => {
  const modes = { 'presentiel': 'Présentiel', 'visio': 'Visio', 'telephone': 'Tél.' }
  return modes[mode] || mode || 'N/A'
}

onMounted(() => {
  fetchDashboard()
})
</script>