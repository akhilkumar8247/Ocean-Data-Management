<template>
  <header class="top-header">
    <div class="header-left">
      <!-- Top Navigation Links -->
      <nav class="top-nav">
        <router-link to="/dashboard" class="nav-item" active-class="active">
          <BarChart3Icon :size="16" />
          <span>Dashboard</span>
        </router-link>
        <router-link to="/requests" class="nav-item" active-class="active">
          <FileTextIcon :size="16" />
          <span>Data Requests</span>
        </router-link>
        <router-link to="/receptions" class="nav-item" active-class="active">
          <FileSpreadsheetIcon :size="16" />
          <span>Data Receptions</span>
        </router-link>
        <router-link to="/organizations" class="nav-item" active-class="active">
          <BuildingIcon :size="16" />
          <span>Organizations</span>
        </router-link>
        <router-link to="/contacts" class="nav-item" active-class="active">
          <UsersIcon :size="16" />
          <span>Contacts</span>
        </router-link>
      </nav>
    </div>
  </header>
</template>

<script setup lang="ts">
import { 
  BarChart3 as BarChart3Icon,
  FileText as FileTextIcon,
  FileSpreadsheet as FileSpreadsheetIcon,
  Building as BuildingIcon,
  Users as UsersIcon
} from '@lucide/vue';

defineProps<{
  title?: string;
}>();
</script>

<style scoped>
.top-header {
  height: 64px;
  background-color: var(--bg-surface);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
  box-shadow: var(--shadow-sm);
  width: 100%;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 2.5rem;
}

.brand-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--color-primary);
  font-weight: 700;
  font-size: 1.125rem;
  letter-spacing: 0.02em;
  transition: opacity var(--transition-fast);
}

.brand-link:hover {
  opacity: 0.9;
}

.top-nav {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  height: 64px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 0.5rem;
  font-weight: 500;
  font-size: 0.925rem;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
  height: 100%;
  position: relative;
}

.nav-item:hover {
  color: var(--color-primary);
}

.nav-item.active {
  color: var(--color-primary);
  font-weight: 600;
}

.nav-item.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background-color: var(--color-primary);
  border-top-left-radius: 2px;
  border-top-right-radius: 2px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  border-radius: var(--radius-md);
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid transparent;
  transition: all var(--transition-fast);
}

.status-indicator.connected {
  background-color: var(--color-success-bg);
  border-color: var(--color-success-border);
  color: var(--color-success);
}

.status-indicator.checking {
  background-color: var(--color-info-bg);
  border-color: var(--color-info-border);
  color: var(--color-info);
}

.status-indicator.disconnected {
  background-color: var(--color-danger-bg);
  border-color: var(--color-danger-border);
  color: var(--color-danger);
}

.pulse-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  transition: all var(--transition-fast);
}

.status-indicator.connected .pulse-dot {
  background-color: var(--color-success);
  box-shadow: 0 0 0 0 rgba(22, 101, 52, 0.4);
  animation: pulse-success 2s infinite;
}

.status-indicator.checking .pulse-dot {
  background-color: var(--color-info);
  box-shadow: 0 0 0 0 rgba(7, 89, 133, 0.4);
  animation: pulse-info 2s infinite;
}

.status-indicator.disconnected .pulse-dot {
  background-color: var(--color-danger);
  box-shadow: 0 0 0 0 rgba(153, 27, 27, 0.4);
  animation: pulse-danger 1s infinite alternate;
}

.profile-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background-color: #f1f5f9;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  border: 1px solid var(--border-color);
}

.avatar-letter {
  width: 24px;
  height: 24px;
  background-color: var(--color-primary);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 0.75rem;
  font-weight: 600;
}

.profile-name {
  font-size: 0.825rem;
  font-weight: 500;
  color: var(--text-secondary);
}

@keyframes pulse-success {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(22, 101, 52, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 6px rgba(22, 101, 52, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(22, 101, 52, 0);
  }
}

@keyframes pulse-info {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(7, 89, 133, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 6px rgba(7, 89, 133, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(7, 89, 133, 0);
  }
}

@keyframes pulse-danger {
  from {
    transform: scale(0.85);
    opacity: 0.5;
  }
  to {
    transform: scale(1.15);
    opacity: 1;
  }
}

@media (max-width: 768px) {
  .header-left {
    gap: 1.25rem;
  }
  .brand-name {
    display: none;
  }
  .top-nav {
    gap: 1rem;
  }
  .nav-item span {
    display: none;
  }
  .nav-item {
    padding: 0 0.25rem;
  }
}

@media (max-width: 640px) {
  .status-indicator {
    display: none;
  }
  .profile-name {
    display: none;
  }
}
</style>
