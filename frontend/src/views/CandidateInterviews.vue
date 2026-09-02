<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Mes entretiens" 
    page-subtitle="Gérez vos entretiens à venir"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" active />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
    </template>

    <!-- Vue d'ensemble -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500">Entretiens à venir</p>
        <p class="text-2xl font-bold text-gray-800">3</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500">Entretiens passés</p>
        <p class="text-2xl font-bold text-gray-800">5</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500">Taux de réussite</p>
        <p class="text-2xl font-bold text-green-600">67%</p>
      </div>
    </div>

    <!-- Liste des entretiens -->
    <div class="space-y-4">
      <div v-for="interview in interviews" :key="interview.id" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h4 class="font-semibold text-gray-800">{{ interview.job }}</h4>
            <p class="text-sm text-gray-500">{{ interview.company }}</p>
            <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-600">
              <span>📅 {{ interview.date }}</span>
              <span>•</span>
              <span>⏰ {{ interview.time }}</span>
              <span>•</span>
              <span>📍 {{ interview.mode }}</span>
            </div>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <span :class="['text-xs font-medium px-3 py-1 rounded-full', interview.status === 'Confirmé' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700']">
              {{ interview.status }}
            </span>
            <button class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700">Voir détails</button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Yassine')
const applicationsCount = ref(12)
const jobsCount = ref(24)
const interviewsCount = ref(2)
const savedCount = ref(5)

const interviews = ref([
  { id: 1, job: 'Data Scientist', company: 'DataMind', date: '25 mai 2024', time: '10:00', mode: 'En ligne (Google Meet)', status: 'Confirmé' },
  { id: 2, job: 'Frontend Developer', company: 'WebCorp', date: '28 mai 2024', time: '14:30', mode: 'Présentiel (Paris)', status: 'Confirmé' },
  { id: 3, job: 'Product Owner', company: 'Innovatech', date: '01 juin 2024', time: '11:00', mode: 'En ligne (Teams)', status: 'À confirmer' },
])
</script>