<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Gestion des offres" 
    page-subtitle="Gérez toutes les offres d'emploi"
  >
    <!-- Header Actions -->
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Publier une offre
      </button>
    </template>

    <!-- Statistiques -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-gray-800">47</p>
        <p class="text-xs text-gray-500">Total</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-green-600">32</p>
        <p class="text-xs text-gray-500">Actives</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-amber-600">8</p>
        <p class="text-xs text-gray-500">En attente</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-red-600">7</p>
        <p class="text-xs text-gray-500">Fermées</p>
      </div>
    </div>

    <!-- Barre de recherche -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
      <div class="flex-1 relative">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher une offre..." 
          class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
        >
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
      </div>
      <select v-model="selectedStatus" class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
        <option value="">Tous les statuts</option>
        <option value="Active">Active</option>
        <option value="En attente">En attente</option>
        <option value="Fermée">Fermée</option>
      </select>
    </div>

    <!-- Liste des offres -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-left">
              <th class="px-6 py-3 font-medium text-gray-500">Offre</th>
              <th class="px-6 py-3 font-medium text-gray-500">Entreprise</th>
              <th class="px-6 py-3 font-medium text-gray-500">Candidatures</th>
              <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
              <th class="px-6 py-3 font-medium text-gray-500">Date</th>
              <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="job in filteredJobs" :key="job.title" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4">
                <div>
                  <p class="font-medium text-gray-800">{{ job.title }}</p>
                  <p class="text-xs text-gray-500">{{ job.location }} • {{ job.contract }}</p>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600">{{ job.company }}</td>
              <td class="px-6 py-4 text-gray-600">{{ job.applications }}</td>
              <td class="px-6 py-4">
                <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(job.status)]">
                  {{ job.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500">{{ job.date }}</td>
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
import { ref, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const userName = ref('Admin')
const searchQuery = ref('')
const selectedStatus = ref('')

const jobs = ref([
  { title: 'Développeur Full Stack', company: 'TechNova', location: 'Paris', contract: 'CDI', applications: 24, status: 'Active', date: '15/05/2024' },
  { title: 'Data Scientist', company: 'DataMind', location: 'Lyon', contract: 'CDI', applications: 18, status: 'Active', date: '12/05/2024' },
  { title: 'UX/UI Designer', company: 'DesignLab', location: 'Bordeaux', contract: 'CDD', applications: 12, status: 'En attente', date: '10/05/2024' },
  { title: 'DevOps Engineer', company: 'CloudSys', location: 'Toulouse', contract: 'Freelance', applications: 8, status: 'Active', date: '05/05/2024' },
  { title: 'Product Owner', company: 'Innovatech', location: 'Paris', contract: 'CDI', applications: 6, status: 'Fermée', date: '01/05/2024' },
])

const filteredJobs = computed(() => {
  return jobs.value.filter(j => {
    const matchSearch = j.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        j.company.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchStatus = !selectedStatus.value || j.status === selectedStatus.value
    return matchSearch && matchStatus
  })
})

const getStatusBadge = (status) => {
  const badges = {
    'Active': 'bg-green-100 text-green-700',
    'En attente': 'bg-amber-100 text-amber-700',
    'Fermée': 'bg-red-100 text-red-700'
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}
</script>