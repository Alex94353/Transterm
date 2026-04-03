<template>
  <main-layout>
    <el-card>
      <template #header>
        <div class="card-header">
          <span class="title">Glossaries</span>
          <el-input
            v-model="glossaryStore.filters.search"
            placeholder="Search glossaries..."
            style="width: 300px"
            @input="handleSearch"
            clearable
          />
        </div>
      </template>

      <el-empty
        v-if="!glossaryStore.loading && glossaryStore.glossaries.length === 0"
        description="No glossaries found"
      />

      <el-row v-else :gutter="20">
        <el-col
          v-for="glossary in glossaryStore.glossaries"
          :key="glossary.id"
          :xs="24"
          :sm="12"
          :md="8"
        >
          <el-card class="glossary-card">
            <template #header>
              <div class="card-header">
                <span class="glossary-name">{{ glossary.name }}</span>
                <el-tag :type="termCount(glossary) > 0 ? 'success' : 'warning'" size="small">
                  {{ termCount(glossary) }} terms
                </el-tag>
              </div>
            </template>

            <div class="glossary-content">
              <p class="glossary-description">{{ glossary.description }}</p>
              <div class="glossary-info">
                <span v-if="glossary.language_pair">
                  <el-icon><connection /></el-icon>
                  {{ glossary.language_pair.source_language?.name }} →
                  {{ glossary.language_pair.target_language?.name }}
                </span>
              </div>
              <el-button
                type="primary"
                @click="$router.push(`/glossaries/${glossary.id}`)"
              >
                View
              </el-button>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <div v-if="glossaryStore.loading" class="loading-spinner">
        <el-icon class="is-loading">
          <loading />
        </el-icon>
        Loading glossaries...
      </div>
    </el-card>
  </main-layout>
</template>

<script setup>
import { onMounted } from 'vue'
import { useGlossaryStore } from '../stores/glossary'
import MainLayout from '../components/Layout/MainLayout.vue'
import { Connection, Loading } from '@element-plus/icons-vue'

const glossaryStore = useGlossaryStore()

onMounted(() => {
  glossaryStore.fetchGlossaries()
})

const handleSearch = () => {
  glossaryStore.fetchGlossaries()
}

const termCount = (glossary) => {
  return Number(glossary?.terms_count ?? 0)
}
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.title {
  font-size: 18px;
  font-weight: bold;
  flex: 1;
  min-width: 150px;
}

.glossary-card {
  height: 100%;
  cursor: pointer;
  transition: all 0.3s ease;
}

.glossary-card:hover {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.glossary-name {
  font-size: 16px;
  font-weight: bold;
  color: var(--tt-ink);
}

.glossary-content {
  height: auto;
}

.glossary-description {
  color: var(--tt-muted);
  line-height: 1.5;
  margin-bottom: 10px;
  min-height: 40px;
}

.glossary-info {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 15px;
  color: var(--tt-muted);
  font-size: 14px;
}

.loading-spinner {
  text-align: center;
  padding: 40px;
  color: var(--tt-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

:global(html.dark-theme) .glossary-name,
:global(html.dark-theme) .glossary-description,
:global(html.dark-theme) .glossary-info,
:global(html.dark-theme) .loading-spinner {
  color: var(--tt-ink);
}

:deep(.el-icon) {
  font-size: 24px;
}

:deep(.is-loading) {
  animation: rotating 2s linear infinite;
}

@keyframes rotating {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
