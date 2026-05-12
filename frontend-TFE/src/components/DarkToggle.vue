<template>
  <button class="dark-toggle-btn" @click="toggleDark" :title="darkMode ? 'Mode clair' : 'Mode sombre'">
    <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
  </button>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const darkMode = ref(false)

function toggleDark() {
  darkMode.value = !darkMode.value
  document.documentElement.classList.toggle('dark-mode', darkMode.value)
  localStorage.setItem('darkMode', darkMode.value ? '1' : '0')
}

onMounted(() => {
  if (localStorage.getItem('darkMode') === '1') {
    darkMode.value = true
    document.documentElement.classList.add('dark-mode')
  }
})
</script>

<style scoped>
.dark-toggle-btn {
  position: fixed;
  top: 16px;
  right: 16px;
  z-index: 9999;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 50%;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: white;
  font-size: 16px;
  transition: background 0.2s, transform 0.2s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.dark-toggle-btn:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(1.1);
}

html.dark-mode .dark-toggle-btn {
  background: rgba(0, 0, 0, 0.3);
  border-color: rgba(255, 255, 255, 0.15);
  color: #f1b800;
}

html.dark-mode .dark-toggle-btn:hover {
  background: rgba(0, 0, 0, 0.5);
}
</style>
