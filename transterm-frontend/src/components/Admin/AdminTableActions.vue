<template>
  <el-space :size="8" wrap>
    <slot name="prepend" :row="row" />

    <el-button
      v-if="showEdit"
      type="primary"
      text
      size="small"
      @click="$emit('edit', row)"
    >
      {{ editText }}
    </el-button>

    <el-popconfirm v-if="showDelete" :title="deleteConfirm" @confirm="$emit('delete', row)">
      <template #reference>
        <el-button type="danger" text size="small">{{ deleteText }}</el-button>
      </template>
    </el-popconfirm>

    <slot name="append" :row="row" />
  </el-space>
</template>

<script setup>
defineProps({
  row: {
    type: Object,
    required: true,
  },
  showEdit: {
    type: Boolean,
    default: true,
  },
  showDelete: {
    type: Boolean,
    default: true,
  },
  editText: {
    type: String,
    default: 'Edit',
  },
  deleteText: {
    type: String,
    default: 'Delete',
  },
  deleteConfirm: {
    type: String,
    default: 'Delete this item?',
  },
})

defineEmits(['edit', 'delete'])
</script>
