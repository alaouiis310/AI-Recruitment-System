<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Gestion des recruteurs" 
    page-subtitle="Gérez tous les recruteurs de la plateforme"
  >
    <!-- Header Actions -->
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Ajouter un recruteur
      </button>
    </template>

    <!-- Statistiques -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-gray-800">86</p>
        <p class="text-xs text-gray-500">Total</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-green-600">72</p>
        <p class="text-xs text-gray-500">Actifs</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-amber-600">8</p>
        <p class="text-xs text-gray-500">En attente</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-red-600">6</p>
        <p class="text-xs text-gray-500">Bloqués</p>
      </div>
    </div>

    <!-- Barre de recherche -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
      <div class="flex-1 relative">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher un recruteur..." 
          class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
        >
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
      </div>
      <select v-model="selectedStatus" class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
        <option value="">Tous les statuts</option>
        <option value="Actif">Actif</option>
        <option value="En attente">En attente</option>
        <option value="Bloqué">Bloqué</option>
      </select>
    </div>

    <!-- Liste des recruteurs -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-left">
              <th class="px-6 py-3 font-medium text-gray-500">Recruteur</th>
              <th class="px-6 py-3 font-medium text-gray-500">Entreprise</th>
              <th class="px-6 py-3 font-medium text-gray-500">Offres</th>
              <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
              <th class="px-6 py-3 font-medium text-gray-500">Date</th>
              <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="recruiter in filteredRecruiters" :key="recruiter.name" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img :src="recruiter.avatar" alt="Avatar" class="w-9 h-9 rounded-full border-2 border-blue-100 object-cover">
                  <div>
                    <p class="font-medium text-gray-800">{{ recruiter.name }}</p>
                    <p class="text-xs text-gray-500">{{ recruiter.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600">{{ recruiter.company }}</td>
              <td class="px-6 py-4 text-gray-600">{{ recruiter.jobs }}</td>
              <td class="px-6 py-4">
                <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(recruiter.status)]">
                  {{ recruiter.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500">{{ recruiter.date }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir</button>
                  <span class="text-gray-300">|</span>
                  <button class="text-gray-500 hover:text-gray-700 text-sm">✎</button>
                  <span class="text-gray-300">|</span>
                  <button class="text-red-500 hover:text-red-700 text-sm">🗑️</button>
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

const recruiters = ref([
  { name: 'Sophie Martin', email: 'sophie@technova.com', company: 'TechNova', jobs: 12, status: 'Actif', date: '15/05/2024', avatar: 'https://ui-avatars.com/api/?name=Sophie+Martin&background=2563eb&color=fff&size=36' },
  { name: 'Jean Dupont', email: 'jean@datamind.com', company: 'DataMind', jobs: 8, status: 'Actif', date: '12/05/2024', avatar: 'https://ui-avatars.com/api/?name=Jean+Dupont&background=7c3aed&color=fff&size=36' },
  { name: 'Marie Petit', email: 'marie@designlab.com', company: 'DesignLab', jobs: 5, status: 'En attente', date: '10/05/2024', avatar: 'https://ui-avatars.com/api/?name=Marie+Petit&background=059669&color=fff&size=36' },
  { name: 'Pierre Durand', email: 'pierre@cloudsys.com', company: 'CloudSys', jobs: 3, status: 'Bloqué', date: '05/05/2024', avatar: 'https://ui-avatars.com/api/?name=Pierre+Durand&background=dc2626&color=fff&size=36' },
])

const filteredRecruiters = computed(() => {
  return recruiters.value.filter(r => {
    const matchSearch = r.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        r.company.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchStatus = !selectedStatus.value || r.status === selectedStatus.value
    return matchSearch && matchStatus
  })
})

const getStatusBadge = (status) => {
  const badges = {
    'Actif': 'bg-green-100 text-green-700',
    'En attente': 'bg-amber-100 text-amber-700',
    'Bloqué': 'bg-red-100 text-red-700'
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}
</script>