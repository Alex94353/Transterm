<template>
  <div class="app-container">
    <navbar />
    <el-main class="main-content">
      <slot>
        <router-view />
      </slot>
    </el-main>
    <el-footer class="app-footer">
      <p>&copy; {{ currentYear }} Transterm. All rights reserved.</p>
    </el-footer>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import Navbar from './Navbar.vue'

const authStore = useAuthStore()
const currentYear = new Date().getFullYear()

onMounted(async () => {
  if (authStore.token && !authStore.user) {
    try {
      await authStore.getCurrentUser()
    } catch (err) {
      console.error('Failed to load user:', err)
    }
  }
})
</script>

<style scoped>
.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  padding: 20px;
}

.app-footer {
  background-color: var(--tt-surface);
  text-align: center;
  padding: 20px;
  border-top: 1px solid var(--tt-border);
  color: var(--tt-muted);
}
</style>
