import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'

const shared = vi.hoisted(() => ({
  route: { query: {} },
  router: { push: vi.fn() },
  authStore: {
    loading: false,
    error: null,
    login: vi.fn(),
    resendVerificationEmail: vi.fn(),
  },
  message: {
    success: vi.fn(),
    warning: vi.fn(),
    error: vi.fn(),
  },
}))

vi.mock('vue-router', () => ({
  useRoute: () => shared.route,
  useRouter: () => shared.router,
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => shared.authStore,
}))

vi.mock('element-plus', () => ({
  ElMessage: shared.message,
}))

import LoginPage from '@/pages/LoginPage.vue'

const buildWrapper = () =>
  mount(LoginPage, {
    global: {
      stubs: {
        MainLayout: { template: '<div><slot /></div>' },
        ElCard: { template: '<section><slot name="header" /><slot /></section>' },
        ElForm: { template: '<form><slot /></form>' },
        ElFormItem: { template: '<div><slot /></div>' },
        ElInput: {
          props: ['modelValue'],
          template: `
            <input
              :value="modelValue"
              @input="$emit('update:modelValue', $event.target.value)"
            />
          `,
        },
        ElButton: {
          template: '<button @click="$emit(\'click\')"><slot /></button>',
        },
        ElAlert: {
          props: ['title'],
          template: '<div class="alert">{{ title }}</div>',
        },
        RouterLink: { template: '<a><slot /></a>' },
      },
    },
  })

describe('LoginPage', () => {
  beforeEach(() => {
    shared.route.query = {}
    shared.router.push.mockReset()
    shared.authStore.login.mockReset()
    shared.authStore.resendVerificationEmail.mockReset()
    shared.authStore.loading = false
    shared.authStore.error = null
    shared.message.success.mockReset()
    shared.message.warning.mockReset()
    shared.message.error.mockReset()
  })

  it('shows success verification banner for verification=success', () => {
    shared.route.query = { verification: 'success' }

    const wrapper = buildWrapper()

    expect(wrapper.text()).toContain('Email confirmed. Your account is activated, you can sign in now.')
  })

  it('shows invalid verification banner for verification=invalid', () => {
    shared.route.query = { verification: 'invalid' }

    const wrapper = buildWrapper()

    expect(wrapper.text()).toContain('Verification link is invalid or expired. Request a new activation email.')
  })

  it('shows warning if resend is requested without valid email', async () => {
    const wrapper = buildWrapper()

    const buttons = wrapper.findAll('button')
    const resendButton = buttons.find((button) => button.text().includes('Resend activation email'))
    await resendButton.trigger('click')

    expect(shared.authStore.resendVerificationEmail).not.toHaveBeenCalled()
    expect(shared.message.warning).toHaveBeenCalledWith(
      'Enter your email in Login field to resend activation email.',
    )
  })

  it('normalizes login email before resend', async () => {
    shared.authStore.resendVerificationEmail.mockResolvedValue({ message: 'ok' })
    const wrapper = buildWrapper()
    const inputs = wrapper.findAll('input')

    await inputs[0].setValue('  QA_User@Student.UKF.sk ')
    await nextTick()

    const buttons = wrapper.findAll('button')
    const resendButton = buttons.find((button) => button.text().includes('Resend activation email'))
    await resendButton.trigger('click')

    expect(shared.authStore.resendVerificationEmail).toHaveBeenCalledWith('qa_user@student.ukf.sk')
    expect(shared.message.success).toHaveBeenCalled()
  })
})
