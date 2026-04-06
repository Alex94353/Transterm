import { describe, expect, it } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AdminFormDialog from '@/components/Admin/AdminFormDialog.vue'

const buildWrapper = (props = {}) =>
  shallowMount(AdminFormDialog, {
    props: {
      modelValue: true,
      title: 'Dialog',
      ...props,
    },
    global: {
      stubs: {
        ElDialog: { template: '<div><slot /><slot name="footer" /></div>' },
        ElButton: {
          template: '<button data-test="button"><slot /></button>',
        },
      },
    },
  })

describe('AdminFormDialog', () => {
  it('emits update:modelValue=false and cancel on cancel button', async () => {
    const wrapper = buildWrapper()

    await wrapper.findAll('[data-test="button"]')[0].trigger('click')

    expect(wrapper.emitted()['update:modelValue']?.[0]).toEqual([false])
    expect(wrapper.emitted().cancel).toHaveLength(1)
  })

  it('emits save on save button', async () => {
    const wrapper = buildWrapper()

    await wrapper.findAll('[data-test="button"]')[1].trigger('click')

    expect(wrapper.emitted().save).toHaveLength(1)
  })
})
