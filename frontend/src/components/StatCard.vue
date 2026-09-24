<template>
  <div :class="['bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200', cardClass]">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">{{ label }}</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ value }}</p>
        <div v-if="trend" class="flex items-center gap-1 mt-2">
          <span :class="['text-xs font-medium', trendPositive ? 'text-green-600' : 'text-red-600']">
            {{ trend }}
          </span>
          <span class="text-xs text-gray-400">{{ trendLabel }}</span>
        </div>
      </div>
      <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-xl', iconBg]">
        {{ icon }}
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  icon: {
    type: String,
    default: '📊'
  },
  iconBg: {
    type: String,
    default: 'bg-blue-50'
  },
  trend: {
    type: String,
    default: ''
  },
  trendLabel: {
    type: String,
    default: 'ce mois'
  },
  trendPositive: {
    type: Boolean,
    default: true
  },
  cardClass: {
    type: String,
    default: ''
  }
})
</script>