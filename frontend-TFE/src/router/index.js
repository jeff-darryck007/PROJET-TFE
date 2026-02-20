import { createRouter, createWebHistory } from "vue-router";

// Tes pages
import LoginView from "@/views/Login.vue";
import ProfileView from "@/views/user/ProfileView.vue";
import Register from "@/views/Register.vue";
import DashboardView from "@/views/DashboardView.vue";
import ForgotPassword from "@/views/ForgotPassword.vue";
import NotFound from "@/views/NotFound.vue";
import Home from "@/views/Home.vue";

const routes = [
  {
    path: "/",
    name: "dashboard",
    component: DashboardView,
  },
  {
    path: "/login",
    name: "login",
    component: LoginView,
  },
  {
    path: "/ProfileView",
    name: "profile-view",
    component: ProfileView,
  },
  {
    path: "/register",
    name: "register",
    component: Register,
  },
  {
    path: "/home",
    name: "home",
    component: Home,
  },
  {
    path: "/forgot-password",
    name: "forgot-password",
    component: ForgotPassword,
  },
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: NotFound,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
