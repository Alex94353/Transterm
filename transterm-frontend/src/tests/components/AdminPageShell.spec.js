import { describe, expect, it, vi } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AdminPageShell from '@/components/Admin/AdminPageShell.vue'

describe('AdminPageShell', () => {
  it('renders title and toolbar slot', () => {
    const wrapper = shallowMount(AdminPageShell, {
      props: { title: 'Editor Dashboard' },
      slots: {
        toolbar: '<span data-test="toolbar-slot">Toolbar</span>',
      },
      global: {
        stubs: {
          MainLayout: { template: '<div><slot /></div>' },
          ElButton: { template: '<button><slot /></button>' },
          ElCard: { template: '<section><slot name="header" /><slot /></section>' },
        },
      },
    })

    expect(wrapper.text()).toContain('Editor Dashboard')
    expect(wrapper.find('[data-test="toolbar-slot"]').exists()).toBe(true)
  })

  it('hides back button when showBack is false', () => {
    const back = vi.fn()
    const wrapper = shallowMount(AdminPageShell, {
      props: { title: 'Glossaries', showBack: false },
      global: {
        mocks: {
          $router: { back },
        },
        stubs: {
          MainLayout: { template: '<div><slot /></div>' },
          ElButton: { template: '<button data-test="back"><slot /></button>' },
          ElCard: { template: '<section><slot name="header" /><slot /></section>' },
        },
      },
    })

    expect(wrapper.find('[data-test="back"]').exists()).toBe(false)
    expect(back).not.toHaveBeenCalled()
  })
})
