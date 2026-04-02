<template>
  <div class="auth-layout">
    <div class="auth-panel card-shadow">
      <div class="auth-brand">
        <h1>Transterm</h1>
        <p>Terminology platform frontend</p>
      </div>
      <router-view />
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
}

.auth-panel {
  width: 100%;
  max-width: 480px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid var(--tt-border);
  border-radius: 18px;
  padding: 1.5rem 1.5rem 1.2rem;
}

.auth-brand {
  margin-bottom: 1.25rem;
}

.auth-brand h1 {
  margin: 0;
  font-size: 1.8rem;
}

.auth-brand p {
  margin: 0.4rem 0 0;
  color: var(--tt-muted);
}
</style>

