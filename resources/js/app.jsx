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
  // Auto-detect base path if hosted on subdirectory (e.g. domain.com/leaves-application)
  const getBasePath = () => {
    const path = window.location.pathname;
    if (path.startsWith('/leaves-application')) {
      return '/leaves-application';
    }
    return '';
  };
  const basePath = getBasePath();

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
    'hrd.requests.override': (id) => `/hrd/requests/${id}/override`,
    'hrd.export': '/hrd/export',
    'hrd.reports.quotas': '/hrd/reports/quotas',
    'hrd.reports.departments': '/hrd/reports/departments',
    'monitoring.index': '/monitoring',
    'hrd.settings': '/hrd/settings',
    'hrd.settings.update': '/hrd/settings',
    'payslips.index': '/payslips',
    'payslips.download': (id) => `/payslips/${id}/download`,
    'payslips.preview': (id) => `/payslips/${id}/preview`,
    'payslips.mark-viewed': (id) => `/payslips/${id}/viewed`,
    'hrd.payslips': '/hrd/payslips',
    'hrd.payslips.bulk-upload': '/hrd/payslips/bulk-upload',
    'hrd.payslips.single-upload': '/hrd/payslips/single-upload',
    'hrd.payslips.destroy': (id) => `/hrd/payslips/${id}`,
    'profile.avatar': '/profile/avatar',
    'profile.password': '/profile/password',
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
        if (!targetPath) return false;
        const fullTarget = basePath ? `${basePath}${targetPath}` : targetPath;
        return currentPath === fullTarget || currentPath.startsWith(fullTarget);
      }
    };
  }

  const target = routes[name];
  let resolved = '';
  if (typeof target === 'function') {
    resolved = target(params);
  } else if (target) {
    resolved = target;
  } else {
    // Smart fallback for dot-separated route names if missing from dict
    let fallbackPath = '/' + name.replace(/\./g, '/').replace('/index', '');
    if (params) {
      fallbackPath += `/${params}`;
    }
    resolved = fallbackPath;
  }

  if (basePath && resolved && resolved.startsWith('/')) {
    return `${basePath}${resolved}`;
  }
  return resolved;
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
