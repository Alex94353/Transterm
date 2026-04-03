<template>
  <el-select-v2
    :model-value="modelValue"
    :options="normalizedOptions"
    :clearable="clearable"
    :placeholder="placeholder"
    :style="{ width }"
    @update:model-value="(value) => $emit('update:modelValue', value)"
    @change="$emit('change')"
  />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  },
  optionLabelKey: {
    type: String,
    default: 'label',
  },
  optionValueKey: {
    type: String,
    default: 'value',
  },
  width: {
    type: String,
    default: '180px',
  },
  placeholder: {
    type: String,
    default: 'Please select',
  },
  clearable: {
    type: Boolean,
    default: false,
  },
})

const normalizedOptions = computed(() =>
  (props.options || []).map((option) => ({
    label: option[props.optionLabelKey],
    value: option[props.optionValueKey],
  })),
)

defineEmits(['update:modelValue', 'change'])
</script>
