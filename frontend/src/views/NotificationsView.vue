<template>
  <DashboardLayout
    :user-name="userName"
    :user-role="libelleRole"
    page-title="Notifications"
    page-subtitle="Les événements qui concernent vos candidatures et vos offres"
  >
    <template #header-actions>
      <button
        @click="toutMarquerLu"
        :disabled="nonLues === 0"
        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 disabled:opacity-50 text-gray-700 text-sm font-medium rounded-xl transition-all"
      >
        Tout marquer comme lu
      </button>
    </template>

    <div class="max-w-3xl mx-auto">
      <div class="flex gap-2 mb-4">
        <button
          v-for="f in filtres"
          :key="f.valeur"
          @click="changerFiltre(f.valeur)"
          :class="['px-4 py-2 rounded-xl text-sm font-medium transition-all', filtre === f.valeur ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50']"
        >
          {{ f.label }}<span v-if="f.valeur === 'non_lues' && nonLues"> ({{ nonLues }})</span>
        </button>
      </div>

      <div v-if="chargement" class="text-center py-12 text-gray-400">Chargement...</div>
      <div v-else-if="erreur" class="p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">{{ erreur }}</div>

      <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-100">
        <div
          v-for="n in notifications"
          :key="n.id_notification"
          :class="['flex items-start gap-4 p-4', n.lu ? '' : 'bg-blue-50/40']"
        >
          <span :class="['mt-1.5 w-2 h-2 rounded-full shrink-0', n.lu ? 'bg-transparent' : 'bg-blue-600']" :aria-label="n.lu ? 'Lue' : 'Non lue'"></span>
          <div class="flex-1 min-w-0">
            <p :class="['text-sm', n.lu ? 'text-gray-600' : 'text-gray-900 font-medium']">{{ n.message }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ formatDate(n.date_envoi) }}</p>
          </div>
          <button
            v-if="!n.lu"
            @click="marquerLue(n)"
            class="shrink-0 text-xs font-medium text-blue-600 hover:text-blue-700"
          >
            Marquer comme lue
          </button>
        </div>
        <p v-if="notifications.length === 0" class="p-10 text-center text-sm text-gray-400">
          {{ filtre === 'non_lues' ? 'Aucune notification non lue.' : 'Aucune notification pour le moment.' }}
        </p>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const userName = computed(() => authStore.user?.nom_complet || '')
const libelleRole = computed(() => ({
  candidat: 'Candidat',
  recruteur: 'Recruteur',
  administrateur: 'Administrateur',
})[authStore.user?.role] || 'Recruteur')

const filtres = [
  { label: 'Toutes', valeur: 'toutes' },
  { label: 'Non lues', valeur: 'non_lues' },
]
const filtre = ref('toutes')
const notifications = ref([])
const nonLues = ref(0)
const chargement = ref(true)
const erreur = ref('')

// RG44 : un utilisateur ne voit que ses propres notifications.
const charger = async () => {
  chargement.value = true
  erreur.value = ''
  try {
    const params = { per_page: 50 }
    if (filtre.value === 'non_lues') params.non_lues = 1
    const response = await api.get('/notifications', { params })
    notifications.value = response.data.notifications || []
    nonLues.value = response.data.non_lues ?? 0
  } catch (err) {
    erreur.value = err.response?.data?.message || 'Impossible de charger les notifications.'
  } finally {
    chargement.value = false
  }
}

const changerFiltre = (valeur) => {
  filtre.value = valeur
  charger()
}

const marquerLue = async (n) => {
  try {
    await api.patch(`/notifications/${n.id_notification}/lue`)
    n.lu = true
    nonLues.value = Math.max(0, nonLues.value - 1)
    if (filtre.value === 'non_lues') {
      notifications.value = notifications.value.filter(x => x.id_notification !== n.id_notification)
    }
  } catch (err) {
    alert('❌ ' + (err.response?.data?.message || 'Action impossible.'))
  }
}

const toutMarquerLu = async () => {
  try {
    await api.post('/notifications/toutes-lues')
    await charger()
  } catch (err) {
    alert('❌ ' + (err.response?.data?.message || 'Action impossible.'))
  }
}

const formatDate = (iso) => new Date(iso).toLocaleString('fr-FR', {
  day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
})

onMounted(charger)
</script>
