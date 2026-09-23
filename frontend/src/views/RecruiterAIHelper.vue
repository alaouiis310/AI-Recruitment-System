<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="AI Helper" 
    page-subtitle="L'intelligence artificielle au service de votre recrutement"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" active />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
    </template>

    <!-- 4 Cards IA -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
      <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">🤖</div>
        <h4 class="font-semibold">Analyse CV</h4>
        <p class="text-blue-100 text-sm mt-1">Analyse automatique des CV</p>
      </div>
      <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">🎯</div>
        <h4 class="font-semibold">Matching</h4>
        <p class="text-purple-100 text-sm mt-1">Score de compatibilité</p>
      </div>
      <div class="bg-gradient-to-br from-amber-600 to-amber-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">💡</div>
        <h4 class="font-semibold">Suggestions</h4>
        <p class="text-amber-100 text-sm mt-1">Recommandations personnalisées</p>
      </div>
      <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">📝</div>
        <h4 class="font-semibold">Questions</h4>
        <p class="text-green-100 text-sm mt-1">Génération d'entretiens</p>
      </div>
    </div>

    <!-- Chat IA -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-5 border-b border-gray-100 flex items-center gap-3">
        <span class="text-2xl">🤖</span>
        <div>
          <h4 class="font-semibold text-gray-800">Assistant IA</h4>
          <p class="text-xs text-gray-500">Posez une question sur vos recrutements</p>
        </div>
        <span class="ml-auto text-xs text-green-600 font-medium">● En ligne</span>
      </div>

      <!-- Messages -->
      <div class="p-5 max-h-80 overflow-y-auto space-y-4">
        <div class="flex items-start gap-3">
          <span class="text-xl">🤖</span>
          <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-2.5 max-w-[80%]">
            <p class="text-sm text-gray-700">Bonjour ! Je suis votre assistant IA. Je peux vous aider à :</p>
            <ul class="text-sm text-gray-600 mt-1 list-disc list-inside">
              <li>Analyser des CV</li>
              <li>Évaluer la compatibilité des candidats</li>
              <li>Générer des questions d'entretien</li>
            </ul>
          </div>
        </div>

        <div v-for="(msg, index) in messages" :key="index" class="flex items-start gap-3" :class="msg.sender === 'user' ? 'flex-row-reverse' : ''">
          <span class="text-xl shrink-0">{{ msg.sender === 'user' ? '👤' : '🤖' }}</span>
          <div :class="[
            'rounded-2xl px-4 py-2.5 max-w-[80%]',
            msg.sender === 'user' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-gray-100 text-gray-700 rounded-tl-none'
          ]">
            <p class="text-sm whitespace-pre-line">{{ msg.text }}</p>
          </div>
        </div>
        <div v-if="enAttente" class="flex items-start gap-3" aria-live="polite">
          <span class="text-xl shrink-0">🤖</span>
          <div class="rounded-2xl rounded-tl-none px-4 py-2.5 bg-gray-100 text-gray-500">
            <p class="text-sm italic">L'assistant rédige sa réponse…</p>
          </div>
        </div>
      </div>

      <!-- Input -->
      <div class="p-4 border-t border-gray-100 flex gap-3">
        <input 
          v-model="userMessage" 
          type="text" 
          placeholder="Posez une question à l'IA..." 
          class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-sm"
          @keydown.enter="sendMessage"
        >
        <button @click="sendMessage" :disabled="enAttente" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
          Envoyer
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Recruteur'
})
const myJobsCount = ref(0)
const candidatesCount = ref(0)
const messagesCount = ref(0)

onMounted(async () => {
  try {
    const response = await api.get('/recruteur/tableau-de-bord')
    const stats = response.data.statistiques || {}
    myJobsCount.value = stats.offres || 0
    candidatesCount.value = stats.candidatures || 0
  } catch (err) {
    console.error('Erreur statistiques:', err)
  }
})

const userMessage = ref('')
const messages = ref([])

// Assistant IA réel (module de Nilam) : POST /api/ia/assistant.
const enAttente = ref(false)

const sendMessage = async () => {
  const texte = userMessage.value.trim()
  if (!texte || enAttente.value) return

  messages.value.push({ sender: 'user', text: texte })
  userMessage.value = ''
  enAttente.value = true

  try {
    const response = await api.post('/ia/assistant', { message: texte })
    messages.value.push({ sender: 'ai', text: response.data.data.reponse })
  } catch (err) {
    // 503 : pas de clé Gemini sur ce serveur ; 502 : Gemini injoignable.
    messages.value.push({
      sender: 'ai',
      text: err.response?.data?.message || "L'assistant est momentanément indisponible.",
    })
  } finally {
    enAttente.value = false
  }
}
</script>