import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

// Window Error Trap to prevent silent blank screens
window.addEventListener('error', (event) => {
  console.error('Global Error Trapped:', event.error || event.message);
});

// Application Route Helper for Form SGIN
window.route = function (name, params) {
  const routes = {
    'login': '/login',
    'quick-login': '/quick-login',
    'logout': '/logout',
    'dashboard': '/dashboard',
    'leave-requests.index': '/leave-requests',
    'leave-requests.create': '/leave-requests/create',
    'leave-requests.store': '/leave-requests',
    'leave-requests.show': (id) => `/leave-requests/${id}`,
    'leave-requests.destroy': (id) => `/leave-requests/${id}`,
    'approvals.index': '/approvals',
    'approvals.approve': (id) => `/approvals/${id}/approve`,
    'approvals.reject': (id) => `/approvals/${id}/reject`,
    'hrd.index': '/hrd',
    'hrd.departments': '/hrd/departments',
    'hrd.departments.store': '/hrd/departments',
    'hrd.departments.update': (id) => `/hrd/departments/${id}/update`,
    'hrd.departments.destroy': (id) => `/hrd/departments/${id}`,
    'hrd.employees': '/hrd/employees',
    'hrd.employees.template': '/hrd/employees/template',
    'hrd.employees.import': '/hrd/employees/import',
    'hrd.employees.store': '/hrd/employees',
    'hrd.employees.update': (id) => `/hrd/employees/${id}/update`,
    'hrd.employees.destroy': (id) => `/hrd/employees/${id}`,
    'hrd.update-quota': (id) => `/hrd/employees/${id}/quota`,
    'hrd.export': '/hrd/export',
    'hrd.settings': '/hrd/settings',
    'hrd.settings.update': '/hrd/settings',
    'payslips.index': '/payslips',
    'payslips.download': (id) => `/payslips/${id}/download`,
    'payslips.preview': (id) => `/payslips/${id}/preview`,
    'hrd.payslips': '/hrd/payslips',
    'hrd.payslips.bulk-upload': '/hrd/payslips/bulk-upload',
    'hrd.payslips.single-upload': '/hrd/payslips/single-upload',
    'hrd.payslips.destroy': (id) => `/hrd/payslips/${id}`,
    'profile.avatar': '/profile/avatar',
    'superadmin.roles.index': '/superadmin/roles',
    'superadmin.roles.store': '/superadmin/roles',
    'superadmin.roles.update': (id) => `/superadmin/roles/${id}`,
    'superadmin.roles.destroy': (id) => `/superadmin/roles/${id}`,
    'superadmin.permissions.store': '/superadmin/permissions',
    'superadmin.users.assign-role': (id) => `/superadmin/users/${id}/assign-role`,
    'storage.local': (path) => `/storage/${path}`,
  };

  if (!name) {
    return {
      current: (routeName) => {
        const currentPath = window.location.pathname;
        const targetPath = typeof routes[routeName] === 'function' ? routes[routeName]('') : routes[routeName];
        return currentPath === targetPath || currentPath.startsWith(targetPath);
      }
    };
  }

  const target = routes[name];
  if (typeof target === 'function') {
    return target(params);
  }

  if (target) return target;

  // Smart fallback for dot-separated route names if missing from dict
  let fallbackPath = '/' + name.replace(/\./g, '/').replace('/index', '');
  if (params) {
    fallbackPath += `/${params}`;
  }
  return fallbackPath;
};

import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Form SGIN';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
  setup({ el, App, props }) {
    const targetElement = el || document.getElementById('app');
    if (!targetElement) {
      console.error('Target element #app not found!');
      return;
    }
    const root = createRoot(targetElement);
    root.render(<App {...props} />);
  },
  progress: {
    color: '#14b8a6',
    showSpinner: true,
  },
});
