<template>
  <main-layout>
    <div class="auth-page">
      <el-card class="auth-card">
      <template #header>
        <div class="card-header">
          <span class="title">Login</span>
        </div>
      </template>

      <el-form
        ref="form"
        :model="formData"
        :rules="rules"
        @submit.prevent="handleLogin"
        label-width="100px"
      >
        <el-form-item v-if="verificationMessage">
          <el-alert
            :title="verificationMessage"
            :type="verificationType"
            :closable="false"
            show-icon
          />
        </el-form-item>

        <el-form-item label="Login" prop="login">
          <el-input
            v-model="formData.login"
            placeholder="Enter email or username"
          />
        </el-form-item>

        <el-form-item>
          <el-alert
            title="Use your account email or username"
            type="info"
            :closable="false"
            show-icon
          />
        </el-form-item>

        <el-form-item label="Password" prop="password">
          <el-input
            v-model="formData.password"
            type="password"
            placeholder="Enter your password"
            show-password
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            @click="handleLogin"
            :loading="authStore.loading"
          >
            Login
          </el-button>
          <span class="login-link">
            Don't have an account?
            <router-link to="/register">Register here</router-link>
          </span>
        </el-form-item>

        <el-form-item>
          <el-button
            type="info"
            text
            @click="handleResendVerificationEmail"
            :loading="authStore.loading"
          >
            Resend activation email
          </el-button>
        </el-form-item>

        <el-alert
          v-if="authStore.error"
          :title="authStore.error"
          type="error"
          closable
        />
      </el-form>
      </el-card>
    </div>
  </main-layout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { ElMessage } from 'element-plus'
import MainLayout from '../components/Layout/MainLayout.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const form = ref()

const formData = reactive({
  login: '',
  password: '',
})

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const verificationStatus = computed(() => String(route.query.verification || '').toLowerCase())

const verificationMessage = computed(() => {
  if (verificationStatus.value === 'success') {
    return 'Email confirmed. Your account is activated, you can sign in now.'
  }

  if (verificationStatus.value === 'already') {
    return 'This account is already activated. You can sign in.'
  }

  if (verificationStatus.value === 'invalid') {
    return 'Verification link is invalid or expired. Request a new activation email.'
  }

  return ''
})

const verificationType = computed(() => {
  if (verificationStatus.value === 'invalid') {
    return 'warning'
  }

  return 'success'
})

const rules = {
  login: [
    { required: true, message: 'Login is required', trigger: 'blur' },
  ],
  password: [
    { required: true, message: 'Password is required', trigger: 'blur' },
    { min: 8, message: 'Password must be at least 8 characters', trigger: 'blur' },
  ],
}

const handleLogin = async () => {
  if (!form.value) return

  const isValid = await Promise.resolve(form.value.validate())
    .then((result) => result !== false)
    .catch(() => false)
  if (!isValid) {
    return
  }

  try {
    await authStore.login(formData.login, formData.password)
    ElMessage.success('Login successful!')
    router.push('/')
  } catch {
    ElMessage.error(authStore.error || 'Login failed')
  }
}

const handleResendVerificationEmail = async () => {
  const email = formData.login.trim().toLowerCase()

  if (!EMAIL_REGEX.test(email)) {
    ElMessage.warning('Enter your email in Login field to resend activation email.')
    return
  }

  try {
    await authStore.resendVerificationEmail(email)
    ElMessage.success('If the account exists and is not activated, verification email has been sent.')
  } catch {
    ElMessage.error(authStore.error || 'Failed to resend verification email')
  }
}
</script>

<style scoped>
.auth-page {
  width: 100%;
  max-width: 820px;
  margin: 0 auto;
}

.auth-card {
  width: 100%;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.title {
  font-size: 18px;
  font-weight: bold;
}

:deep(.el-form) {
  max-width: 400px;
  margin: 0 auto;
}

.login-link {
  margin-left: 20px;
  color: #606266;
}

.login-link a {
  color: var(--tt-accent);
  text-decoration: none;
}

.login-link a:hover {
  color: var(--tt-accent-hover);
  text-decoration: underline;
}

@media (min-width: 1024px) {
  .auth-page {
    padding-top: 24px;
  }
}
</style>
