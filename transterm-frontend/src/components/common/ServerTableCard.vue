<template>
  <el-card class="card-shadow table-card">
    <el-table
      :data="rows"
      :row-key="rowKey"
      v-loading="loading"
      stripe
    >
      <el-table-column
        v-for="column in columns"
        :key="column.key + (column.label || '')"
        :prop="column.key"
        :label="column.label"
        :width="column.width"
        :min-width="column.minWidth || minColumnWidth"
      >
        <template #default="{ row, $index }">
          <slot name="cell" :row="row" :column="column" :index="$index">
            {{ defaultCell(row, column) }}
          </slot>
        </template>
      </el-table-column>

      <el-table-column
        v-if="hasActions"
        :label="actionsLabel"
        :width="actionsWidth"
        :fixed="actionsFixed"
      >
        <template #default="{ row, $index }">
          <slot name="actions" :row="row" :index="$index" />
        </template>
      </el-table-column>
    </el-table>

    <div v-if="showPagination" class="pagination-wrap">
      <el-pagination
        background
        :layout="paginationLayout"
        :current-page="page"
        :page-size="perPage"
        :page-sizes="pageSizes"
        :total="total"
        @update:current-page="(value) => $emit('update:page', value)"
        @update:page-size="(value) => $emit('update:perPage', value)"
      />
    </div>
  </el-card>
</template>

<script setup>
import { computed, useSlots } from 'vue'
import { resolvePath } from '@/utils/object'

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
  columns: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  rowKey: {
    type: [String, Function],
    default: 'id',
  },
  page: {
    type: Number,
    default: 1,
  },
  perPage: {
    type: Number,
    default: 10,
  },
  total: {
    type: Number,
    default: 0,
  },
  showPagination: {
    type: Boolean,
    default: true,
  },
  pageSizes: {
    type: Array,
    default: () => [10, 20, 50, 100],
  },
  paginationLayout: {
    type: String,
    default: 'total, sizes, prev, pager, next',
  },
  renderer: {
    type: Function,
    default: null,
  },
  minColumnWidth: {
    type: Number,
    default: 130,
  },
  actionsLabel: {
    type: String,
    default: 'Actions',
  },
  actionsWidth: {
    type: Number,
    default: 160,
  },
  actionsFixed: {
    type: String,
    default: 'right',
  },
})

defineEmits(['update:page', 'update:perPage'])

const hasActions = computed(() => Boolean(useSlots().actions))

function defaultCell(row, column) {
  if (typeof props.renderer === 'function') {
    return props.renderer(row, column)
  }

  return resolvePath(row, column?.key, '-')
}
</script>

<style scoped>
.table-card {
  overflow: hidden;
}

.pagination-wrap {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
}
</style>
