<template>
  <div>
    <PageHeaderBlock
      :title="entity?.title || 'Catalog'"
      :subtitle="entity?.description || ''"
    />

    <EntityFiltersCard
      :model="filters"
      :filters="entity?.filters || []"
    >
      <template #actions>
        <el-button type="primary" @click="applyFilters">Apply filters</el-button>
        <el-button @click="resetFilters">Reset</el-button>
      </template>
    </EntityFiltersCard>

    <ServerTableCard
      :rows="rows"
      :columns="entity?.columns || []"
      :loading="loading"
      :renderer="renderValueByColumn"
      :page="page"
      :per-page="perPage"
      :total="total"
      :actions-width="120"
      @update:page="changePage"
      @update:perPage="changePerPage"
    >
      <template #actions="{ row }">
        <el-button type="primary" link @click="openDetails(row)">Open</el-button>
      </template>
    </ServerTableCard>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getPublicEntity, renderValueByColumn } from '@/config/publicEntities'
import { cleanQuery } from '@/utils/object'
import { getApiErrorMessage } from '@/utils/errors'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { resetFilterModel } from '@/composables/useFilters'
import PageHeaderBlock from '@/components/common/PageHeaderBlock.vue'
import EntityFiltersCard from '@/components/common/EntityFiltersCard.vue'
import ServerTableCard from '@/components/common/ServerTableCard.vue'

const route = useRoute()
const router = useRouter()

const entityKey = computed(() => route.meta?.entityKey)
const entity = computed(() => getPublicEntity(entityKey.value))
const {
  loading,
  rows,
  page,
  perPage,
  total,
  runPageRequest,
  resetPage,
  resetPagination,
  handlePageChange,
  handlePerPageChange,
} = usePaginatedList(10)

const filters = reactive({})

function setupFilters() {
  resetFilterModel(filters, entity.value?.filters || [])
}

async function load() {
  if (!entity.value) {
    return
  }

  try {
    await runPageRequest(() =>
      entity.value.list(
        cleanQuery({
          ...filters,
          page: page.value,
          per_page: perPage.value,
        }),
      ),
    )
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to load data.'))
  }
}

function applyFilters() {
  resetPage()
  return load()
}

function resetFilters() {
  setupFilters()
  resetPage()
  return load()
}

function changePage(value) {
  return handlePageChange(value, load)
}

function changePerPage(value) {
  return handlePerPageChange(value, load)
}

function openDetails(row) {
  router.push(`/${entityKey.value}/${row.id}`)
}

watch(
  entity,
  () => {
    setupFilters()
    resetPagination()
    return load()
  },
  { immediate: true },
)
</script>
