<template>
  <el-drawer
    :model-value="modelValue"
    :title="title"
    :size="size"
    @update:model-value="(value) => $emit('update:modelValue', value)"
  >
    <pre class="json-preview">{{ JSON.stringify(data, null, 2) }}</pre>
  </el-drawer>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Details',
  },
  size: {
    type: String,
    default: '50%',
  },
  data: {
    type: [Object, Array, String, Number, Boolean, null],
    default: null,
  },
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.json-preview {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 12px;
  line-height: 1.4;
}
</style>

