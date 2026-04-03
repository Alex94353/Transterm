<template>
  <router-view />
</template>

<script setup>
import { onBeforeMount } from 'vue'

const applyTheme = () => {
  const savedTheme = localStorage.getItem('theme') || 'light'
  document.documentElement.classList.toggle('dark-theme', savedTheme === 'dark')
}

onBeforeMount(() => {
  applyTheme()
})
</script>
