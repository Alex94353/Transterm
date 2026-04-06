import { describe, expect, it } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AdminSearchBar from '@/components/Admin/AdminSearchBar.vue'

const ElInputStub = {
  template: `
    <button
      data-test="input"
      @click="$emit('update:model-value', 'typed'); $emit('clear')"
    >
      Input
    </button>
  `,
}

describe('AdminSearchBar', () => {
  it('renders secondary field only when enabled', () => {
    const baseWrapper = shallowMount(AdminSearchBar, {
      props: { modelValue: '' },
      global: {
        stubs: {
          ElSpace: { template: '<div><slot /></div>' },
          ElInput: ElInputStub,
          ElButton: { template: '<button data-test="search"><slot /></button>' },
        },
      },
    })

    const secondaryWrapper = shallowMount(AdminSearchBar, {
      props: { modelValue: '', showSecondary: true, secondaryValue: '' },
      global: {
        stubs: {
          ElSpace: { template: '<div><slot /></div>' },
          ElInput: ElInputStub,
          ElButton: { template: '<button><slot /></button>' },
        },
      },
    })

    expect(baseWrapper.findAll('[data-test="input"]')).toHaveLength(1)
    expect(secondaryWrapper.findAll('[data-test="input"]')).toHaveLength(2)
  })

  it('emits update, search and clear from input interactions', async () => {
    const wrapper = shallowMount(AdminSearchBar, {
      props: { modelValue: '', showSecondary: true, secondaryValue: '' },
      global: {
        stubs: {
          ElSpace: { template: '<div><slot /></div>' },
          ElInput: ElInputStub,
          ElButton: { template: '<button><slot /></button>' },
        },
      },
    })

    await wrapper.findAll('[data-test="input"]')[0].trigger('click')

    expect(wrapper.emitted()['update:modelValue']?.[0]).toEqual(['typed'])
    expect(wrapper.emitted()['update:secondaryValue']).toBeUndefined()
    expect(wrapper.emitted().clear).toHaveLength(1)
  })

  it('emits search on search button click', async () => {
    const wrapper = shallowMount(AdminSearchBar, {
      props: { modelValue: '', searchText: 'Go' },
      global: {
        stubs: {
          ElSpace: { template: '<div><slot /></div>' },
          ElInput: ElInputStub,
          ElButton: { template: '<button data-test="search"><slot /></button>' },
        },
      },
    })

    await wrapper.get('[data-test="search"]').trigger('click')

    expect(wrapper.emitted().search).toHaveLength(1)
  })
})
