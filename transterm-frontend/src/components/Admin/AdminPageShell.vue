<template>
  <main-layout>
    <el-button v-if="showBack" @click="$router.back()" icon="arrow-left" text>Back</el-button>

    <el-card class="admin-page-card">
      <template #header>
        <div class="card-header">
          <span class="title">{{ title }}</span>
          <slot name="toolbar" />
        </div>
      </template>

      <slot />
    </el-card>
  </main-layout>
</template>

<script setup>
import MainLayout from '../Layout/MainLayout.vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  showBack: {
    type: Boolean,
    default: true,
  },
})
</script>

<style scoped>
.admin-page-card {
  margin-top: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 12px;
}

.title {
  font-size: 18px;
  font-weight: bold;
}
</style>
