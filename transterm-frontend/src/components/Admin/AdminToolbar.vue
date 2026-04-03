<template>
  <el-space class="toolbar" wrap :size="10">
    <el-text v-if="label" size="small" class="toolbar-label">{{ label }}</el-text>
    <slot />
  </el-space>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    default: '',
  },
})
</script>

<style scoped>
.toolbar {
  justify-content: flex-end;
  width: 100%;
}

.toolbar-label {
  color: #606266;
}
</style>
