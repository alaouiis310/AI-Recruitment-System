<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Tableau de bord" 
    page-subtitle="Vue d'ensemble de vos recrutements"
  >
<!-- Menu -->
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

    <!-- Statistiques -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
      <StatCard 
        label="Nouvelles candidatures" 
        value="24" 
        icon="📥" 
        icon-bg="bg-blue-50" 
        trend="+5" 
        trend-label="cette semaine" 
      />
      <StatCard 
        label="Entretiens à venir" 
        value="5" 
        icon="🗓️" 
        icon-bg="bg-purple-50" 
        trend="+2" 
        trend-label="cette semaine" 
      />
      <StatCard 
        label="Shortlist" 
        value="18" 
        icon="⭐" 
        icon-bg="bg-amber-50" 
        trend="+3" 
        trend-label="nouveaux" 
      />
      <StatCard 
        label="Embauchés" 
        value="3" 
        icon="🎯" 
        icon-bg="bg-green-50" 
        trend="+1" 
        trend-label="ce mois" 
      />
    </div>

    <!-- Pipeline & Offres recommandées -->
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

      <!-- Offres recommandées -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Offres recommandées pour vous</h3>
        <div class="space-y-3">
          <div v-for="job in recommendedJobs" :key="job.title" class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
            <div>
              <p class="text-sm font-medium text-gray-800">{{ job.title }}</p>
              <p class="text-xs text-gray-500">{{ job.company }}</p>
            </div>
            <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-lg">Match {{ job.match }}%</span>
          </div>
        </div>
        <button class="w-full mt-3 text-sm text-blue-600 font-medium hover:text-blue-700">Voir toutes les offres →</button>
      </div>
    </div>

    <!-- CV Recommandations & Tâches -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">📄 Suggestions CV</h3>
        <div class="space-y-3">
          <div v-for="cv in cvSuggestions" :key="cv.name" class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
            <div>
              <p class="text-sm font-medium text-gray-800">{{ cv.name }}</p>
              <p class="text-xs text-gray-500">{{ cv.role }}</p>
            </div>
            <button class="text-xs font-medium text-blue-600 hover:text-blue-700">Voir →</button>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">✅ Tâches à faire</h3>
        <div class="space-y-3">
          <div v-for="task in tasks" :key="task.label" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
            <input type="checkbox" class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <span class="text-sm text-gray-700">{{ task.label }}</span>
            <span class="ml-auto text-xs text-gray-400">{{ task.count }}</span>
          </div>
        </div>
        <button class="w-full mt-3 text-sm text-blue-600 font-medium hover:text-blue-700">Voir toutes les tâches →</button>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import StatCard from '../components/StatCard.vue'
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Sophie Martin')
const myJobsCount = ref(12)
const candidatesCount = ref(86)
const messagesCount = ref(4)

const pipelineStages = ref([
  { label: 'Nouveaux', count: 12, percentage: 30, color: '#3b82f6' },
  { label: 'Screening', count: 8, percentage: 20, color: '#60a5fa' },
  { label: 'Entretien', count: 6, percentage: 15, color: '#f59e0b' },
  { label: 'Offre', count: 3, percentage: 7, color: '#10b981' },
  { label: 'Embauchés', count: 2, percentage: 5, color: '#8b5cf6' },
])

const recommendedJobs = ref([
  { title: 'Développeur Full Stack', company: 'TechNova', match: 92 },
  { title: 'Data Scientist', company: 'DataMind', match: 85 },
  { title: 'Product Owner', company: 'Innovatech', match: 78 },
])

const cvSuggestions = ref([
  { name: 'Thomas Leroy', role: 'Dev Full Stack' },
  { name: 'Camille Dubois', role: 'UX Designer' },
  { name: 'Mehdi Amine', role: 'Data Analyst' },
])

const tasks = ref([
  { label: 'Évaluer les candidatures', count: 12 },
  { label: 'Planifier les entretiens', count: 3 },
  { label: 'Relancer les candidats', count: 5 },
])
</script>