<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Ajouter un candidat" 
    page-subtitle="Créez un nouveau profil candidat sur la plateforme"
  >
    <!-- Menu Latéral (Sidebar) - Identique au thème Admin -->
    <template #menu>
      <SidebarItem to="/admin" icon="🏠" label="Accueil" />
      <SidebarItem to="/admin/recruiters" icon="👥" label="Recruteurs" :badge="86" />
      <SidebarItem to="/admin/candidates" icon="👨‍💼" label="Candidats" :badge="1248" active />
      <SidebarItem to="/admin/jobs" icon="💼" label="Offres d'emploi" :badge="47" />
      <SidebarItem to="/admin/applications" icon="📝" label="Candidatures" :badge="5426" />
      <SidebarItem to="/admin/analytics" icon="📊" label="Analytiques" />
      <SidebarItem to="/admin/settings" icon="⚙️" label="Paramètres" />
      <SidebarItem to="/admin/profile" icon="👤" label="Mon profil" />
    </template>

    <!-- Contenu Principal -->
    <div class="max-w-4xl mx-auto">
      
      <!-- Carte du Formulaire -->
      <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        
        <form @submit.prevent="addCandidate" class="space-y-6">
          
          <!-- Section 1: Informations Personnelles -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg text-sm">👤</span>
              Informations Personnelles
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom</label>
                <input type="text" v-model="form.firstName" placeholder="Ex: Thomas" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom</label>
                <input type="text" v-model="form.lastName" placeholder="Ex: Leroy" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" v-model="form.email" placeholder="thomas@email.com" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone</label>
                <input type="tel" v-model="form.phone" placeholder="+33 6 12 34 56 78" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
            </div>
          </div>

          <!-- Séparateur -->
          <div class="border-t border-gray-100"></div>

          <!-- Section 2: Détails Professionnels -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-purple-100 text-purple-600 p-1.5 rounded-lg text-sm">💼</span>
              Détails Professionnels
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Poste actuel / Titre</label>
                <input type="text" v-model="form.title" placeholder="Ex: Développeur Full Stack" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Localisation</label>
                <input type="text" v-model="form.location" placeholder="Ex: Paris, France" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Années d'expérience</label>
                <input type="number" v-model="form.experience" placeholder="Ex: 5" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Statut initial</label>
                <select v-model="form.status" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white">
                  <option value="Actif">Actif</option>
                  <option value="En recherche">En recherche</option>
                  <option value="Inactif">Inactif</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Séparateur -->
          <div class="border-t border-gray-100"></div>

          <!-- Section 3: Compétences (Optionnel) -->
          <div>
             <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-green-100 text-green-600 p-1.5 rounded-lg text-sm">⚡</span>
              Compétences clés
            </h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Compétences (séparées par des virgules)</label>
              <input type="text" v-model="form.skills" placeholder="Ex: Vue.js, Node.js, Python, SQL" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-4 pt-4">
            <button type="button" @click="$router.back()" class="px-6 py-3 text-gray-600 font-medium hover:bg-gray-50 rounded-xl transition-all">
              Annuler
            </button>
            <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200 flex items-center gap-2">
              <span>+</span> Ajouter le candidat
            </button>
          </div>

        </form>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'

const router = useRouter()
const userName = ref('Admin')

// Données du formulaire
const form = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  title: '',
  location: '',
  experience: 0,
  status: 'Actif',
  skills: ''
})

// Fonction de soumission
const addCandidate = () => {
  // Ici vous pouvez ajouter la logique d'appel API (axios/fetch)
  console.log('Nouveau candidat:', form)
  
  // Simulation d'ajout réussi
  alert('✅ Candidat ajouté avec succès !')
  
  // Redirection vers la liste des candidats
  router.push('/admin/candidates')
}
</script>