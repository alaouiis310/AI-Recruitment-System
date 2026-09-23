<template>
  <div
    v-if="ouvert"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40"
    @click.self="$emit('fermer')"
    @keydown.esc="$emit('fermer')"
  >
    <div role="dialog" aria-modal="true" :aria-label="titre" class="w-full max-w-lg max-h-[85vh] overflow-y-auto bg-white rounded-2xl shadow-xl">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">{{ titre }}</h3>
        <button ref="boutonFermer" @click="$emit('fermer')" aria-label="Fermer" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
      </div>
      <dl class="px-6 py-4 divide-y divide-gray-50">
        <div v-for="ligne in lignesAffichees" :key="ligne.label" class="py-2.5 grid grid-cols-3 gap-4">
          <dt class="text-sm text-gray-500">{{ ligne.label }}</dt>
          <dd class="col-span-2 text-sm text-gray-800 whitespace-pre-line break-words">{{ ligne.valeur }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, nextTick } from 'vue'

const props = defineProps({
  ouvert: { type: Boolean, default: false },
  titre: { type: String, default: 'Détail' },
  // [{ label, valeur }] — les valeurs vides sont masquées.
  lignes: { type: Array, default: () => [] },
})
defineEmits(['fermer'])

const lignesAffichees = computed(() =>
  props.lignes.filter(l => l.valeur !== null && l.valeur !== undefined && l.valeur !== '')
)

// Le focus passe dans la fenêtre à l'ouverture, pour la navigation au clavier.
const boutonFermer = ref(null)
watch(() => props.ouvert, async (ouvert) => {
  if (ouvert) {
    await nextTick()
    boutonFermer.value?.focus()
  }
})
</script>
