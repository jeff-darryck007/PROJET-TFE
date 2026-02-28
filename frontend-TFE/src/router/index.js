import { createRouter, createWebHistory } from "vue-router";

// Tes pages
import LoginView from "@/views/Login.vue";
import ProfileView from "@/views/user/ProfileView.vue";
import FavorisView from "@/views/user/FavorisView.vue";
import Register from "@/views/Register.vue";
import DashboardView from "@/views/DashboardView.vue";
import ForgotPassword from "@/views/ForgotPassword.vue";
import NotFound from "@/views/NotFound.vue";
import Home from "@/views/Home.vue";
import PublierView from "@/views/user/PublierView.vue";
import MessageView from "@/views/user/MessageView.vue";
import NotificationView from "@/views/user/NotificationView.vue";


const routes = [
  {
    path: "/",
    name: "dashboard",
    component: DashboardView,
  },
  {
    path: "/NotificationView",
    name: "notification-View",
    component: NotificationView,
  },
  {
    path: "/FavorisView",
    name: "favoris-View",
    component: FavorisView,
  },
  {
    path: "/MessageView",
    name: "Message-View",
    component: MessageView,
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
    path: "/PublierView",
    name: "publier-view",
    component: PublierView,
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
