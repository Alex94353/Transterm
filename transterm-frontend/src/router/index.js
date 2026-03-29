import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '../stores/auth'


const HomePage = () => import('../pages/HomePage.vue')

const routes = [
  {
    path: '/',
    component: HomePage,
    meta: { title: 'Home' },
  },

]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})


export default router
