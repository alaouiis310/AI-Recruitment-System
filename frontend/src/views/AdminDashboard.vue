<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Tableau de bord" 
    page-subtitle="Vue d'ensemble de votre plateforme"
  >
    <!-- Header Actions -->
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Exporter les données
      </button>
    </template>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6 mb-6">
      <StatCard 
        label="Candidats" 
        :value="candidatesCount" 
        icon="🧑‍💻" 
        icon-bg="bg-blue-50" 
        trend="+12.5%" 
        trend-label="ce mois" 
      />
      <StatCard 
        label="Recruteurs" 
        :value="recruitersCount" 
        icon="👤" 
        icon-bg="bg-indigo-50" 
        trend="+8.2%" 
        trend-label="ce mois" 
      />
      <StatCard 
        label="Candidatures" 
        :value="applicationsCount" 
        icon="📝" 
        icon-bg="bg-amber-50" 
        trend="+21%" 
        trend-label="ce mois" 
      />
      <StatCard 
        label="Embauchés" 
        :value="hiredCount" 
        icon="🎯" 
        icon-bg="bg-green-50" 
        trend="+14%" 
        trend-label="ce mois" 
      />
    </div>

    <!-- Deuxième ligne de statistiques -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6">
      <StatCard 
        label="Offres actives" 
        :value="activeJobs" 
        icon="💼" 
        icon-bg="bg-emerald-50" 
        trend="+5" 
        trend-label="nouvelles" 
      />
      <StatCard 
        label="Entretiens planifiés" 
        :value="interviewsCount" 
        icon="🗓️" 
        icon-bg="bg-purple-50" 
        trend="+3" 
        trend-label="cette semaine" 
      />
      <StatCard 
        label="Taux de matching IA" 
        value="94%" 
        icon="🤖" 
        icon-bg="bg-cyan-50" 
        trend="↑" 
        trend-label="Précision" 
      />
    </div>

    <!-- Graphique & Activité récente -->
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
const candidatesCount = ref(1248)
const recruitersCount = ref(86)
const applicationsCount = ref(5426)
const hiredCount = ref(324)
const activeJobs = ref(47)
const interviewsCount = ref(23)

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

const recentActivities = ref([
  { icon: '👤', text: 'Nouveau recruteur ajouté', time: 'Il y a 10 min' },
  { icon: '💼', text: 'Offre publiée - Développeur Full Stack', time: 'Il y a 30 min' },
  { icon: '🧑‍💻', text: '125 nouveaux candidats', time: 'Il y a 1 h' },
  { icon: '🤖', text: 'Mise à jour du modèle IA', time: 'Il y a 2 h' },
])
</script>