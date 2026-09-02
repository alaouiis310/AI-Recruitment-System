<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Messages" 
    page-subtitle="Gérez vos échanges avec les candidats"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" active />
    </template>

    <!-- Header Actions -->
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Nouveau message
      </button>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Liste des conversations -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-[600px] flex flex-col">
        <div class="p-4 border-b border-gray-100">
          <input 
            v-model="searchConversation" 
            type="text" 
            placeholder="Rechercher..." 
            class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-sm"
          >
        </div>
        <div class="flex-1 overflow-y-auto">
          <div 
            v-for="conv in filteredConversations" 
            :key="conv.id" 
            @click="selectedConversation = conv"
            :class="[
              'flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 transition-all border-b border-gray-50',
              selectedConversation?.id === conv.id ? 'bg-blue-50' : ''
            ]"
          >
            <img :src="conv.avatar" alt="Avatar" class="w-11 h-11 rounded-full border-2 border-blue-100 object-cover">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800">{{ conv.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ conv.lastMessage }}</p>
            </div>
            <div class="text-right shrink-0">
              <p class="text-xs text-gray-400">{{ conv.time }}</p>
              <span v-if="conv.unread" class="inline-block w-5 h-5 bg-blue-600 text-white text-xs font-bold rounded-full text-center leading-5">{{ conv.unread }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Conversation -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-[600px] flex flex-col">
        <div v-if="selectedConversation" class="flex-1 flex flex-col">
          <!-- Header -->
          <div class="flex items-center gap-3 p-4 border-b border-gray-100">
            <img :src="selectedConversation.avatar" alt="Avatar" class="w-10 h-10 rounded-full border-2 border-blue-100 object-cover">
            <div>
              <p class="font-semibold text-gray-800 text-sm">{{ selectedConversation.name }}</p>
              <p class="text-xs text-gray-500">{{ selectedConversation.job }}</p>
            </div>
            <div class="ml-auto flex gap-2">
              <button class="text-gray-400 hover:text-gray-600 text-sm">📞</button>
              <button class="text-gray-400 hover:text-gray-600 text-sm">📋</button>
            </div>
          </div>

          <!-- Messages -->
          <div class="flex-1 p-4 overflow-y-auto space-y-3">
            <div v-for="(msg, index) in chatMessages" :key="index" class="flex" :class="msg.sender === 'me' ? 'justify-end' : ''">
              <div :class="[
                'max-w-[75%] rounded-2xl px-4 py-2.5 text-sm',
                msg.sender === 'me' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-gray-100 text-gray-700 rounded-tl-none'
              ]">
                {{ msg.text }}
              </div>
            </div>
          </div>

          <!-- Input -->
          <div class="p-4 border-t border-gray-100 flex gap-3">
            <input 
              v-model="newMessage" 
              type="text" 
              placeholder="Écrire un message..." 
              class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-sm"
              @keydown.enter="sendMessageToCandidate"
            >
            <button @click="sendMessageToCandidate" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
              Envoyer
            </button>
          </div>
        </div>

        <div v-else class="flex-1 flex items-center justify-center text-gray-400">
          <div class="text-center">
            <p class="text-5xl mb-3">💬</p>
            <p>Sélectionnez une conversation</p>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Sophie Martin')
const myJobsCount = ref(12)
const candidatesCount = ref(86)
const messagesCount = ref(4)

const searchConversation = ref('')
const selectedConversation = ref(null)
const newMessage = ref('')

const conversations = ref([
  { id: 1, name: 'Thomas Leroy', job: 'Dev Full Stack', lastMessage: 'Merci pour votre retour !', time: '10:30', unread: 2, avatar: 'https://ui-avatars.com/api/?name=Thomas+Leroy&background=2563eb&color=fff&size=44' },
  { id: 2, name: 'Camille Dubois', job: 'UX Designer', lastMessage: 'Quand pouvons-nous planifier un entretien ?', time: 'Hier', unread: 1, avatar: 'https://ui-avatars.com/api/?name=Camille+Dubois&background=7c3aed&color=fff&size=44' },
  { id: 3, name: 'Mehdi Amine', job: 'Data Analyst', lastMessage: 'Merci pour l\'opportunité !', time: 'Hier', unread: 0, avatar: 'https://ui-avatars.com/api/?name=Mehdi+Amine&background=059669&color=fff&size=44' },
])

const chatMessages = ref([
  { sender: 'other', text: 'Bonjour, je suis intéressé par le poste.' },
  { sender: 'me', text: 'Bonjour ! Merci pour votre candidature. Pouvez-vous me parler de votre expérience ?' },
  { sender: 'other', text: 'Bien sûr ! J\'ai 5 ans d\'expérience en développement web.' },
])

const filteredConversations = computed(() => {
  if (!searchConversation.value) return conversations.value
  return conversations.value.filter(c => c.name.toLowerCase().includes(searchConversation.value.toLowerCase()))
})

const sendMessageToCandidate = () => {
  if (!newMessage.value.trim()) return
  chatMessages.value.push({ sender: 'me', text: newMessage.value })
  newMessage.value = ''
}
</script>