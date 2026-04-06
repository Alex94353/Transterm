import { describe, expect, it } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AdminTableActions from '@/components/Admin/AdminTableActions.vue'

describe('AdminTableActions', () => {
  const buildWrapper = (props = {}) =>
    shallowMount(AdminTableActions, {
      props: {
        row: { id: 10 },
        ...props,
      },
      global: {
        stubs: {
          ElSpace: { template: '<div><slot /></div>' },
          ElButton: {
            template: '<button data-test="button"><slot /></button>',
          },
          ElPopconfirm: {
            template: `
              <div>
                <slot name="reference" />
                <button data-test="confirm" @click="$emit('confirm')">Confirm</button>
              </div>
            `,
          },
        },
      },
    })

  it('shows edit and delete controls by default', () => {
    const wrapper = buildWrapper()

    expect(wrapper.text()).toContain('Edit')
    expect(wrapper.text()).toContain('Delete')
  })

  it('emits edit with current row', async () => {
    const wrapper = buildWrapper()

    await wrapper.findAll('[data-test="button"]')[0].trigger('click')

    expect(wrapper.emitted().edit?.[0]).toEqual([{ id: 10 }])
  })

  it('emits delete when popconfirm is confirmed', async () => {
    const wrapper = buildWrapper()

    await wrapper.get('[data-test="confirm"]').trigger('click')

    expect(wrapper.emitted().delete?.[0]).toEqual([{ id: 10 }])
  })
})
