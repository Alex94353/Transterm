<template>
  <el-space :size="8" wrap>
    <el-select-v2
      :model-value="modelValue"
      :options="idOrderOptions"
      placeholder="Please select"
      :style="{ width }"
      @update:model-value="(value) => $emit('update:modelValue', value)"
      @change="$emit('change')"
    />

    <el-button v-if="showReset" @click="$emit('reset')">{{ resetText }}</el-button>
  </el-space>
</template>

<script setup>
const idOrderOptions = [
  { label: 'ID: Newest', value: 'desc' },
  { label: 'ID: Oldest', value: 'asc' },
]

defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  width: {
    type: String,
    default: '150px',
  },
  showReset: {
    type: Boolean,
    default: false,
  },
  resetText: {
    type: String,
    default: 'Reset',
  },
})

defineEmits(['update:modelValue', 'change', 'reset'])
</script>
