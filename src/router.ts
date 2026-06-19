import { createRouter, createWebHashHistory } from 'vue-router';
import DashboardView from './views/DashboardView.vue';
import DataRequestsView from './views/DataRequestsView.vue';
import DataReceptionsView from './views/DataReceptionsView.vue';
import OrganizationsView from './views/OrganizationsView.vue';
import ContactsView from './views/ContactsView.vue';

const routes = [
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { title: 'Dashboard' }
  },
  {
    path: '/requests',
    name: 'requests',
    component: DataRequestsView,
    meta: { title: 'Data Requests Management' }
  },
  {
    path: '/receptions',
    name: 'receptions',
    component: DataReceptionsView,
    meta: { title: 'Data Receptions Management' }
  },
  {
    path: '/organizations',
    name: 'organizations',
    component: OrganizationsView,
    meta: { title: 'Organizations Management' }
  },
  {
    path: '/contacts',
    name: 'contacts',
    component: ContactsView,
    meta: { title: 'Contacts Management' }
  }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
