import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './assets/dark-mode.css'

const app = createApp(App)

app.use(router) // <-- on l'ajoute à l'application
app.mount('#app')
