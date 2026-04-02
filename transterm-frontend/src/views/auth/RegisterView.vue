<template>
  <div>
    <h2 class="form-title">Create Account</h2>

    <el-alert
      v-if="errorMessage"
      type="error"
      :closable="false"
      show-icon
      :title="errorMessage"
      class="mb-12"
    />

    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-position="top"
      @submit.prevent="submit"
    >
      <el-form-item label="Username" prop="username" :error="firstServerError('username')">
        <el-input v-model="form.username" clearable />
      </el-form-item>

      <el-form-item label="Email" prop="email" :error="firstServerError('email')">
        <el-input v-model="form.email" clearable />
      </el-form-item>

      <el-form-item label="Name" prop="name" :error="firstServerError('name')">
        <el-input v-model="form.name" clearable />
      </el-form-item>

      <el-form-item label="Surname" prop="surname" :error="firstServerError('surname')">
        <el-input v-model="form.surname" clearable />
      </el-form-item>

      <el-form-item label="Password" prop="password" :error="firstServerError('password')">
        <el-input v-model="form.password" type="password" show-password clearable />
      </el-form-item>

      <el-form-item
        label="Confirm password"
        prop="password_confirmation"
        :error="firstServerError('password_confirmation')"
      >
        <el-input v-model="form.password_confirmation" type="password" show-password clearable />
      </el-form-item>

      <el-button
        type="primary"
        native-type="submit"
        :loading="loading"
        style="width: 100%"
      >
        Register
      </el-button>
    </el-form>

    <p class="auth-switch">
      Already registered?
      <router-link to="/login">Login</router-link>
    </p>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import { getApiErrorMessage, getValidationErrors } from '@/utils/errors'

const router = useRouter()
const authStore = useAuthStore()

const formRef = ref(null)
const loading = ref(false)
const errorMessage = ref('')
const serverErrors = ref({})

const form = reactive({
  username: '',
  email: '',
  name: '',
  surname: '',
  password: '',
  password_confirmation: '',
})

const rules = {
  username: [{ required: true, message: 'Username is required.', trigger: 'blur' }],
  email: [
    { required: true, message: 'Email is required.', trigger: 'blur' },
    { type: 'email', message: 'Invalid email.', trigger: 'blur' },
  ],
  name: [{ required: true, message: 'Name is required.', trigger: 'blur' }],
  password: [
    { required: true, message: 'Password is required.', trigger: 'blur' },
    { min: 8, message: 'Minimum 8 characters.', trigger: 'blur' },
  ],
  password_confirmation: [
    { required: true, message: 'Confirm your password.', trigger: 'blur' },
    {
      validator: (_, value, callback) => {
        if (value !== form.password) {
          callback(new Error('Passwords do not match.'))
          return
        }

        callback()
      },
      trigger: 'blur',
    },
  ],
}

function firstServerError(field) {
  return serverErrors.value?.[field]?.[0] || ''
}

async function submit() {
  if (!formRef.value) {
    return
  }

  serverErrors.value = {}
  errorMessage.value = ''

  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) {
    return
  }

  loading.value = true

  try {
    await authStore.register({ ...form })
    ElMessage.success('Registration successful.')
    await router.push('/terms')
  } catch (error) {
    serverErrors.value = getValidationErrors(error)
    errorMessage.value = getApiErrorMessage(error, 'Unable to register.')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.form-title {
  margin: 0 0 1rem;
}

.auth-switch {
  margin-top: 1rem;
  color: var(--tt-muted);
}

.auth-switch a {
  color: var(--tt-accent);
  font-weight: 600;
}

.mb-12 {
  margin-bottom: 12px;
}
</style>

