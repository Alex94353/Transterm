<template>
  <main-layout>
    <el-card>
      <template #header>
        <div class="card-header">
          <span class="title">My Comments</span>
        </div>
      </template>

      <el-empty
        v-if="!isLoading && comments.length === 0"
        description="No comments yet"
      />

      <div v-else class="comments-list">
        <div
          v-for="comment in comments"
          :key="comment.id"
          class="comment-card"
        >
          <div class="comment-header">
            <div>
              <strong class="comment-term">{{ getTermLabel(comment) }}</strong>
              <p class="comment-glossary">
                {{ getGlossaryLabel(comment) }}
              </p>
            </div>
            <span class="comment-date">{{
              new Date(comment.created_at).toLocaleDateString()
            }}</span>
          </div>
          <p class="comment-content">{{ comment.content }}</p>
          <div class="comment-actions">
            <el-button
              type="primary"
              text
              size="small"
              @click="handleEdit(comment)"
            >
              Edit
            </el-button>
            <el-popconfirm
              title="Delete this comment?"
              @confirm="handleDelete(comment.id)"
            >
              <template #reference>
                <el-button type="danger" text size="small">
                  Delete
                </el-button>
              </template>
            </el-popconfirm>
          </div>
        </div>
      </div>

      <el-dialog v-model="editDialogVisible" title="Edit Comment">
        <el-form>
          <el-form-item label="Comment">
            <el-input
              v-model="editForm.content"
              type="textarea"
              :rows="4"
            />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="editDialogVisible = false">Cancel</el-button>
          <el-button
            type="primary"
            @click="handleSaveEdit"
            :loading="isSubmitting"
          >
            Save
          </el-button>
        </template>
      </el-dialog>

      <div v-if="isLoading" class="loading-spinner">
        <el-icon class="is-loading">
          <loading />
        </el-icon>
        Loading comments...
      </div>
    </el-card>
  </main-layout>
</template>

<script setup>
import { onMounted, ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import MainLayout from '../components/Layout/MainLayout.vue'
import userService from '../services/userService'
import { Loading } from '@element-plus/icons-vue'

const comments = ref([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const editDialogVisible = ref(false)
const editingCommentId = ref(null)

const editForm = reactive({
  content: '',
})

onMounted(() => {
  fetchComments()
})

const fetchComments = async () => {
  isLoading.value = true
  try {
    const response = await userService.getUserComments()
    const items = response.data.data || response.data || []
    comments.value = Array.isArray(items)
      ? items.map((comment) => ({
        ...comment,
        content: comment.body || '',
      }))
      : []
  } catch (err) {
    const message = err.response?.data?.message || 'Failed to load comments'
    ElMessage.error(message)
  } finally {
    isLoading.value = false
  }
}

const handleEdit = (comment) => {
  editingCommentId.value = comment.id
  editForm.content = comment.body || comment.content || ''
  editDialogVisible.value = true
}

const handleSaveEdit = async () => {
  if (!editForm.content.trim()) {
    ElMessage.warning('Please enter a comment')
    return
  }

  isSubmitting.value = true
  try {
    await userService.updateComment(editingCommentId.value, {
      body: editForm.content,
    })
    ElMessage.success('Comment updated successfully')
    editDialogVisible.value = false
    fetchComments()
  } catch {
    ElMessage.error('Failed to update comment')
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async (commentId) => {
  try {
    await userService.deleteComment(commentId)
    ElMessage.success('Comment deleted successfully')
    fetchComments()
  } catch {
    ElMessage.error('Failed to delete comment')
  }
}

const getTermLabel = (comment) => {
  return comment?.term?.name || `Term #${comment?.term_id ?? '-'}`
}

const getGlossaryLabel = (comment) => {
  return comment?.term?.glossary?.name || '-'
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

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.comment-card {
  border: 1px solid #ebeef5;
  border-radius: 4px;
  padding: 15px;
  background-color: #fafafa;
}

.comment-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 10px;
}

.comment-term {
  display: block;
  color: #303133;
  font-size: 16px;
  margin-bottom: 5px;
}

.comment-glossary {
  color: #909399;
  font-size: 12px;
  margin: 0;
}

.comment-date {
  color: #909399;
  font-size: 12px;
  white-space: nowrap;
}

.comment-content {
  color: #606266;
  line-height: 1.6;
  margin: 0 0 10px 0;
}

.comment-actions {
  display: flex;
  gap: 10px;
}

.loading-spinner {
  text-align: center;
  padding: 40px;
  color: #606266;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
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
