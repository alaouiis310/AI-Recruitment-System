<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Publier une offre" 
    page-subtitle="Créez et publiez une nouvelle offre d'emploi"
  >
    <!-- Menu Latéral (Sidebar) -->
    <template #menu>
      <SidebarItem to="/admin" icon="🏠" label="Accueil" />
      <SidebarItem to="/admin/recruiters" icon="👥" label="Recruteurs" :badge="86" />
      <SidebarItem to="/admin/candidates" icon="👨‍💼" label="Candidats" :badge="1248" />
      <SidebarItem to="/admin/jobs" icon="💼" label="Offres d'emploi" :badge="47" active />
      <SidebarItem to="/admin/applications" icon="📝" label="Candidatures" :badge="5426" />
      <SidebarItem to="/admin/analytics" icon="📊" label="Analytiques" />
      <SidebarItem to="/admin/settings" icon="⚙️" label="Paramètres" />
      <SidebarItem to="/admin/profile" icon="👤" label="Mon profil" />
    </template>

    <!-- Contenu Principal -->
    <div class="max-w-4xl mx-auto">
      
      <!-- Carte du Formulaire -->
      <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        
        <form @submit.prevent="publishJob" class="space-y-6">
          
          <!-- Section 1: Informations sur le poste -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg text-sm">💼</span>
              Informations sur le poste
            </h3>
            <div class="grid grid-cols-1 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Titre de l'offre</label>
                <input type="text" v-model="form.title" placeholder="Ex: Développeur Full Stack" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Entreprise</label>
                  <input type="text" v-model="form.company" placeholder="Ex: TechNova" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Localisation</label>
                  <input type="text" v-model="form.location" placeholder="Ex: Paris, France" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Type de contrat</label>
                  <select v-model="form.contractType" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white">
                    <option value="CDI">CDI</option>
                    <option value="CDD">CDD</option>
                    <option value="Freelance">Freelance</option>
                    <option value="Stage">Stage</option>
                    <option value="Alternance">Alternance</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Salaire (optionnel)</label>
                  <input type="text" v-model="form.salary" placeholder="Ex: 45 000 € - 55 000 €" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
                </div>
              </div>
            </div>
          </div>

          <!-- Séparateur -->
          <div class="border-t border-gray-100"></div>

          <!-- Section 2: Description -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-purple-100 text-purple-600 p-1.5 rounded-lg text-sm">📝</span>
              Description du poste
            </h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                <textarea v-model="form.description" rows="4" placeholder="Décrivez les missions principales..." class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all resize-none"></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Compétences requises (séparées par des virgules)</label>
                <input type="text" v-model="form.skills" placeholder="Ex: Vue.js, Node.js, Python, SQL" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
            </div>
          </div>

          <!-- Séparateur -->
          <div class="border-t border-gray-100"></div>

          <!-- Section 3: Paramètres de publication -->
          <div>
             <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-green-100 text-green-600 p-1.5 rounded-lg text-sm">⚙️</span>
              Paramètres de publication
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Statut</label>
                <select v-model="form.status" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white">
                  <option value="Active">Active</option>
                  <option value="En attente">En attente</option>
                  <option value="Fermée">Fermée</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Date de clôture (optionnel)</label>
                <input type="date" v-model="form.deadline" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-4 pt-4">
            <button type="button" @click="$router.back()" class="px-6 py-3 text-gray-600 font-medium hover:bg-gray-50 rounded-xl transition-all">
              Annuler
            </button>
            <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200 flex items-center gap-2">
              <span>+</span> Publier l'offre
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
  title: '',
  company: '',
  location: '',
  contractType: 'CDI',
  salary: '',
  description: '',
  skills: '',
  status: 'Active',
  deadline: ''
})

// Fonction de soumission
const publishJob = () => {
  console.log('Nouvelle offre:', form)
  alert('✅ Offre publiée avec succès !')
  router.push('/admin/jobs')
}
</script>