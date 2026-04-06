import { describe, expect, it } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AdminFilterSelect from '@/components/Admin/AdminFilterSelect.vue'

const ElSelectV2Stub = {
  props: ['options'],
  template: `
    <button
      data-test="select"
      @click="$emit('update:model-value', options[0]?.value); $emit('change')"
    >
      {{ options.map((option) => option.label + ':' + option.value).join('|') }}
    </button>
  `,
}

describe('AdminFilterSelect', () => {
  it('normalizes options by label/value keys', () => {
    const wrapper = shallowMount(AdminFilterSelect, {
      props: {
        modelValue: null,
        options: [{ id: 2, name: 'Slovak' }],
        optionLabelKey: 'name',
        optionValueKey: 'id',
      },
      global: {
        stubs: {
          ElSelectV2: ElSelectV2Stub,
        },
      },
    })

    expect(wrapper.text()).toContain('Slovak:2')
  })

  it('emits model update and change from select', async () => {
    const wrapper = shallowMount(AdminFilterSelect, {
      props: {
        modelValue: null,
        options: [{ value: 'approved', label: 'Approved' }],
      },
      global: {
        stubs: {
          ElSelectV2: ElSelectV2Stub,
        },
      },
    })

    await wrapper.get('[data-test="select"]').trigger('click')

    expect(wrapper.emitted()['update:modelValue']?.[0]).toEqual(['approved'])
    expect(wrapper.emitted().change).toHaveLength(1)
  })
})
