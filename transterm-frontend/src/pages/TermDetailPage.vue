<template>
  <main-layout>
    <el-button @click="$router.back()" icon="arrow-left" text>Back</el-button>

    <el-card v-if="glossaryStore.currentTerm" style="margin-top: 20px">
      <template #header>
        <div class="card-header">
          <span class="title">{{ glossaryStore.currentTerm.name }}</span>
        </div>
      </template>

      <el-row :gutter="20" style="margin-bottom: 30px">
        <el-col :xs="24" :md="16">
          <div class="term-details">
            <h3>Definition</h3>
            <p>{{ glossaryStore.currentTerm.definition }}</p>

            <h3 style="margin-top: 20px">Details</h3>
            <el-descriptions :column="1" border>
              <el-descriptions-item v-if="glossaryStore.currentTerm.glossary" label="Glossary">
                {{ glossaryStore.currentTerm.glossary.name }}
              </el-descriptions-item>
              <el-descriptions-item label="Created">
                {{ new Date(glossaryStore.currentTerm.created_at).toLocaleDateString() }}
              </el-descriptions-item>
              <el-descriptions-item v-if="glossaryStore.currentTerm.updated_at" label="Updated">
                {{ new Date(glossaryStore.currentTerm.updated_at).toLocaleDateString() }}
              </el-descriptions-item>
            </el-descriptions>
          </div>
        </el-col>

        <el-col :xs="24" :md="8">
          <el-card class="reference-card">
            <template #header>
              <span>Translations</span>
            </template>
            <el-empty
              v-if="!glossaryStore.currentTerm.translations || glossaryStore.currentTerm.translations.length === 0"
              description="No translations"
            />
            <div v-else>
              <div
                v-for="translation in glossaryStore.currentTerm.translations"
                :key="translation.id"
                class="translation-item"
              >
                <strong>{{ translation.language?.name }}</strong>
                <p>{{ translation.content }}</p>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <el-divider />

      <h3>Comments</h3>

      <el-form
        v-if="authStore.isAuthenticated"
        @submit.prevent="handleAddComment"
        style="margin-bottom: 30px"
      >
        <el-form-item>
          <el-input
            v-model="commentForm.content"
            type="textarea"
            placeholder="Add a comment..."
            :rows="4"
          />
        </el-form-item>
        <el-button type="primary" @click="handleAddComment" :loading="isSubmitting">
          Add Comment
        </el-button>
      </el-form>

      <el-empty
        v-if="!glossaryStore.currentTerm.comments || glossaryStore.currentTerm.comments.length === 0"
        description="No comments yet"
      />

      <div v-else class="comments-list">
        <div
          v-for="comment in glossaryStore.currentTerm.comments"
          :key="comment.id"
          class="comment-item"
        >
          <div class="comment-header">
            <strong>{{ comment.user?.name }}</strong>
            <span class="comment-date">{{
              new Date(comment.created_at).toLocaleDateString()
            }}</span>
          </div>
          <p class="comment-content">{{ comment.content }}</p>
        </div>
      </div>
    </el-card>

    <el-skeleton v-else animated :count="3" />
  </main-layout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useGlossaryStore } from '../stores/glossary'
import { ElMessage } from 'element-plus'
import MainLayout from '../components/Layout/MainLayout.vue'

const route = useRoute()
const authStore = useAuthStore()
const glossaryStore = useGlossaryStore()
const isSubmitting = ref(false)

const commentForm = reactive({
  content: '',
})

watch(
  () => route.params.id,
  (id) => {
    commentForm.content = ''
    glossaryStore.fetchTerm(id)
  },
  { immediate: true },
)

const handleAddComment = async () => {
  if (!commentForm.content.trim()) {
    ElMessage.warning('Please enter a comment')
    return
  }

  isSubmitting.value = true
  try {
    await glossaryStore.addComment(route.params.id, {
      body: commentForm.content,
    })
    ElMessage.success('Comment added successfully')
    commentForm.content = ''
    glossaryStore.fetchTerm(route.params.id)
  } catch {
    ElMessage.error('Failed to add comment')
  } finally {
    isSubmitting.value = false
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

.term-details h3 {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 10px;
  color: #303133;
}

.term-details p {
  color: #606266;
  line-height: 1.6;
}

.reference-card {
  height: fit-content;
}

.translation-item {
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #ebeef5;
}

.translation-item strong {
  display: block;
  color: #303133;
  margin-bottom: 5px;
}

.translation-item p {
  color: #606266;
  margin: 0;
}

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.comment-item {
  border: 1px solid #ebeef5;
  border-radius: 4px;
  padding: 15px;
  background-color: #fafafa;
}

.comment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.comment-header strong {
  color: #303133;
}

.comment-date {
  color: #909399;
  font-size: 12px;
}

.comment-content {
  color: #606266;
  line-height: 1.6;
  margin: 0;
}
</style>
