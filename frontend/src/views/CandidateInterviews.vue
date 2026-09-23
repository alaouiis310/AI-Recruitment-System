<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Mes entretiens" 
    page-subtitle="Gérez vos entretiens à venir"
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

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-gray-500 mt-4 text-sm">Chargement des entretiens...</p>
      </div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-4xl mb-2">⚠️</p>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="fetchInterviews" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else>
      <!-- Vue d'ensemble -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <p class="text-sm text-gray-500">Entretiens à venir</p>
          <p class="text-2xl font-bold text-gray-800">{{ upcomingInterviews.length }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <p class="text-sm text-gray-500">Entretiens passés</p>
          <p class="text-2xl font-bold text-gray-800">{{ pastInterviews.length }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <p class="text-sm text-gray-500">Total</p>
          <p class="text-2xl font-bold text-blue-600">{{ allInterviews.length }}</p>
        </div>
      </div>

      <!-- Liste -->
      <div class="space-y-4">
        <div 
          v-for="interview in allInterviews" 
          :key="interview.id_entretien" 
          class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all"
        >
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h4 class="font-semibold text-gray-800">
                {{ interview.offre?.titre || interview.candidature?.offre?.titre || 'Entretien' }}
              </h4>
              <p class="text-sm text-gray-500">
                {{ interview.offre?.departement?.entreprise?.nom || interview.candidature?.offre?.departement?.entreprise?.nom || '' }}
              </p>
              <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-600">
                <span>📅 {{ formatDate(interview.date) }}</span>
                <span>•</span>
                <span>⏰ {{ interview.heure }}</span>
                <span>•</span>
                <span>📍 {{ formatMode(interview.mode) }}</span>
              </div>
              <p v-if="interview.commentaire" class="text-sm text-gray-500 mt-2 italic">
                "{{ interview.commentaire }}"
              </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
              <span :class="['text-xs font-medium px-3 py-1 rounded-full', getResultatColor(interview.resultat)]">
                {{ formatResultat(interview.resultat) }}
              </span>
              <a 
                v-if="interview.lien_si_online" 
                :href="interview.lien_si_online" 
                target="_blank"
                class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700"
              >
                Rejoindre
              </a>
            </div>
          </div>
        </div>

        <!-- Aucun entretien -->
        <div v-if="allInterviews.length === 0" class="text-center py-16 bg-white rounded-2xl border border-gray-100">
          <p class="text-6xl mb-4">🗓️</p>
          <h3 class="text-xl font-semibold text-gray-800">Aucun entretien planifié</h3>
          <p class="text-gray-500 mt-2">Vos entretiens apparaîtront ici une fois planifiés.</p>
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
const allInterviews = ref([])

const applicationsCount = ref(0)
const jobsCount = ref(0)
const interviewsCount = computed(() => allInterviews.value.length)
const savedCount = ref(0)

const upcomingInterviews = computed(() => {
  const now = new Date()
  return allInterviews.value.filter(i => new Date(i.date) >= now)
})

const pastInterviews = computed(() => {
  const now = new Date()
  return allInterviews.value.filter(i => new Date(i.date) < now)
})

const fetchInterviews = async () => {
  loading.value = true
  error.value = ''

  try {
    // 1. Récupérer les candidatures
    const appsResponse = await api.get('/candidat/candidatures')
    const applications = appsResponse.data.data || appsResponse.data.candidatures || []
    applicationsCount.value = applications.length

    // 2. Pour chaque candidature, récupérer les entretiens
    const allInterviewsData = []
    
    for (const app of applications) {
      try {
        const interviewsResponse = await api.get(`/candidat/candidatures/${app.id_candidature}/entretiens`)
        const interviews = interviewsResponse.data.data || interviewsResponse.data.entretiens || interviewsResponse.data || []
        
        interviews.forEach(interview => {
          allInterviewsData.push({
            ...interview,
            offre: app.offre,
            candidature: app,
          })
        })
      } catch (e) {
        // Pas d'entretiens pour cette candidature
      }
    }

    allInterviews.value = allInterviewsData.sort((a, b) => new Date(a.date) - new Date(b.date))

  } catch (err) {
    console.error('Erreur entretiens:', err)
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

const formatMode = (mode) => {
  const modes = {
    'presentiel': 'Présentiel',
    'visio': 'Visioconférence',
    'telephone': 'Téléphone'
  }
  return modes[mode] || mode || 'N/A'
}

const formatResultat = (resultat) => {
  const labels = {
    'en_attente': 'En attente',
    'favorable': 'Favorable',
    'defavorable': 'Défavorable',
  }
  return labels[resultat] || 'En attente'
}

const getResultatColor = (resultat) => {
  const colors = {
    'en_attente': 'bg-amber-100 text-amber-700',
    'favorable': 'bg-green-100 text-green-700',
    'defavorable': 'bg-red-100 text-red-700',
  }
  return colors[resultat] || 'bg-amber-100 text-amber-700'
}

onMounted(() => {
  fetchInterviews()
})
</script>