<template>
  <el-dialog
    :model-value="modelValue"
    :title="title"
    :width="width"
    destroy-on-close
    @update:model-value="(value) => $emit('update:modelValue', value)"
  >
    <el-form
      ref="formRef"
      :model="stateFormModel"
      :rules="rules"
      label-position="top"
    >
      <el-form-item
        v-for="field in fields"
        :key="field.key"
        :label="field.label"
        :prop="field.key"
        :error="serverErrors?.[field.key]?.[0] || ''"
      >
        <el-input
          v-if="field.type === 'text'"
          v-model="stateFormModel[field.key]"
          clearable
        />

        <el-input
          v-else-if="field.type === 'textarea'"
          v-model="stateFormModel[field.key]"
          type="textarea"
          :rows="field.rows || 4"
        />

        <el-switch
          v-else-if="field.type === 'switch'"
          v-model="stateFormModel[field.key]"
        />

        <el-input-number
          v-else-if="field.type === 'number'"
          v-model="stateFormModel[field.key]"
          :min="field.min ?? 0"
          :max="field.max"
          controls-position="right"
          style="width: 100%"
        />

        <el-cascader
          v-else-if="field.type === 'cascader'"
          v-model="stateFormModel[field.key]"
          :options="resolveOptions(field)"
          :props="field.props || {}"
          clearable
          style="width: 100%"
        />

        <el-select
          v-else-if="field.type === 'select'"
          v-model="stateFormModel[field.key]"
          :multiple="Boolean(field.multiple)"
          :clearable="!field.required"
          style="width: 100%"
        >
          <el-option
            v-for="option in resolveOptions(field)"
            :key="String(option.value)"
            :label="option.label"
            :value="option.value"
          />
        </el-select>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="$emit('cancel')">Cancel</el-button>
      <el-button type="primary" :loading="saving" @click="$emit('submit')">
        {{ submitLabel }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Edit',
  },
  width: {
    type: String,
    default: '640px',
  },
  submitLabel: {
    type: String,
    default: 'Save',
  },
  formModel: {
    type: Object,
    required: true,
  },
  fields: {
    type: Array,
    default: () => [],
  },
  rules: {
    type: Object,
    default: () => ({}),
  },
  serverErrors: {
    type: Object,
    default: () => ({}),
  },
  lookups: {
    type: Object,
    default: () => ({}),
  },
  saving: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:modelValue', 'cancel', 'submit'])

const formRef = ref(null)
const stateFormModel = props.formModel

function resolveOptions(field) {
  if (field.options) {
    return field.options
  }

  if (field.lookup) {
    return props.lookups?.[field.lookup] || []
  }

  return []
}

async function validate() {
  if (!formRef.value) {
    return false
  }

  const valid = await formRef.value.validate().catch(() => false)
  return Boolean(valid)
}

defineExpose({
  validate,
})
</script>
