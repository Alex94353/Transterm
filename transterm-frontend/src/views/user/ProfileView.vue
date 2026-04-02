<template>
  <div>
    <PageHeaderBlock
      title="My Profile"
      subtitle="Update your account information."
    />

    <el-row :gutter="16">
      <el-col :lg="14" :md="24">
        <el-card class="card-shadow" v-loading="loading">
          <el-form
            ref="formRef"
            :model="form"
            :rules="rules"
            label-position="top"
            @submit.prevent="save"
          >
            <el-form-item label="Username" prop="username" :error="firstServerError('username')">
              <el-input v-model="form.username" />
            </el-form-item>

            <el-form-item label="Email" prop="email" :error="firstServerError('email')">
              <el-input v-model="form.email" />
            </el-form-item>

            <el-form-item label="Name" prop="name" :error="firstServerError('name')">
              <el-input v-model="form.name" />
            </el-form-item>

            <el-form-item label="Surname" prop="surname" :error="firstServerError('surname')">
              <el-input v-model="form.surname" />
            </el-form-item>

            <el-form-item label="Language" prop="language" :error="firstServerError('language')">
              <el-input v-model="form.language" />
            </el-form-item>

            <el-form-item label="Public profile" prop="visible" :error="firstServerError('visible')">
              <el-switch v-model="form.visible" />
            </el-form-item>

            <el-button type="primary" :loading="saving" @click="save">
              Save changes
            </el-button>
          </el-form>
        </el-card>
      </el-col>

      <el-col :lg="10" :md="24">
        <el-card class="card-shadow side-card" v-loading="loading">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="User ID">{{ user?.id }}</el-descriptions-item>
            <el-descriptions-item label="Activated">{{ user?.activated ? 'Yes' : 'No' }}</el-descriptions-item>
            <el-descriptions-item label="Banned">{{ user?.banned ? 'Yes' : 'No' }}</el-descriptions-item>
            <el-descriptions-item label="Country">
              {{ user?.profile?.country?.name || user?.country?.name || '—' }}
            </el-descriptions-item>
            <el-descriptions-item label="Roles">
              <el-tag
                v-for="role in user?.roles || []"
                :key="role.id"
                class="mr-6 mb-6"
              >
                {{ role.name }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="Permissions">
              <el-tag
                v-for="permission in uniquePermissions"
                :key="permission"
                type="info"
                class="mr-6 mb-6"
              >
                {{ permission }}
              </el-tag>
            </el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import { userApi } from '@/api/user'
import { getApiErrorMessage, getValidationErrors } from '@/utils/errors'
import PageHeaderBlock from '@/components/common/PageHeaderBlock.vue'

const authStore = useAuthStore()
const formRef = ref(null)
const loading = ref(false)
const saving = ref(false)
const user = ref(null)
const serverErrors = ref({})

const form = reactive({
  username: '',
  email: '',
  name: '',
  surname: '',
  language: '',
  visible: false,
})

const rules = {
  username: [{ required: true, message: 'Username is required.', trigger: 'blur' }],
  email: [
    { required: true, message: 'Email is required.', trigger: 'blur' },
    { type: 'email', message: 'Invalid email.', trigger: 'blur' },
  ],
  name: [{ required: true, message: 'Name is required.', trigger: 'blur' }],
}

const uniquePermissions = computed(() => {
  const names = new Set()

  for (const role of user.value?.roles || []) {
    for (const permission of role?.permissions || []) {
      if (permission?.name) {
        names.add(permission.name)
      }
    }
  }

  return Array.from(names)
})

function firstServerError(field) {
  return serverErrors.value?.[field]?.[0] || ''
}

function fillForm(nextUser) {
  form.username = nextUser?.username || ''
  form.email = nextUser?.email || ''
  form.name = nextUser?.name || ''
  form.surname = nextUser?.surname || ''
  form.language = nextUser?.language || ''
  form.visible = Boolean(nextUser?.visible)
}

async function loadProfile() {
  loading.value = true

  try {
    user.value = await userApi.getProfile()
    fillForm(user.value)
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to load profile.'))
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!formRef.value) {
    return
  }

  serverErrors.value = {}
  const valid = await formRef.value.validate().catch(() => false)

  if (!valid) {
    return
  }

  saving.value = true

  try {
    const payload = {
      username: form.username,
      email: form.email,
      name: form.name,
      surname: form.surname || null,
      language: form.language || null,
      visible: form.visible,
    }

    const response = await userApi.updateProfile(payload)
    user.value = response.user
    fillForm(response.user)
    authStore.user = response.user
    ElMessage.success(response.message || 'Profile updated.')
  } catch (error) {
    serverErrors.value = getValidationErrors(error)
    ElMessage.error(getApiErrorMessage(error, 'Unable to save profile.'))
  } finally {
    saving.value = false
  }
}

loadProfile()
</script>

<style scoped>
.side-card {
  margin-top: 0;
}

.mr-6 {
  margin-right: 6px;
}

.mb-6 {
  margin-bottom: 6px;
}

@media (max-width: 992px) {
  .side-card {
    margin-top: 1rem;
  }
}
</style>
