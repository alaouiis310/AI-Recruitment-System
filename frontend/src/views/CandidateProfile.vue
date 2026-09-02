<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Mon profil" 
    page-subtitle="Gérez vos informations personnelles"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" active />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
    </template>

    <!-- Formulaire -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Photo de profil -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center">
        <div class="relative inline-block">
          <img 
            src="https://ui-avatars.com/api/?name=Yassine&background=2563eb&color=fff&size=120" 
            alt="Avatar" 
            class="w-28 h-28 rounded-full border-4 border-blue-100 object-cover mx-auto"
          >
          <button class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full shadow-lg hover:bg-blue-700 transition-colors text-sm">
            📷
          </button>
        </div>
        <h3 class="font-semibold text-gray-800 mt-4">Yassine</h3>
        <p class="text-sm text-gray-500">Candidat</p>
        <div class="mt-4 p-3 bg-gray-50 rounded-xl text-sm text-gray-600">
          <p>📧 yassine@email.com</p>
          <p class="mt-1">📱 +33 6 12 34 56 78</p>
        </div>
        <button class="w-full mt-4 py-2 border border-red-200 text-red-500 hover:bg-red-50 rounded-xl text-sm font-medium transition-all">
          Supprimer mon compte
        </button>
      </div>

      <!-- Informations -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <form @submit.prevent="saveProfile" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom</label>
              <input type="text" v-model="profile.firstName" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom</label>
              <input type="text" v-model="profile.lastName" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input type="email" v-model="profile.email" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone</label>
            <input type="tel" v-model="profile.phone" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Localisation</label>
            <input type="text" v-model="profile.location" placeholder="Ville, Pays" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Expérience</label>
            <input type="number" v-model="profile.experience" placeholder="Années d'expérience" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
          </div>
          <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200">
            Enregistrer les modifications
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Yassine')
const applicationsCount = ref(12)
const jobsCount = ref(24)
const interviewsCount = ref(2)
const savedCount = ref(5)

const profile = reactive({
  firstName: 'Yassine',
  lastName: '',
  email: 'yassine@email.com',
  phone: '+33 6 12 34 56 78',
  location: 'Paris, France',
  experience: 3
})

const saveProfile = () => {
  alert('✅ Profil enregistré avec succès !')
}
</script>