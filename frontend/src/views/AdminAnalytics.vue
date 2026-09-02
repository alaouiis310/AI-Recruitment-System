<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Analytiques" 
    page-subtitle="Visualisez les performances de votre plateforme"
  >
    <!-- Header Actions -->
    <template #header-actions>
      <div class="flex items-center gap-3">
        <select class="px-4 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-sm">
          <option>Cette année</option>
          <option>Ce mois</option>
          <option>Cette semaine</option>
        </select>
        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
          Exporter PDF
        </button>
      </div>
    </template>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
      <StatCard 
        label="Taux de conversion" 
        value="18.2%" 
        icon="📊" 
        icon-bg="bg-blue-50" 
        trend="+2.1%" 
        trend-label="ce mois" 
      />
      <StatCard 
        label="Temps moyen de recrutement" 
        value="12.4j" 
        icon="⏱️" 
        icon-bg="bg-purple-50" 
        trend="-1.2j" 
        trend-label="ce mois" 
        :trend-positive="false" 
      />
      <StatCard 
        label="Candidats par offre" 
        value="28.5" 
        icon="👥" 
        icon-bg="bg-amber-50" 
        trend="+3.2" 
        trend-label="ce mois" 
      />
      <StatCard 
        label="Précision IA" 
        value="94.2%" 
        icon="🎯" 
        icon-bg="bg-green-50" 
        trend="+1.8%" 
        trend-label="ce mois" 
      />
    </div>

    <!-- Graphiques -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Graphique 1 -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Évolution des candidatures</h3>
        <div class="h-48 flex items-end gap-2">
          <div v-for="(item, index) in applicationsChart" :key="index" class="flex-1 flex flex-col items-center gap-2">
            <div class="w-full rounded-lg transition-all duration-500" :style="{ height: item.height + '%', background: '#3b82f6' }"></div>
            <span class="text-xs text-gray-400">{{ item.label }}</span>
          </div>
        </div>
      </div>

      <!-- Graphique 2 -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Sources des candidats</h3>
        <div class="space-y-3">
          <div v-for="source in sources" :key="source.label" class="flex items-center gap-3">
            <span class="text-sm text-gray-600 w-24">{{ source.label }}</span>
            <div class="flex-1 bg-gray-200 rounded-full h-2">
              <div class="h-2 rounded-full" :style="{ width: source.percentage + '%', background: source.color }"></div>
            </div>
            <span class="text-sm font-medium text-gray-800 w-12 text-right">{{ source.percentage }}%</span>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import StatCard from '../components/StatCard.vue'

const userName = ref('Admin')

const applicationsChart = ref([
  { label: 'Jan', height: 65 },
  { label: 'Fév', height: 75 },
  { label: 'Mar', height: 55 },
  { label: 'Avr', height: 85 },
  { label: 'Mai', height: 70 },
  { label: 'Juin', height: 90 },
  { label: 'Juil', height: 80 },
])

const sources = ref([
  { label: 'Site carrière', percentage: 45, color: '#3b82f6' },
  { label: 'LinkedIn', percentage: 25, color: '#7c3aed' },
  { label: 'Indeed', percentage: 15, color: '#059669' },
  { label: 'Cooptation', percentage: 10, color: '#d97706' },
  { label: 'Autres', percentage: 5, color: '#6b7280' },
])
</script>