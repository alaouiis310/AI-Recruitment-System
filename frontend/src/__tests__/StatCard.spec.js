import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatCard from '@/components/common/StatCard.vue'

describe('StatCard', () => {
  it('affiche le titre et la valeur fournis en props', () => {
    const wrapper = mount(StatCard, {
      props: { titre: 'Candidatures', valeur: 42, icone: 'users' },
    })
    expect(wrapper.text()).toContain('Candidatures')
    expect(wrapper.text()).toContain('42')
  })

  it('affiche la tendance quand elle est fournie', () => {
    const wrapper = mount(StatCard, {
      props: { titre: 'Offres', valeur: 10, tendance: '+5%' },
    })
    expect(wrapper.text()).toContain('+5%')
  })

  it('masque la tendance quand elle est absente', () => {
    const wrapper = mount(StatCard, {
      props: { titre: 'Offres', valeur: 10 },
    })
    expect(wrapper.find('[data-test="tendance"]').exists()).toBe(false)
  })
})