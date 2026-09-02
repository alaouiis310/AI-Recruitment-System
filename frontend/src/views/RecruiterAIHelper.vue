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
            <p class="text-sm">{{ msg.text }}</p>
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
        <button @click="sendMessage" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
          Envoyer
        </button>
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

const userMessage = ref('')
const messages = ref([])

const sendMessage = () => {
  if (!userMessage.value.trim()) return

  // Ajouter le message de l'utilisateur
  messages.value.push({ sender: 'user', text: userMessage.value })

  // Simuler une réponse IA
  setTimeout(() => {
    messages.value.push({ 
      sender: 'ai', 
      text: "Je vais analyser votre demande... C'est une excellente question ! Je vous recommande de consulter les candidats avec un score IA supérieur à 80%."
    })
  }, 500)

  userMessage.value = ''
}
</script>