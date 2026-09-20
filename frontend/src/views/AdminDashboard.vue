<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Tableau de bord" 
    page-subtitle="Vue d'ensemble de votre plateforme"
  >
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Exporter les données
      </button>
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
      <button @click="fetchDashboard" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else>
      <!-- Statistiques -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6 mb-6">
        <StatCard 
          label="Candidats" 
          :value="formatNumber(stats.total_candidats || 0)" 
          icon="🧑‍💻" 
          icon-bg="bg-blue-50" 
          trend="+12.5%" 
          trend-label="ce mois" 
        />
        <StatCard 
          label="Recruteurs" 
          :value="formatNumber(stats.total_recruteurs || 0)" 
          icon="👤" 
          icon-bg="bg-indigo-50" 
          trend="+8.2%" 
          trend-label="ce mois" 
        />
        <StatCard 
          label="Candidatures" 
          :value="formatNumber(stats.total_candidatures || 0)" 
          icon="📝" 
          icon-bg="bg-amber-50" 
          trend="+21%" 
          trend-label="ce mois" 
        />
        <StatCard 
          label="Embauchés" 
          :value="formatNumber(stats.total_embauches || 0)" 
          icon="🎯" 
          icon-bg="bg-green-50" 
          trend="+14%" 
          trend-label="ce mois" 
        />
      </div>

      <!-- Deuxième ligne -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6">
        <StatCard 
          label="Offres actives" 
          :value="stats.offres_actives || 0" 
          icon="💼" 
          icon-bg="bg-emerald-50" 
        />
        <StatCard 
          label="Entretiens planifiés" 
          :value="stats.entretiens_planifies || 0" 
          icon="🗓️" 
          icon-bg="bg-purple-50" 
        />
        <StatCard 
          label="Taux de matching IA" 
          :value="(stats.taux_matching_ia || 94) + '%'" 
          icon="🤖" 
          icon-bg="bg-cyan-50" 
          trend="↑" 
          trend-label="Précision" 
        />
      </div>

      <!-- Graphique & Activité -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Graphique -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-4">Activité recrutement</h3>
          <div class="h-48 flex items-end gap-2">
            <div v-for="(item, index) in chartData" :key="index" class="flex-1 flex flex-col items-center gap-2">
              <div 
                class="w-full rounded-lg transition-all duration-500 hover:opacity-80" 
                :style="{ height: item.height + '%', background: item.color }"
              ></div>
              <span class="text-xs text-gray-400">{{ item.label }}</span>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-4 mt-4 pt-4 border-t border-gray-100">
            <span v-for="(item, index) in legendItems" :key="index" class="flex items-center gap-2 text-xs text-gray-600">
              <span class="w-3 h-3 rounded" :style="{ background: item.color }"></span>
              {{ item.label }}
            </span>
          </div>
        </div>

        <!-- Activité récente -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-4">Activité récente</h3>
          <div class="space-y-4">
            <div v-for="(activity, index) in recentActivities" :key="index" class="flex items-start gap-3 text-sm">
              <span class="text-lg shrink-0">{{ activity.icon }}</span>
              <div>
                <p class="text-gray-800">{{ activity.text }}</p>
                <p class="text-xs text-gray-400">{{ activity.time }}</p>
              </div>
            </div>
            <p v-if="recentActivities.length === 0" class="text-center text-sm text-gray-400 py-3">
              Aucune activité
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
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Admin'
})

const loading = ref(true)
const error = ref('')
const stats = ref({})
const recentActivities = ref([])

const chartData = ref([
  { label: 'Jan', height: 65, color: '#3b82f6' },
  { label: 'Fév', height: 75, color: '#3b82f6' },
  { label: 'Mar', height: 55, color: '#60a5fa' },
  { label: 'Avr', height: 85, color: '#60a5fa' },
  { label: 'Mai', height: 70, color: '#93c5fd' },
  { label: 'Juin', height: 90, color: '#93c5fd' },
  { label: 'Juil', height: 80, color: '#bfdbfe' },
])

const legendItems = [
  { label: 'Nouvelles', color: '#3b82f6' },
  { label: 'En cours', color: '#60a5fa' },
  { label: 'Entretien', color: '#93c5fd' },
  { label: 'Offre', color: '#bfdbfe' },
]

const formatNumber = (num) => {
  return new Intl.NumberFormat('fr-FR').format(num)
}

const fetchDashboard = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/tableau-de-bord')
    const data = response.data

    stats.value = data.statistiques || data.stats || {}
    recentActivities.value = data.activite_recente || []
  } catch (err) {
    console.error('Erreur dashboard:', err)
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

onMounted(() => {
  fetchDashboard()
})
</script>