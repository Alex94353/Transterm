<template>
  <main-layout>
    <div class="home-page">
      <el-card class="hero-card">
        <template #header>
          <div class="card-header">
            <span class="title">Welcome to Transterm</span>
          </div>
        </template>
        <div class="hero-content">
          <h1>Glossary Management System</h1>
          <p>
            Transterm is a powerful platform for managing terminology, glossaries, and
            translations across multiple languages.
          </p>
          <div class="hero-buttons">
            <el-button
              type="primary"
              size="large"
              @click="$router.push('/glossaries')"
            >
              Browse Glossaries
            </el-button>
            <el-button
              v-if="authStore.isEditor && authStore.canAccessManagement"
              type="warning"
              plain
              size="large"
              @click="$router.push('/admin')"
            >
              Management
            </el-button>
            <el-button
              v-if="!authStore.isAuthenticated"
              size="large"
              @click="$router.push('/register')"
            >
              Sign Up
            </el-button>
          </div>
        </div>
      </el-card>

      <div class="feature-stack">
        <el-card
          class="feature-card clickable-card"
          role="button"
          tabindex="0"
          @click="goToGlossaries"
          @keydown.enter="goToGlossaries"
        >
          <template #header>
            <div class="card-header">
              <el-icon><document-copy /></el-icon>
              <span>Glossaries</span>
            </div>
          </template>
          <p>Browse and explore specialized glossaries in multiple language pairs.</p>
        </el-card>

        <el-card
          class="feature-card clickable-card"
          role="button"
          tabindex="0"
          @click="goToTerms"
          @keydown.enter="goToTerms"
        >
          <template #header>
            <div class="card-header">
              <el-icon><search /></el-icon>
              <span>Search Terms</span>
            </div>
          </template>
          <p>Find specific terminology with powerful search and filtering options.</p>
        </el-card>

        <el-card
          class="feature-card clickable-card"
          role="button"
          tabindex="0"
          @click="goToCollaborate"
          @keydown.enter="goToCollaborate"
        >
          <template #header>
            <div class="card-header">
              <el-icon><chat-dot-round /></el-icon>
              <span>Collaborate</span>
            </div>
          </template>
          <p>Add comments and contribute to the community of translators.</p>
        </el-card>
      </div>
    </div>
  </main-layout>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import MainLayout from '../components/Layout/MainLayout.vue'
import { DocumentCopy, Search, ChatDotRound } from '@element-plus/icons-vue'

const router = useRouter()
const authStore = useAuthStore()

const goToGlossaries = () => {
  router.push('/glossaries')
}

const goToTerms = () => {
  router.push('/glossaries')
}

const goToCollaborate = () => {
  if (authStore.isAuthenticated) {
    router.push('/my-comments')
    return
  }

  router.push('/login')
}
</script>

<style scoped>
.home-page {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}

.hero-card {
  margin-bottom: 20px;
}

.feature-stack {
  width: 100%;
  display: grid;
  gap: 20px;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  align-items: stretch;
}

.hero-content {
  text-align: center;
  padding: 40px 20px;
}

.hero-content h1 {
  font-size: 36px;
  margin-bottom: 20px;
  color: var(--tt-ink);
}

.hero-content p {
  font-size: 16px;
  color: var(--tt-muted);
  margin-bottom: 30px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.hero-buttons {
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}

.feature-card {
  height: 100%;
  width: 100%;
}

.clickable-card {
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.clickable-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.14);
}

.clickable-card:focus-visible {
  outline: 2px solid var(--tt-accent);
  outline-offset: 2px;
}

.feature-card p {
  color: var(--tt-muted);
  line-height: 1.6;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 10px;
}

:deep(.el-icon) {
  font-size: 20px;
  color: var(--tt-accent);
}

:deep(.el-card__header) {
  border-bottom-color: var(--tt-border);
}

@media (max-width: 768px) {
  .home-page {
    max-width: 100%;
  }

  .feature-stack {
    grid-template-columns: 1fr;
  }

  .hero-content h1 {
    font-size: 24px;
  }

  .hero-content p {
    font-size: 14px;
  }
}
</style>
