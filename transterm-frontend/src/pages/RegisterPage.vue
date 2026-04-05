<template>
  <main-layout>
    <div class="auth-page">
      <el-card class="auth-card">
      <template #header>
        <div class="card-header">
          <span class="title">Register</span>
        </div>
      </template>

      <el-form
        ref="form"
        :model="formData"
        :rules="rules"
        @submit.prevent="handleRegister"
        label-width="100px"
      >
        <el-form-item label="Username" prop="username">
          <el-input
            v-model="formData.username"
            placeholder="Enter your username"
          />
        </el-form-item>

        <el-form-item label="Name" prop="name">
          <el-input
            v-model="formData.name"
            placeholder="Enter your name"
          />
        </el-form-item>

        <el-form-item label="Surname" prop="surname">
          <el-input
            v-model="formData.surname"
            placeholder="Enter your surname (optional)"
          />
        </el-form-item>

        <el-form-item label="Email" prop="email">
          <el-input
            v-model="formData.email"
            type="email"
            placeholder="Enter your UKF email"
          />
        </el-form-item>

        <el-form-item>
          <el-alert
            title="Use your institutional email: @student.ukf.sk or @ukf.sk"
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

        <el-form-item label="Confirm" prop="confirmPassword">
          <el-input
            v-model="formData.confirmPassword"
            type="password"
            placeholder="Confirm your password"
            show-password
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            @click="handleRegister"
            :loading="authStore.loading"
          >
            Register
          </el-button>
          <span class="register-link">
            Already have an account?
            <router-link to="/login">Login here</router-link>
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
  username: '',
  name: '',
  surname: '',
  email: '',
  password: '',
  confirmPassword: '',
})

const UKF_EMAIL_REGEX = /^[^@\s]+@(student\.ukf\.sk|ukf\.sk)$/i

const validateConfirmPassword = (rule, value, callback) => {
  if (value === '') {
    callback(new Error('Please confirm your password'))
  } else if (value !== formData.password) {
    callback(new Error('Passwords do not match'))
  } else {
    callback()
  }
}

const validateUkfEmail = (rule, value, callback) => {
  if (!value) {
    callback()
    return
  }

  if (!UKF_EMAIL_REGEX.test(value.trim())) {
    callback(new Error('Only @student.ukf.sk and @ukf.sk emails are allowed'))
    return
  }

  callback()
}

const rules = {
  username: [
    { required: true, message: 'Username is required', trigger: 'blur' },
    { min: 3, message: 'Username must be at least 3 characters', trigger: 'blur' },
  ],
  name: [
    { required: true, message: 'Name is required', trigger: 'blur' },
    { min: 3, message: 'Name must be at least 3 characters', trigger: 'blur' },
  ],
  email: [
    { required: true, message: 'Email is required', trigger: 'blur' },
    { type: 'email', message: 'Invalid email format', trigger: 'blur' },
    { validator: validateUkfEmail, trigger: 'blur' },
  ],
  password: [
    { required: true, message: 'Password is required', trigger: 'blur' },
    { min: 8, message: 'Password must be at least 8 characters', trigger: 'blur' },
  ],
  confirmPassword: [
    { required: true, validator: validateConfirmPassword, trigger: 'blur' },
  ],
}

const handleRegister = async () => {
  if (!form.value) return

  try {
    await form.value.validate()
    const result = await authStore.register({
      username: formData.username,
      name: formData.name,
      surname: formData.surname || null,
      email: formData.email.trim().toLowerCase(),
      password: formData.password,
      password_confirmation: formData.confirmPassword,
    })

    if (result?.requiresActivation) {
      ElMessage.warning('Account created. Please wait for activation by administrator.')
      router.push('/login')
      return
    }

    ElMessage.success('Registration successful!')
    router.push('/')
  } catch {
    ElMessage.error(authStore.error || 'Registration failed')
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

.register-link {
  margin-left: 20px;
  color: #606266;
}

.register-link a {
  color: var(--tt-accent);
  text-decoration: none;
}

.register-link a:hover {
  color: var(--tt-accent-hover);
  text-decoration: underline;
}

@media (min-width: 1024px) {
  .auth-page {
    padding-top: 24px;
  }
}
</style>
