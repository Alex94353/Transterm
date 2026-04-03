<template>
  <el-space :size="8" wrap>
    <el-input
      :model-value="modelValue"
      :placeholder="placeholder"
      clearable
      :style="{ width }"
      @update:model-value="(value) => $emit('update:modelValue', value)"
      @keyup.enter="$emit('search')"
      @clear="$emit('clear')"
    />
    <el-input
      v-if="showSecondary"
      :model-value="secondaryValue"
      :placeholder="secondaryPlaceholder"
      clearable
      :style="{ width: secondaryWidth }"
      @update:model-value="(value) => $emit('update:secondaryValue', value)"
      @keyup.enter="$emit('search')"
      @clear="$emit('clear')"
    />
    <el-button type="primary" @click="$emit('search')">{{ searchText }}</el-button>
  </el-space>
</template>

<script setup>
defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    default: 'Search',
  },
  width: {
    type: String,
    default: '260px',
  },
  showSecondary: {
    type: Boolean,
    default: false,
  },
  secondaryValue: {
    type: String,
    default: '',
  },
  secondaryPlaceholder: {
    type: String,
    default: 'Filter',
  },
  secondaryWidth: {
    type: String,
    default: '160px',
  },
  searchText: {
    type: String,
    default: 'Search',
  },
})

defineEmits(['update:modelValue', 'update:secondaryValue', 'search', 'clear'])
</script>
