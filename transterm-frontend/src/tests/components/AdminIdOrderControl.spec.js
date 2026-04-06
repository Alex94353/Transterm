import { describe, expect, it } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AdminIdOrderControl from '@/components/Admin/AdminIdOrderControl.vue'

describe('AdminIdOrderControl', () => {
  it('emits update:modelValue and change from select', async () => {
    const wrapper = shallowMount(AdminIdOrderControl, {
      props: { modelValue: 'desc' },
      global: {
        stubs: {
          ElSpace: { template: '<div><slot /></div>' },
          ElSelectV2: {
            template: `
              <button
                data-test="select"
                @click="$emit('update:model-value', 'asc'); $emit('change')"
              >
                Select
              </button>
            `,
          },
          ElButton: { template: '<button><slot /></button>' },
        },
      },
    })

    await wrapper.get('[data-test="select"]').trigger('click')

    expect(wrapper.emitted()['update:modelValue']?.[0]).toEqual(['asc'])
    expect(wrapper.emitted().change).toHaveLength(1)
  })

  it('shows reset button and emits reset when enabled', async () => {
    const wrapper = shallowMount(AdminIdOrderControl, {
      props: { modelValue: 'desc', showReset: true, resetText: 'Reset all' },
      global: {
        stubs: {
          ElSpace: { template: '<div><slot /></div>' },
          ElSelectV2: { template: '<div />' },
          ElButton: { template: '<button data-test="reset"><slot /></button>' },
        },
      },
    })

    expect(wrapper.text()).toContain('Reset all')
    await wrapper.get('[data-test="reset"]').trigger('click')

    expect(wrapper.emitted().reset).toHaveLength(1)
  })
})
