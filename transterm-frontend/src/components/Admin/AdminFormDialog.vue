<template>
  <el-dialog
    :model-value="modelValue"
    :title="title"
    :width="width"
    @update:model-value="(value) => $emit('update:modelValue', value)"
  >
    <slot />

    <template #footer>
      <slot name="footer">
        <el-button @click="handleCancel">{{ cancelText }}</el-button>
        <el-button type="primary" :loading="loading" @click="$emit('save')">
          {{ saveText }}
        </el-button>
      </slot>
    </template>
  </el-dialog>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  width: {
    type: String,
    default: '520px',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  cancelText: {
    type: String,
    default: 'Cancel',
  },
  saveText: {
    type: String,
    default: 'Save',
  },
})

const emit = defineEmits(['update:modelValue', 'save', 'cancel'])

const handleCancel = () => {
  emit('update:modelValue', false)
  emit('cancel')
}
</script>
