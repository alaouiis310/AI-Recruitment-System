<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Tableau de bord" 
    page-subtitle="Suivez l'état de vos candidatures"
  >
    <!-- Menu -->
<!-- Menu -->
<template #menu>
  <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
  <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
  <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
  <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
  <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
  
  <!-- Séparateur -->
  <div class="border-t border-gray-100 my-3"></div>

  <SidebarItem to="/candidate/ai-helper" icon="🤖" label="AI Helper" />
  
  <div class="border-t border-gray-100 my-3"></div>

  <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
  <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
</template>

    <!-- Statistiques -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4 mb-6">
      <StatCard 
        label="Candidatures" 
        value="12" 
        icon="📝" 
        icon-bg="bg-blue-50" 
      />
      <StatCard 
        label="En cours" 
        value="5" 
        icon="🔄" 
        icon-bg="bg-amber-50" 
      />
      <StatCard 
        label="Entretiens" 
        value="2" 
        icon="🗓️" 
        icon-bg="bg-purple-50" 
      />
      <StatCard 
        label="Offres reçues" 
        value="1" 
        icon="🎯" 
        icon-bg="bg-green-50" 
      />
      <StatCard 
        label="Refusées" 
        value="4" 
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
            <tr v-for="app in recentApplications" :key="app.job" class="border-b border-gray-50">
              <td class="py-3 font-medium text-gray-800">{{ app.job }}</td>
              <td class="py-3 text-gray-600">{{ app.company }}</td>
              <td class="py-3">
                <span :class="['text-xs font-medium px-2 py-1 rounded-full', getStatusColor(app.status)]">
                  {{ app.status }}
                </span>
              </td>
              <td class="py-3 text-gray-500">{{ app.date }}</td>
              <td class="py-3">
                <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir →</button>
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
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4">
          <p class="font-semibold text-gray-800">Data Scientist</p>
          <p class="text-sm text-gray-600">DataMind</p>
          <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-600">
            <span>📅 10:00</span>
            <span>📍 En ligne (Google Meet)</span>
            <span>👤 Léa Martin</span>
          </div>
          <button class="mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all">
            Voir les détails
          </button>
        </div>
      </div>

      <!-- Offres recommandées -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">✨ Offres recommandées</h3>
        <div class="space-y-3">
          <div v-for="job in recommendedJobs" :key="job.title" class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
            <div>
              <p class="text-sm font-medium text-gray-800">{{ job.title }}</p>
              <p class="text-xs text-gray-500">{{ job.location }}</p>
            </div>
            <button class="text-xs font-medium text-blue-600 hover:text-blue-700">Postuler →</button>
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
              <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#3b82f6" stroke-width="3" stroke-dasharray="80 100"/>
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-800">80%</span>
          </div>
          <div>
            <p class="text-sm text-gray-600">Plus votre profil est complet, plus vous avez de chances d'être repéré.</p>
            <button class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-700">Compléter maintenant →</button>
          </div>
        </div>
      </div>

      <!-- Activité récente -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">🔄 Activité récente</h3>
        <div class="space-y-3">
          <div v-for="activity in recentActivities" :key="activity.text" class="flex items-start gap-3 text-sm">
            <span class="text-lg shrink-0">{{ activity.icon }}</span>
            <div>
              <p class="text-gray-700">{{ activity.text }}</p>
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
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Yassine')
const applicationsCount = ref(12)
const jobsCount = ref(24)
const interviewsCount = ref(2)
const savedCount = ref(5)

const recentApplications = ref([
  { job: 'Développeur Full Stack', company: 'TechNova', status: 'En cours', date: '20 mai 2024' },
  { job: 'Data Scientist', company: 'DataMind', status: 'Entretien', date: '18 mai 2024' },
  { job: 'UX/UI Designer', company: 'DesignLab', status: 'Offre reçue', date: '15 mai 2024' },
  { job: 'Product Owner', company: 'Innovatech', status: 'En cours', date: '12 mai 2024' },
])

const recommendedJobs = ref([
  { title: 'Frontend Developer', location: 'Paris, France' },
  { title: 'Full Stack Developer', location: 'Lyon, France' },
  { title: 'Product Designer', location: 'Bordeaux, France' },
])

const recentActivities = ref([
  { icon: '💬', text: 'Nouveau message de TechNova', time: 'Il y a 1 h' },
  { icon: '👁️', text: 'Votre candidature a été vue', time: 'Il y a 3 h' },
  { icon: '🗓️', text: 'Entretien programmé avec DataMind', time: 'Il y a 5 h' },
  { icon: '🎯', text: 'Offre reçue de DesignLab', time: 'Il y a 1 j' },
])

const getStatusColor = (status) => {
  const colors = {
    'En cours': 'bg-blue-100 text-blue-700',
    'Entretien': 'bg-purple-100 text-purple-700',
    'Offre reçue': 'bg-green-100 text-green-700',
    'Refusée': 'bg-red-100 text-red-700'
  }
  return colors[status] || 'bg-gray-100 text-gray-700'
}
</script>