<template>
  <div>
    <h2 class="form-title">Sign In</h2>

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
      <el-form-item label="Login (username or email)" prop="login" :error="firstServerError('login')">
        <el-input
          v-model="form.login"
          placeholder="Enter username or email"
          clearable
        />
      </el-form-item>

      <el-form-item label="Password" prop="password" :error="firstServerError('password')">
        <el-input
          v-model="form.password"
          type="password"
          placeholder="Enter password"
          show-password
          clearable
        />
      </el-form-item>

      <el-button
        type="primary"
        native-type="submit"
        :loading="loading"
        style="width: 100%"
      >
        Login
      </el-button>
    </el-form>

    <p class="auth-switch">
      No account yet?
      <router-link to="/register">Register</router-link>
    </p>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import { getApiErrorMessage, getValidationErrors } from '@/utils/errors'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const formRef = ref(null)
const loading = ref(false)
const errorMessage = ref('')
const serverErrors = ref({})

const form = reactive({
  login: '',
  password: '',
})

const rules = {
  login: [{ required: true, message: 'Login is required.', trigger: 'blur' }],
  password: [{ required: true, message: 'Password is required.', trigger: 'blur' }],
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
    await authStore.login({ ...form })

    ElMessage.success('Login successful.')
    const next = route.query.redirect || '/terms'
    await router.push(next)
  } catch (error) {
    serverErrors.value = getValidationErrors(error)
    errorMessage.value = getApiErrorMessage(error, 'Unable to login.')
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

