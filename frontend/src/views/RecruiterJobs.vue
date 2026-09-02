<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Mes offres" 
    page-subtitle="Gérez toutes vos offres d'emploi"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" active />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
    </template>

    <!-- Header Actions -->
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Publier une offre
      </button>
    </template>

    <!-- Statistiques Offres -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-gray-800">12</p>
        <p class="text-xs text-gray-500">Total</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-green-600">8</p>
        <p class="text-xs text-gray-500">Actives</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-amber-600">3</p>
        <p class="text-xs text-gray-500">En attente</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-red-600">1</p>
        <p class="text-xs text-gray-500">Fermées</p>
      </div>
    </div>

    <!-- Liste des offres -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-left">
              <th class="px-6 py-3 font-medium text-gray-500">Offre</th>
              <th class="px-6 py-3 font-medium text-gray-500">Candidatures</th>
              <th class="px-6 py-3 font-medium text-gray-500">Date</th>
              <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
              <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="job in jobs" :key="job.title" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4">
                <div>
                  <p class="font-medium text-gray-800">{{ job.title }}</p>
                  <p class="text-xs text-gray-500">{{ job.location }} • {{ job.contract }}</p>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600">{{ job.applications }}</td>
              <td class="px-6 py-4 text-gray-500">{{ job.date }}</td>
              <td class="px-6 py-4">
                <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(job.status)]">
                  {{ job.status }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir</button>
                  <span class="text-gray-300">|</span>
                  <button class="text-gray-500 hover:text-gray-700 text-sm">✎</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Sophie Martin')
const myJobsCount = ref(12)
const candidatesCount = ref(86)
const messagesCount = ref(4)

const jobs = ref([
  { title: 'Développeur Full Stack', location: 'Paris', contract: 'CDI', applications: 24, date: '15/05/2024', status: 'Active' },
  { title: 'Data Scientist', location: 'Lyon', contract: 'CDI', applications: 18, date: '12/05/2024', status: 'Active' },
  { title: 'UX/UI Designer', location: 'Bordeaux', contract: 'CDD', applications: 12, date: '10/05/2024', status: 'En attente' },
  { title: 'DevOps Engineer', location: 'Toulouse', contract: 'Freelance', applications: 8, date: '05/05/2024', status: 'Active' },
  { title: 'Product Owner', location: 'Paris', contract: 'CDI', applications: 6, date: '01/05/2024', status: 'Fermée' },
])

const getStatusBadge = (status) => {
  const badges = {
    'Active': 'bg-green-100 text-green-700',
    'En attente': 'bg-amber-100 text-amber-700',
    'Fermée': 'bg-red-100 text-red-700'
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}
</script>