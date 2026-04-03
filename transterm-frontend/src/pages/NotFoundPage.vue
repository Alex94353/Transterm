<template>
  <main-layout>
    <el-card>
      <template #header>
        <div class="card-header">
          <span class="title">404 - Page Not Found</span>
        </div>
      </template>

      <div class="not-found-container">
        <div class="not-found-code">404</div>
        <h1>Page Not Found</h1>
        <p>Sorry, the page you are looking for does not exist.</p>
        <el-button type="primary" @click="$router.push('/')">
          Go Home
        </el-button>
      </div>
    </el-card>
  </main-layout>
</template>

<script setup>
import MainLayout from '../components/Layout/MainLayout.vue'
</script>

<style scoped>
.not-found-container {
  text-align: center;
  padding: 60px 20px;
}

.not-found-code {
  font-size: 120px;
  font-weight: bold;
  color: var(--tt-accent);
  margin-bottom: 20px;
}

h1 {
  font-size: 32px;
  margin-bottom: 20px;
  color: #303133;
}

p {
  font-size: 16px;
  color: #606266;
  margin-bottom: 30px;
}
</style>
