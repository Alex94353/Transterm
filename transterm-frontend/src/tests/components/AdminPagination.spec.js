import { describe, expect, it } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AdminPagination from '@/components/Admin/AdminPagination.vue'

describe('AdminPagination', () => {
  it('emits model updates from pagination control', async () => {
    const wrapper = shallowMount(AdminPagination, {
      props: {
        currentPage: 2,
        pageSize: 10,
        total: 100,
      },
      global: {
        stubs: {
          ElPagination: {
            template: `
              <button
                data-test="pagination-update"
                @click="$emit('update:current-page', 5); $emit('update:page-size', 50)"
              >
                update
              </button>
            `,
          },
        },
      },
    })

    await wrapper.get('[data-test="pagination-update"]').trigger('click')

    expect(wrapper.emitted()['update:currentPage']?.[0]).toEqual([5])
    expect(wrapper.emitted()['update:pageSize']?.[0]).toEqual([50])
  })

  it('forwards current-change and size-change events', async () => {
    const wrapper = shallowMount(AdminPagination, {
      props: {
        currentPage: 1,
        pageSize: 10,
        total: 100,
      },
      global: {
        stubs: {
          ElPagination: {
            template: `
              <button
                data-test="pagination-events"
                @click="$emit('current-change', 3); $emit('size-change', 20)"
              >
                events
              </button>
            `,
          },
        },
      },
    })

    await wrapper.get('[data-test="pagination-events"]').trigger('click')

    expect(wrapper.emitted()['current-change']?.[0]).toEqual([3])
    expect(wrapper.emitted()['size-change']?.[0]).toEqual([20])
  })
})
