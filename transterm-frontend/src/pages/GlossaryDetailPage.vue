<template>
  <main-layout>
    <el-button @click="$router.back()" icon="arrow-left" text>Back</el-button>

    <el-card v-if="glossaryStore.currentGlossary" style="margin-top: 20px">
      <template #header>
        <div class="card-header">
          <span class="title">{{ glossaryStore.currentGlossary.name }}</span>
        </div>
      </template>

      <el-row :gutter="20" style="margin-bottom: 30px">
        <el-col :xs="24" :md="16">
          <div class="glossary-description">
            <h3>Description</h3>
            <p>{{ glossaryStore.currentGlossary.description }}</p>
          </div>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-card class="info-card">
            <template #header>
              <span>Information</span>
            </template>
            <el-descriptions :column="1" border>
              <el-descriptions-item
                v-if="glossaryStore.currentGlossary.language_pair"
                label="Language Pair"
              >
                {{
                  glossaryStore.currentGlossary.language_pair.source_language?.name
                }}
                →
                {{
                  glossaryStore.currentGlossary.language_pair.target_language?.name
                }}
              </el-descriptions-item>
              <el-descriptions-item label="Created">
                {{
                  new Date(
                    glossaryStore.currentGlossary.created_at
                  ).toLocaleDateString()
                }}
              </el-descriptions-item>
            </el-descriptions>
          </el-card>
        </el-col>
      </el-row>

      <el-divider />

      <div class="terms-header">
        <h3>Terms</h3>
        <el-tag type="info">{{ filteredTerms.length }} found</el-tag>
      </div>
      <el-input
        v-model="searchTerm"
        placeholder="Search terms..."
        style="margin-bottom: 20px"
        clearable
      />

      <el-empty
        v-if="filteredTerms.length === 0"
        description="No terms found"
      />

      <el-table
        v-else
        :data="filteredTerms"
        stripe
        style="width: 100%"
      >
        <el-table-column prop="name" label="Term" width="200" />
        <el-table-column prop="definition" label="Definition" show-overflow-tooltip />
        <el-table-column label="Action" width="100">
          <template #default="{ row }">
            <el-button
              type="primary"
              text
              @click="$router.push(`/terms/${row.id}`)"
            >
              View
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div v-if="glossaryStore.hasMoreGlossaryTerms" class="load-more-wrap">
        <el-button
          type="primary"
          plain
          :loading="glossaryStore.loadingMoreGlossaryTerms"
          @click="handleLoadMore"
        >
          Load more terms
        </el-button>
      </div>
    </el-card>

    <el-skeleton
      v-else
      animated
      :count="3"
    />
  </main-layout>
</template>

<script setup>
import { watch, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useGlossaryStore } from '../stores/glossary'
import MainLayout from '../components/Layout/MainLayout.vue'

const route = useRoute()
const glossaryStore = useGlossaryStore()
const searchTerm = ref('')

const filteredTerms = computed(() => {
  if (!glossaryStore.currentGlossary?.terms) return []
  return glossaryStore.currentGlossary.terms.filter((term) => {
    const query = searchTerm.value.toLowerCase()
    return (
      term.name.toLowerCase().includes(query) ||
      (term.definition && term.definition.toLowerCase().includes(query))
    )
  })
})

watch(
  () => route.params.id,
  (id) => {
    searchTerm.value = ''
    glossaryStore.fetchGlossary(id)
  },
  { immediate: true },
)

const handleLoadMore = async () => {
  try {
    await glossaryStore.loadMoreGlossaryTerms()
  } catch {
    // error is already handled in the store
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
  font-size: 24px;
  font-weight: bold;
  color: #303133;
}

.glossary-description {
  margin-bottom: 20px;
}

.glossary-description h3 {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 10px;
  color: #303133;
}

.glossary-description p {
  color: #606266;
  line-height: 1.6;
}

.info-card {
  height: fit-content;
}

.terms-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.load-more-wrap {
  margin-top: 16px;
  display: flex;
  justify-content: center;
}
</style>
