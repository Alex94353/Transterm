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
        <el-form-item label="Login" prop="login">
          <el-input
            v-model="formData.login"
            placeholder="Enter UKF email or username"
          />
        </el-form-item>

        <el-form-item>
          <el-alert
            title="Sign in with @student.ukf.sk or @ukf.sk account"
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
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { ElMessage } from 'element-plus'
import MainLayout from '../components/Layout/MainLayout.vue'

const router = useRouter()
const authStore = useAuthStore()
const form = ref()

const formData = reactive({
  login: '',
  password: '',
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

  try {
    await form.value.validate()
    await authStore.login(formData.login, formData.password)
    ElMessage.success('Login successful!')
    router.push('/')
  } catch {
    ElMessage.error(authStore.error || 'Login failed')
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
