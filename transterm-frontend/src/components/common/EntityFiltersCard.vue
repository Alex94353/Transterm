<template>
  <el-card class="card-shadow filters-card">
    <el-form :model="stateModel" label-position="top" class="filters-grid">
      <el-form-item
        v-for="filter in filters"
        :key="filter.key"
        :label="filter.label"
        class="filter-item"
      >
        <el-input
          v-if="filter.type === 'text'"
          v-model="stateModel[filter.key]"
          :placeholder="filter.placeholder || ''"
          clearable
          @change="(value) => emitChange(filter, value)"
        />

        <el-input-number
          v-else-if="filter.type === 'number'"
          v-model="stateModel[filter.key]"
          :min="filter.min"
          :max="filter.max"
          controls-position="right"
          style="width: 100%"
          @change="(value) => emitChange(filter, value)"
        />

        <el-select
          v-else-if="filter.type === 'select'"
          v-model="stateModel[filter.key]"
          :clearable="filter.clearable ?? true"
          :multiple="Boolean(filter.multiple)"
          style="width: 100%"
          @change="(value) => emitChange(filter, value)"
        >
          <el-option
            v-for="option in filter.options || []"
            :key="String(option.value)"
            :label="option.label"
            :value="option.value"
          />
        </el-select>

        <el-cascader
          v-else-if="filter.type === 'cascader'"
          v-model="stateModel[filter.key]"
          :options="filter.options || []"
          :props="filter.props || {}"
          clearable
          style="width: 100%"
          @change="(value) => emitChange(filter, value)"
        />

        <el-switch
          v-else-if="filter.type === 'switch'"
          v-model="stateModel[filter.key]"
          @change="(value) => emitChange(filter, value)"
        />

        <el-date-picker
          v-else-if="filter.type === 'date'"
          v-model="stateModel[filter.key]"
          :type="filter.pickerType || 'date'"
          :value-format="filter.valueFormat || 'YYYY-MM-DD'"
          :clearable="filter.clearable ?? true"
          style="width: 100%"
          @change="(value) => emitChange(filter, value)"
        />
      </el-form-item>
    </el-form>

    <div class="filters-actions">
      <slot name="actions" />
    </div>
  </el-card>
</template>

<script setup>
const props = defineProps({
  model: {
    type: Object,
    required: true,
  },
  filters: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['change'])
const stateModel = props.model

function emitChange(filter, value) {
  emit('change', {
    filter,
    key: filter.key,
    value,
    model: props.model,
  })
}
</script>

<style scoped>
.filters-card {
  margin-bottom: 1rem;
}

.filters-grid {
  display: grid;
  gap: 0 12px;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

.filter-item {
  margin-bottom: 0.2rem;
}

.filters-actions {
  margin-top: 0.5rem;
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
}
</style>
