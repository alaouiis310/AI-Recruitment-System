<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Mon profil" 
    page-subtitle="Gérez vos informations personnelles et professionnelles"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
      <div class="mt-4 border-t border-gray-100 pt-4">
        <SidebarItem to="/recruiter/profile" icon="👤" label="Mon profil" active />
      </div>
    </template>

    <!-- Header Actions -->
    <template #header-actions>
      <button @click="saveProfile" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        💾 Enregistrer
      </button>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Photo de profil & Infos -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center">
        <div class="relative inline-block">
          <img 
            :src="profile.avatar" 
            alt="Avatar" 
            class="w-28 h-28 rounded-full border-4 border-blue-100 object-cover mx-auto"
          >
          <button class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full shadow-lg hover:bg-blue-700 transition-colors text-sm">
            📷
          </button>
        </div>
        <h3 class="font-semibold text-gray-800 mt-4">{{ profile.firstName }} {{ profile.lastName }}</h3>
        <p class="text-sm text-blue-600 font-medium">{{ profile.role }}</p>
        <p class="text-sm text-gray-500 mt-1">{{ profile.company }}</p>
        
        <div class="mt-4 p-3 bg-gray-50 rounded-xl text-sm text-gray-600">
          <p>📧 {{ profile.email }}</p>
          <p class="mt-1">📱 {{ profile.phone }}</p>
          <p class="mt-1">📍 {{ profile.location }}</p>
        </div>

        <div class="mt-4 flex justify-center gap-3">
          <a href="#" class="text-gray-500 hover:text-blue-600 transition-colors">🔗 LinkedIn</a>
          <span class="text-gray-300">|</span>
          <a href="#" class="text-gray-500 hover:text-blue-600 transition-colors">🐙 GitHub</a>
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
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email professionnel</label>
            <input type="email" v-model="profile.email" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone</label>
            <input type="tel" v-model="profile.phone" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Entreprise</label>
            <input type="text" v-model="profile.company" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Localisation</label>
            <input type="text" v-model="profile.location" placeholder="Ville, Pays" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Poste</label>
            <input type="text" v-model="profile.jobTitle" placeholder="Responsable Recrutement" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Bio</label>
            <textarea v-model="profile.bio" rows="3" placeholder="Présentez-vous en quelques mots..." class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all resize-none"></textarea>
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

const userName = ref('Sophie Martin')
const myJobsCount = ref(12)
const candidatesCount = ref(86)
const messagesCount = ref(4)

const profile = reactive({
  firstName: 'Sophie',
  lastName: 'Martin',
  email: 'sophie.martin@entreprise.com',
  phone: '+33 6 12 34 56 78',
  company: 'TechNova Solutions',
  location: 'Paris, France',
  jobTitle: 'Responsable Recrutement',
  role: 'Recruteur',
  bio: 'Passionnée par le recrutement tech depuis 8 ans, je cherche les meilleurs talents pour faire grandir nos équipes.',
  avatar: 'https://ui-avatars.com/api/?name=Sophie+Martin&background=2563eb&color=fff&size=120'
})

const saveProfile = () => {
  alert('✅ Profil enregistré avec succès !')
}
</script>