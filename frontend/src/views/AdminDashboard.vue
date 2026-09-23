<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Tableau de bord" 
    page-subtitle="Vue d'ensemble de votre plateforme"
  >
    <template #header-actions>
      <button @click="exporter" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
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
          label="Score moyen IA" 
          :value="Math.round(stats.score_moyen || 0) + '/100'" 
          icon="🤖" 
          icon-bg="bg-cyan-50" 
        />
      </div>

      <!-- Graphique & Activité -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Graphique -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-4">Candidatures reçues sur six mois</h3>
          <div class="h-48 flex items-end gap-2">
            <div v-for="(item, index) in chartData" :key="index" class="flex-1 flex flex-col items-center gap-2">
              <div 
                class="w-full rounded-lg transition-all duration-500 hover:opacity-80" 
                :style="{ height: item.height + '%', background: item.color }"
              ></div>
              <span class="text-xs text-gray-400">{{ item.label }}</span>
            </div>
            <p v-if="chartData.length === 0" class="w-full text-center text-sm text-gray-400 self-center">Aucune donnée.</p>
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

const chartData = ref([])

const legendItems = [
  { label: 'Candidatures déposées par mois', color: '#3b82f6' },
]

const MOIS = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc']
const TEINTES = ['#bfdbfe', '#93c5fd', '#60a5fa', '#3b82f6', '#2563eb', '#1d4ed8']

// Hauteur des barres proportionnelle au mois le plus chargé.
const barresMensuelles = (evolution = []) => {
  const max = Math.max(1, ...evolution.map(e => e.total))
  return evolution.map((e, i) => ({
    label: MOIS[Number(e.mois.slice(5)) - 1],
    height: e.total ? Math.max(6, Math.round(e.total / max * 100)) : 2,
    color: TEINTES[Math.min(i, TEINTES.length - 1)],
  }))
}

const formatNumber = (num) => {
  return new Intl.NumberFormat('fr-FR').format(num)
}

const fetchDashboard = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/tableau-de-bord')
    const data = response.data

    // L'API regroupe ses statistiques par domaine : on les ramène aux
    // clés lues par les cartes.
    const s = data.statistiques || {}
    stats.value = {
      total_candidats: s.utilisateurs?.candidats ?? 0,
      total_recruteurs: s.utilisateurs?.recruteurs ?? 0,
      total_candidatures: s.candidatures?.total ?? 0,
      total_embauches: s.candidatures?.acceptees ?? 0,
      offres_actives: s.offres?.ouvertes ?? 0,
      entretiens_planifies: s.traitements?.entretiens_a_venir ?? 0,
      score_moyen: s.traitements?.score_moyen ?? 0,
    }
    chartData.value = barresMensuelles(s.evolution_candidatures)

    // Activité récente : les dernières candidatures déposées.
    recentActivities.value = (data.candidatures_recentes || []).map(c => ({
      icon: '📝',
      text: `${c.candidat?.utilisateur?.nom_complet || 'Un candidat'} a postulé à « ${c.offre?.titre || 'une offre'} » — ${c.statut_libelle}`,
      time: new Date(c.date_candidature).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }),
    }))
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

// Export des candidatures au format CSV, produit par l'API.
const exporter = async () => {
  try {
    const response = await api.get('/admin/candidatures/export', { responseType: 'blob' })
    const lien = document.createElement('a')
    lien.href = URL.createObjectURL(response.data)
    lien.download = `candidatures-${new Date().toISOString().slice(0, 10)}.csv`
    lien.click()
    URL.revokeObjectURL(lien.href)
  } catch (err) {
    alert("❌ Erreur lors de l'export.")
  }
}
</script>