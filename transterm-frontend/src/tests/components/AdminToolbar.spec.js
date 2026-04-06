import { describe, expect, it } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AdminToolbar from '@/components/Admin/AdminToolbar.vue'

describe('AdminToolbar', () => {
  it('renders label when provided', () => {
    const wrapper = shallowMount(AdminToolbar, {
      props: { label: 'Filters' },
      global: {
        stubs: {
          ElSpace: { template: '<div><slot /></div>' },
          ElText: { template: '<span><slot /></span>' },
        },
      },
    })

    expect(wrapper.text()).toContain('Filters')
  })

  it('renders default slot content', () => {
    const wrapper = shallowMount(AdminToolbar, {
      slots: {
        default: '<button data-test="custom-btn">Action</button>',
      },
      global: {
        stubs: {
          ElSpace: { template: '<div><slot /></div>' },
          ElText: { template: '<span><slot /></span>' },
        },
      },
    })

    expect(wrapper.find('[data-test="custom-btn"]').exists()).toBe(true)
  })
})
