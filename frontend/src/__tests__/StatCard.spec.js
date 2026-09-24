import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatCard from '@/components/StatCard.vue'

describe('StatCard', () => {
  // -----------------------------------------------------------------
  // Affichage des props obligatoires
  // -----------------------------------------------------------------

  it('affiche le label et la valeur fournis en props', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Candidatures', value: 42 },
    })

    expect(wrapper.text()).toContain('Candidatures')
    expect(wrapper.text()).toContain('42')
  })

  it('accepte une valeur numerique', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Offres', value: 128 },
    })

    expect(wrapper.text()).toContain('128')
  })

  it('accepte une valeur textuelle', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Statut', value: 'Actif' },
    })

    expect(wrapper.text()).toContain('Actif')
  })

  // -----------------------------------------------------------------
  // Icone
  // -----------------------------------------------------------------

  it('affiche l icone par defaut si aucune n est fournie', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Test', value: 1 },
    })

    expect(wrapper.text()).toContain('📊')
  })

  it('affiche l icone fournie en props', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Offres', value: 10, icon: '🎯' },
    })

    expect(wrapper.text()).toContain('🎯')
  })

  // -----------------------------------------------------------------
  // Tendance (trend)
  // -----------------------------------------------------------------

  it('n affiche pas la tendance quand elle est absente', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Offres', value: 10 },
    })

    expect(wrapper.text()).not.toContain('+5%')
    expect(wrapper.text()).not.toContain('ce mois')
  })

  it('affiche la tendance quand elle est fournie', () => {
    const wrapper = mount(StatCard, {
      props: {
        label: 'Offres',
        value: 10,
        trend: '+5%',
        trendLabel: 'ce mois',
      },
    })

    expect(wrapper.text()).toContain('+5%')
    expect(wrapper.text()).toContain('ce mois')
  })

  it('affiche une tendance negative en rouge', () => {
    const wrapper = mount(StatCard, {
      props: {
        label: 'Candidatures',
        value: 5,
        trend: '-3%',
        trendPositive: false,
      },
    })

    const trendSpan = wrapper.find('.text-red-600')
    expect(trendSpan.exists()).toBe(true)
    expect(trendSpan.text()).toContain('-3%')
  })

  it('affiche une tendance positive en vert', () => {
    const wrapper = mount(StatCard, {
      props: {
        label: 'Candidatures',
        value: 50,
        trend: '+12%',
        trendPositive: true,
      },
    })

    const trendSpan = wrapper.find('.text-green-600')
    expect(trendSpan.exists()).toBe(true)
    expect(trendSpan.text()).toContain('+12%')
  })

  // -----------------------------------------------------------------
  // Classes CSS personnalisees
  // -----------------------------------------------------------------

  it('applique cardClass personnalise', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Test', value: 1, cardClass: 'custom-class' },
    })

    expect(wrapper.find('.custom-class').exists()).toBe(true)
  })

  it('applique iconBg personnalise', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Test', value: 1, iconBg: 'bg-red-100' },
    })

    expect(wrapper.find('.bg-red-100').exists()).toBe(true)
  })

  // -----------------------------------------------------------------
  // Structure du composant
  // -----------------------------------------------------------------

  it('affiche le label en petit texte gris', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Candidatures', value: 42 },
    })

    const label = wrapper.find('.text-sm.text-gray-500')
    expect(label.exists()).toBe(true)
    expect(label.text()).toBe('Candidatures')
  })

  it('affiche la valeur en gros texte noir', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Candidatures', value: 42 },
    })

    const value = wrapper.find('.text-2xl.text-gray-900')
    expect(value.exists()).toBe(true)
    expect(value.text()).toBe('42')
  })
})