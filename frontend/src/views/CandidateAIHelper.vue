<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="AI Helper" 
    page-subtitle="Optimisez vos candidatures avec l'intelligence artificielle"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
      <div class="mt-4 border-t border-gray-100 pt-4">
        <SidebarItem to="/candidate/ai-helper" icon="🤖" label="AI Helper" active />
      </div>
    </template>

    <!-- 4 Cards IA -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
      <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">📄</div>
        <h4 class="font-semibold">Analyse CV</h4>
        <p class="text-blue-100 text-sm mt-1">Optimisez votre CV</p>
      </div>
      <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">🎯</div>
        <h4 class="font-semibold">Matching</h4>
        <p class="text-purple-100 text-sm mt-1">Offres compatibles</p>
      </div>
      <div class="bg-gradient-to-br from-amber-600 to-amber-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">💡</div>
        <h4 class="font-semibold">Suggestions</h4>
        <p class="text-amber-100 text-sm mt-1">Améliorations</p>
      </div>
      <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">📝</div>
        <h4 class="font-semibold">Lettre</h4>
        <p class="text-green-100 text-sm mt-1">Génération automatique</p>
      </div>
    </div>

    <!-- Section Analyse de CV -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-3">📊 Score de votre CV</h3>
        <div class="text-center">
          <div class="relative inline-block">
            <svg class="w-32 h-32" viewBox="0 0 36 36">
              <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e5e7eb" stroke-width="3"/>
              <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#3b82f6" stroke-width="3" stroke-dasharray="75 100"/>
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-3xl font-bold text-gray-800">75%</span>
          </div>
        </div>
        <div class="space-y-2 mt-4">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Contenu</span>
            <span class="font-medium text-green-600">82%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-1.5">
            <div class="h-1.5 rounded-full bg-green-500" style="width: 82%"></div>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Format</span>
            <span class="font-medium text-amber-600">68%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-1.5">
            <div class="h-1.5 rounded-full bg-amber-500" style="width: 68%"></div>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Mots-clés</span>
            <span class="font-medium text-green-600">78%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-1.5">
            <div class="h-1.5 rounded-full bg-green-500" style="width: 78%"></div>
          </div>
        </div>
        <button class="w-full mt-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition-all">
          🔄 Analyser avec l'IA
        </button>
      </div>

      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-3">💡 Suggestions d'amélioration</h3>
        <div class="space-y-3">
          <div v-for="suggestion in suggestions" :key="suggestion.id" class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
            <span class="text-lg shrink-0">{{ suggestion.icon }}</span>
            <div>
              <p class="text-sm font-medium text-gray-800">{{ suggestion.title }}</p>
              <p class="text-xs text-gray-500">{{ suggestion.description }}</p>
            </div>
            <button class="ml-auto shrink-0 text-xs font-medium text-blue-600 hover:text-blue-700">Appliquer</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Chat IA -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-5 border-b border-gray-100 flex items-center gap-3">
        <span class="text-2xl">🤖</span>
        <div>
          <h4 class="font-semibold text-gray-800">Assistant IA</h4>
          <p class="text-xs text-gray-500">Conseils personnalisés pour vos candidatures</p>
        </div>
        <span class="ml-auto text-xs text-green-600 font-medium">● En ligne</span>
      </div>

      <!-- Messages -->
      <div class="p-5 max-h-64 overflow-y-auto space-y-4">
        <div class="flex items-start gap-3">
          <span class="text-xl">🤖</span>
          <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-2.5 max-w-[80%]">
            <p class="text-sm text-gray-700">Bonjour ! Je suis votre assistant IA. Je peux vous aider à :</p>
            <ul class="text-sm text-gray-600 mt-1 list-disc list-inside">
              <li>Optimiser votre CV</li>
              <li>Trouver des offres compatibles</li>
              <li>Préparer vos entretiens</li>
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
          placeholder="Posez une question sur votre candidature..." 
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

const userName = ref('Yassine')
const applicationsCount = ref(12)
const jobsCount = ref(24)
const interviewsCount = ref(2)
const savedCount = ref(5)

const userMessage = ref('')
const messages = ref([])

const suggestions = ref([
  { id: 1, icon: '📝', title: 'Ajoutez plus de mots-clés', description: 'Ajoutez "Laravel" et "Docker" pour correspondre aux offres' },
  { id: 2, icon: '📊', title: 'Quantifiez vos résultats', description: 'Utilisez des chiffres pour vos réalisations' },
  { id: 3, icon: '🎯', title: 'Personnalisez votre objectif', description: 'Adaptez votre profil aux offres cibles' },
])

const sendMessage = () => {
  if (!userMessage.value.trim()) return

  messages.value.push({ sender: 'user', text: userMessage.value })

  setTimeout(() => {
    messages.value.push({ 
      sender: 'ai', 
      text: "Je vais analyser votre demande. Pour améliorer votre CV, je vous recommande d'ajouter des mots-clés spécifiques comme 'Laravel', 'Vue.js' et 'Docker'."
    })
  }, 500)

  userMessage.value = ''
}
</script>