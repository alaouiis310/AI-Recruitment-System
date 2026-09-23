<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Analytiques" 
    page-subtitle="Visualisez les performances de votre plateforme"
  >
    <template #header-actions>
      <div class="flex items-center gap-3">
        <select class="px-4 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-sm">
          <option>Cette année</option>
          <option>Ce mois</option>
          <option>Cette semaine</option>
        </select>
        <button @click="imprimer" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
          Exporter PDF
        </button>
      </div>
    </template>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-4xl mb-2">⚠️</p>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="fetchAnalytics" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else>
      <!-- Statistiques -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
        <StatCard 
          label="Taux de conversion" 
          :value="(stats.taux_conversion || 0) + '%'" 
          icon="📊" 
          icon-bg="bg-blue-50" 
        />
        <StatCard 
          label="Délai moyen de décision" 
          :value="(stats.temps_moyen_recrutement || 0) + 'j'" 
          icon="⏱️" 
          icon-bg="bg-purple-50" 
        />
        <StatCard 
          label="Candidats par offre" 
          :value="stats.candidats_par_offre || 0" 
          icon="👥" 
          icon-bg="bg-amber-50" 
        />
        <StatCard 
          label="Score moyen IA" 
          :value="Math.round(stats.score_moyen || 0) + '/100'" 
          icon="🎯" 
          icon-bg="bg-green-50" 
        />
      </div>

      <!-- Graphiques -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Évolution candidatures -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-4">Évolution des candidatures</h3>
          <div class="h-48 flex items-end gap-2">
            <div v-for="(item, index) in applicationsChart" :key="index" class="flex-1 flex flex-col items-center gap-2">
              <div 
                class="w-full rounded-lg transition-all duration-500" 
                :style="{ height: item.height + '%', background: '#3b82f6' }"
              ></div>
              <span class="text-xs text-gray-400">{{ item.label }}</span>
            </div>
          </div>
        </div>

        <!-- Sources -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800 mb-4">Candidatures par statut</h3>
          <div class="space-y-3">
            <div v-for="source in sources" :key="source.label" class="flex items-center gap-3">
              <span class="text-sm text-gray-600 w-32">{{ source.label }}</span>
              <div class="flex-1 bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full" :style="{ width: source.percentage + '%', background: source.color }"></div>
              </div>
              <span class="text-sm font-medium text-gray-800 w-12 text-right">{{ source.percentage }}%</span>
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

const applicationsChart = ref([])

const sources = ref([])

const MOIS = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc']

const fetchAnalytics = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/analytiques')
    const data = response.data
    // L'API regroupe ses statistiques par domaine : on les ramène aux
    // clés lues par les cartes.
    const s = data.statistiques || {}
    const c = s.candidatures || {}
    stats.value = {
      taux_conversion: c.taux_conversion ?? 0,
      temps_moyen_recrutement: c.delai_moyen_decision_jours ?? 0,
      candidats_par_offre: s.offres?.total ? Math.round((c.total || 0) / s.offres.total * 10) / 10 : 0,
      score_moyen: s.traitements?.score_moyen ?? 0,
    }

    const evolution = s.evolution_candidatures || []
    const max = Math.max(1, ...evolution.map(e => e.total))
    applicationsChart.value = evolution.map(e => ({
      label: MOIS[Number(e.mois.slice(5)) - 1],
      height: e.total ? Math.max(6, Math.round(e.total / max * 100)) : 2,
    }))

    // Répartition réelle des candidatures par statut (RG32). Le MLD ne
    // trace pas la provenance des candidats : aucune « source » n'est inventée.
    const total = c.total || 0
    sources.value = [
      { label: 'En attente', n: c.en_attente, color: '#3b82f6' },
      { label: 'En cours', n: c.en_cours, color: '#d97706' },
      { label: 'Présélectionnées', n: c.preselectionnees, color: '#7c3aed' },
      { label: 'Acceptées', n: c.acceptees, color: '#059669' },
      { label: 'Refusées', n: c.refusees, color: '#dc2626' },
    ].map(x => ({ ...x, percentage: total ? Math.round((x.n || 0) / total * 100) : 0 }))
  } catch (err) {
    console.error('Erreur analytiques:', err)
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
  fetchAnalytics()
})

// Export PDF : la boîte d'impression du navigateur propose « Enregistrer en PDF ».
const imprimer = () => window.print()
</script>