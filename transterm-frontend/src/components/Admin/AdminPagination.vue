<template>
  <div class="pagination-wrap">
    <el-pagination
      v-model:current-page="currentPageProxy"
      v-model:page-size="pageSizeProxy"
      :page-sizes="pageSizes"
      :total="total"
      layout="total, sizes, prev, pager, next"
      @current-change="(page) => $emit('current-change', page)"
      @size-change="(size) => $emit('size-change', size)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  pageSize: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
  pageSizes: {
    type: Array,
    default: () => [10, 20, 50],
  },
})

const emit = defineEmits(['update:currentPage', 'update:pageSize', 'current-change', 'size-change'])

const currentPageProxy = computed({
  get: () => props.currentPage,
  set: (value) => emit('update:currentPage', value),
})

const pageSizeProxy = computed({
  get: () => props.pageSize,
  set: (value) => emit('update:pageSize', value),
})
</script>

<style scoped>
.pagination-wrap {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}
</style>
