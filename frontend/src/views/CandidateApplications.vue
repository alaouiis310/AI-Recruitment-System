<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Mes candidatures" 
    page-subtitle="Suivez l'état de vos candidatures"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" active />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
    </template>

    <!-- Header Actions -->
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Nouvelle candidature
      </button>
    </template>

    <!-- Filtres -->
    <div class="flex flex-wrap items-center gap-3 mb-6">
      <button 
        v-for="filter in filters" 
        :key="filter.label"
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

    <!-- Liste des candidatures -->
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
            <tr v-for="app in filteredApplications" :key="app.job" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4 font-medium text-gray-800">{{ app.job }}</td>
              <td class="px-6 py-4 text-gray-600">{{ app.company }}</td>
              <td class="px-6 py-4">
                <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusColor(app.status)]">
                  {{ app.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500">{{ app.date }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-16 bg-gray-200 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full" :style="{ width: app.score + '%', background: getScoreColor(app.score) }"></div>
                  </div>
                  <span class="text-xs font-medium" :class="getScoreTextColor(app.score)">{{ app.score }}%</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir →</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-sm">
        <p class="text-gray-500">Affichage de {{ filteredApplications.length }} candidatures</p>
        <div class="flex gap-2">
          <button class="px-3 py-1 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors disabled:opacity-50" disabled>Précédent</button>
          <button class="px-3 py-1 rounded-lg bg-blue-600 text-white">1</button>
          <button class="px-3 py-1 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">2</button>
          <button class="px-3 py-1 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">3</button>
          <button class="px-3 py-1 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">Suivant</button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Yassine')
const applicationsCount = ref(12)
const jobsCount = ref(24)
const interviewsCount = ref(2)
const savedCount = ref(5)
const activeFilter = ref('all')

const filters = ref([
  { label: 'Toutes', value: 'all', count: 12 },
  { label: 'En cours', value: 'en cours', count: 5 },
  { label: 'Entretien', value: 'entretien', count: 2 },
  { label: 'Offre reçue', value: 'offre reçue', count: 1 },
  { label: 'Refusée', value: 'refusée', count: 4 },
])

const applications = ref([
  { job: 'Développeur Full Stack', company: 'TechNova', status: 'En cours', date: '20 mai 2024', score: 85 },
  { job: 'Data Scientist', company: 'DataMind', status: 'Entretien', date: '18 mai 2024', score: 92 },
  { job: 'UX/UI Designer', company: 'DesignLab', status: 'Offre reçue', date: '15 mai 2024', score: 78 },
  { job: 'Product Owner', company: 'Innovatech', status: 'En cours', date: '12 mai 2024', score: 71 },
  { job: 'DevOps Engineer', company: 'CloudSys', status: 'Refusée', date: '10 mai 2024', score: 55 },
  { job: 'Frontend Developer', company: 'WebCorp', status: 'En cours', date: '08 mai 2024', score: 88 },
  { job: 'Data Analyst', company: 'DataMind', status: 'Entretien', date: '05 mai 2024', score: 76 },
  { job: 'Backend Developer', company: 'TechNova', status: 'Refusée', date: '02 mai 2024', score: 62 },
])

const filteredApplications = computed(() => {
  if (activeFilter.value === 'all') return applications.value
  return applications.value.filter(app => app.status.toLowerCase() === activeFilter.value)
})

const getStatusColor = (status) => {
  const colors = {
    'En cours': 'bg-blue-100 text-blue-700',
    'Entretien': 'bg-purple-100 text-purple-700',
    'Offre reçue': 'bg-green-100 text-green-700',
    'Refusée': 'bg-red-100 text-red-700'
  }
  return colors[status] || 'bg-gray-100 text-gray-700'
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
</script>