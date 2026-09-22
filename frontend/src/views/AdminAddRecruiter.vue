<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Ajouter un recruteur" 
    page-subtitle="Créez un nouveau profil recruteur sur la plateforme"
  >
    <!-- Menu Latéral (Sidebar) - Identique au thème Admin -->
    <template #menu>
      <SidebarItem to="/admin" icon="🏠" label="Accueil" />
      <SidebarItem to="/admin/recruiters" icon="👥" label="Recruteurs" :badge="86" active />
      <SidebarItem to="/admin/candidates" icon="👨‍💼" label="Candidats" :badge="1248" />
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
        
        <form @submit.prevent="addRecruiter" class="space-y-6">
          
          <!-- Section 1: Informations Personnelles -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg text-sm">👤</span>
              Informations Personnelles
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom</label>
                <input type="text" v-model="form.firstName" placeholder="Ex: Sophie" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom</label>
                <input type="text" v-model="form.lastName" placeholder="Ex: Martin" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email professionnel</label>
                <input type="email" v-model="form.email" placeholder="sophie@entreprise.com" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
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
              <span class="bg-purple-100 text-purple-600 p-1.5 rounded-lg text-sm">🏢</span>
              Détails de l'entreprise
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom de l'entreprise</label>
                <input type="text" v-model="form.company" placeholder="Ex: TechCorp" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Poste / Titre</label>
                <input type="text" v-model="form.title" placeholder="Ex: Responsable Recrutement" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Localisation</label>
                <input type="text" v-model="form.location" placeholder="Ex: Paris, France" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Statut initial</label>
                <select v-model="form.status" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white">
                  <option value="Actif">Actif</option>
                  <option value="En attente">En attente</option>
                  <option value="Inactif">Inactif</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Séparateur -->
          <div class="border-t border-gray-100"></div>

          <!-- Section 3: Informations Complémentaires -->
          <div>
             <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-green-100 text-green-600 p-1.5 rounded-lg text-sm">📋</span>
              Informations complémentaires
            </h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Secteur d'activité</label>
              <input type="text" v-model="form.sector" placeholder="Ex: Informatique, Santé, Finance..." class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-4 pt-4">
            <button type="button" @click="$router.back()" class="px-6 py-3 text-gray-600 font-medium hover:bg-gray-50 rounded-xl transition-all">
              Annuler
            </button>
            <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200 flex items-center gap-2">
              <span>+</span> Ajouter le recruteur
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
  company: '',
  title: '',
  location: '',
  status: 'Actif',
  sector: ''
})

// Fonction de soumission
const addRecruiter = () => {
  console.log('Nouveau recruteur:', form)
  alert('✅ Recruteur ajouté avec succès !')
  router.push('/admin/recruiters')
}
</script>