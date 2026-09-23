import { describe, it, expect } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import App from '../App.vue'

// App ne fait que monter le routeur : on vérifie que la route courante s'affiche.
describe('App', () => {
  it('affiche le composant de la route courante', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/', component: { template: '<p>Page de connexion</p>' } }],
    })
    router.push('/')
    await router.isReady()

    const wrapper = mount(App, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.text()).toContain('Page de connexion')
  })
})
