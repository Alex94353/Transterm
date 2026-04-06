import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h, nextTick } from 'vue'
import { mount } from '@vue/test-utils'

const shared = vi.hoisted(() => ({
  router: { push: vi.fn() },
  authStore: {
    loading: false,
    error: null,
    register: vi.fn(),
  },
  message: {
    success: vi.fn(),
    warning: vi.fn(),
    error: vi.fn(),
  },
  validate: vi.fn(async () => true),
}))

vi.mock('vue-router', () => ({
  useRouter: () => shared.router,
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => shared.authStore,
}))

vi.mock('element-plus', () => ({
  ElMessage: shared.message,
}))

import RegisterPage from '@/pages/RegisterPage.vue'

const ElFormStub = defineComponent({
  setup(_, { slots, expose }) {
    expose({
      validate: () => shared.validate(),
    })

    return () => h('form', {}, slots.default ? slots.default() : [])
  },
})

const buildWrapper = () =>
  mount(RegisterPage, {
    global: {
      stubs: {
        MainLayout: { template: '<div><slot /></div>' },
        ElCard: { template: '<section><slot name="header" /><slot /></section>' },
        ElForm: ElFormStub,
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

describe('RegisterPage', () => {
  beforeEach(() => {
    shared.router.push.mockReset()
    shared.authStore.register.mockReset()
    shared.authStore.loading = false
    shared.authStore.error = null
    shared.message.success.mockReset()
    shared.message.warning.mockReset()
    shared.message.error.mockReset()
    shared.validate.mockReset()
    shared.validate.mockResolvedValue(true)
  })

  const fillForm = async (wrapper) => {
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('qa_user')
    await inputs[1].setValue('QA')
    await inputs[2].setValue('Tester')
    await inputs[3].setValue('  QA_User@Student.UKF.sk ')
    await inputs[4].setValue('Password123!')
    await inputs[5].setValue('Password123!')
    await nextTick()
  }

  it('submits normalized payload to auth store register', async () => {
    shared.authStore.register.mockResolvedValue({ requiresActivation: false })
    const wrapper = buildWrapper()
    await fillForm(wrapper)

    const registerButton = wrapper.findAll('button').find((button) => button.text().includes('Register'))
    await registerButton.trigger('click')

    expect(shared.authStore.register).toHaveBeenCalledWith({
      username: 'qa_user',
      name: 'QA',
      surname: 'Tester',
      email: 'qa_user@student.ukf.sk',
      password: 'Password123!',
      password_confirmation: 'Password123!',
    })
  })

  it('redirects to login when activation is required', async () => {
    shared.authStore.register.mockResolvedValue({ requiresActivation: true })
    const wrapper = buildWrapper()
    await fillForm(wrapper)

    const registerButton = wrapper.findAll('button').find((button) => button.text().includes('Register'))
    await registerButton.trigger('click')

    expect(shared.message.warning).toHaveBeenCalledWith(
      'Account created. Check your email and confirm activation link.',
    )
    expect(shared.router.push).toHaveBeenCalledWith('/login')
  })

  it('redirects to home on fully successful registration', async () => {
    shared.authStore.register.mockResolvedValue({ requiresActivation: false })
    const wrapper = buildWrapper()
    await fillForm(wrapper)

    const registerButton = wrapper.findAll('button').find((button) => button.text().includes('Register'))
    await registerButton.trigger('click')

    expect(shared.message.success).toHaveBeenCalledWith('Registration successful!')
    expect(shared.router.push).toHaveBeenCalledWith('/')
  })
})
