<template>
  <main-layout>
    <el-card>
      <template #header>
        <div class="card-header">
          <span class="title">My Profile</span>
        </div>
      </template>

      <el-row v-if="authStore.user" :gutter="20">
        <el-col :xs="24" :md="12">
          <el-form
            ref="form"
            :model="formData"
            :rules="rules"
            label-width="120px"
            @submit.prevent="handleUpdate"
          >
            <el-form-item label="Name" prop="name">
              <el-input v-model="formData.name" />
            </el-form-item>

            <el-form-item label="Email" prop="email">
              <el-input v-model="formData.email" type="email" disabled />
            </el-form-item>

            <el-form-item label="Bio" prop="bio">
              <el-input
                v-model="formData.bio"
                type="textarea"
                placeholder="Add a bio..."
              />
            </el-form-item>

            <el-form-item>
              <el-button
                type="primary"
                @click="handleUpdate"
                :loading="isLoading"
              >
                Update Profile
              </el-button>
            </el-form-item>
          </el-form>
        </el-col>

        <el-col :xs="24" :md="12">
          <el-card class="profile-card">
            <template #header>
              <span>Account Information</span>
            </template>
            <el-descriptions :column="1" border>
              <el-descriptions-item label="User ID">
                {{ authStore.user.id }}
              </el-descriptions-item>
              <el-descriptions-item label="Roles">
                <el-tag
                  v-for="role in (authStore.user.roles || [])"
                  :key="role.id"
                  style="margin-right: 5px"
                >
                  {{ role.name }}
                </el-tag>
                <span v-if="!authStore.user.roles || authStore.user.roles.length === 0">No roles</span>
              </el-descriptions-item>
              <el-descriptions-item label="Status">
                <el-tag :type="userStatusType">
                  {{ userStatusLabel }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Member Since">
                {{ new Date(authStore.user.created_at).toLocaleDateString() }}
              </el-descriptions-item>
            </el-descriptions>
          </el-card>
        </el-col>
      </el-row>

      <el-alert
        v-if="error"
        :title="error"
        type="error"
        closable
        @close="error = ''"
      />
    </el-card>
  </main-layout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { ElMessage } from 'element-plus'
import MainLayout from '../components/Layout/MainLayout.vue'
import userService from '../services/userService'

const authStore = useAuthStore()
const form = ref()
const isLoading = ref(false)
const error = ref('')

const formData = reactive({
  name: authStore.user?.name || '',
  email: authStore.user?.email || '',
  bio: authStore.user?.profile?.about || '',
})

const rules = {
  name: [
    { required: true, message: 'Name is required', trigger: 'blur' },
    { min: 3, message: 'Name must be at least 3 characters', trigger: 'blur' },
  ],
}

const userStatus = computed(() => {
  if (!authStore.user) return 'unknown'
  if (authStore.user.banned) return 'banned'
  return authStore.user.activated ? 'active' : 'inactive'
})

const userStatusLabel = computed(() => {
  if (userStatus.value === 'banned') return 'Banned'
  if (userStatus.value === 'active') return 'Active'
  return 'Inactive'
})

const userStatusType = computed(() => {
  if (userStatus.value === 'banned') return 'danger'
  return userStatus.value === 'active' ? 'success' : 'warning'
})

const handleUpdate = async () => {
  if (!form.value) return

  try {
    await form.value.validate()
    isLoading.value = true
    const response = await userService.updateProfile({
      name: formData.name,
      bio: formData.bio,
    })

    if (response?.data?.user) {
      authStore.user = response.data.user
      formData.bio = response.data.user?.profile?.about || ''
    }

    ElMessage.success('Profile updated successfully')
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update profile'
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.title {
  font-size: 18px;
  font-weight: bold;
}

.profile-card {
  height: fit-content;
}
</style>
