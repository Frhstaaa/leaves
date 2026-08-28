import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';

// Window Error Trap to prevent silent blank screens
window.addEventListener('error', (event) => {
  console.error('Global Error Trapped:', event.error || event.message);
});

// Global Inertia 419 CSRF / Session Expired Auto-Recovery
router.on('invalid', (event) => {
  const status = event.detail?.response?.status;
  if (status === 419) {
    event.preventDefault();
    console.warn('[CSO Security] 419 CSRF / Session Expired intercepted. Smoothly refreshing session...');
    window.location.reload();
  }
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
    'ping': '/ping',
    'dashboard': '/dashboard',
    'leave-requests.index': '/leave-requests',
    'leave-requests.create': '/leave-requests/create',
    'leave-requests.store': '/leave-requests',
    'leave-requests.show': (id) => `/leave-requests/${id}`,
    'leave-requests.destroy': (id) => `/leave-requests/${id}`,
    'leave-requests.attachment': (id) => `/leave-requests/${id}/attachment`,
    'leave-requests.attachment.view': (id) => `/leave-requests/${id}/attachment/view`,
    'leave-requests.attachment.download': (id) => `/leave-requests/${id}/attachment/download`,
    'leave-requests.report.print': '/leave-requests/report/print',
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
    'hrd.employees.import.preview': '/hrd/employees/import/preview',
    'hrd.employees.export-biodata': '/hrd/employees/export-biodata',
    'hrd.employees.biodata': (id) => `/hrd/employees/${id}/biodata`,
    'hrd.employees.biodata.alt': (id) => `/hrd/employees/biodata/${id}`,
    'hrd.employees.biodata.update': (id) => `/hrd/employees/${id}/biodata`,
    'hrd.employees.biodata.print': (id) => `/hrd/employees/${id}/biodata/print`,
    'hrd.employees.biodata.print.alt': (id) => `/hrd/employees/biodata/${id}/print`,
    'hrd.employees.store': '/hrd/employees',
    'hrd.employees.update': (id) => `/hrd/employees/${id}/update`,
    'employees.update': (id) => `/hrd/employees/${id}/update`,
    'hrd.employees.update.alt': (id) => `/hrd/employees/update/${id}`,
    'update': (id) => `/hrd/employees/${id}/update`,
    'hrd.employees.destroy': (id) => `/hrd/employees/${id}`,
    'hrd.update-quota': (id) => `/hrd/employees/${id}/quota`,
    'hrd.requests.override': (id) => `/hrd/requests/${id}/override`,
    'hrd.export': '/hrd/export',
    'hrd.reports.quotas': '/hrd/reports/quotas',
    'hrd.reports.departments': '/hrd/reports/departments',
    'monitoring.index': '/monitoring',
    'monitoring.annual-report': '/monitoring/annual-report',
    'monitoring.annual-report.pdf': '/monitoring/annual-report/pdf',
    'monitoring.annual-report.export': '/monitoring/annual-report/export',
    'hrd.settings': '/hrd/settings',
    'hrd.settings.update': '/hrd/settings',
    'hrd.settings.test-r2': '/hrd/settings/test-r2',
    'payslips.index': '/payslips',
    'payslips.download': (id) => `/payslips/${id}/download`,
    'payslips.preview': (id) => `/payslips/${id}/preview`,
    'payslips.mark-viewed': (id) => `/payslips/${id}/viewed`,
    'hrd.payslips': '/hrd/payslips',
    'hrd.payslips.bulk-upload': '/hrd/payslips/bulk-upload',
    'hrd.payslips.single-upload': '/hrd/payslips/single-upload',
    'hrd.payslips.destroy': (id) => `/hrd/payslips/${id}`,
    'profile.biodata': '/profile/biodata',
    'profile.biodata.update': '/profile/biodata',
    'profile.biodata.print': '/profile/biodata/print',
    'biodata': '/profile/biodata',
    'biodata.update': '/profile/biodata',
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

// Periodic Session Keep-Alive & CSRF Token Heartbeat (Every 5 minutes)
if (typeof window !== 'undefined') {
  setInterval(() => {
    if (document.visibilityState === 'visible') {
      try {
        fetch(window.route('ping'), {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        })
          .then((res) => res.json())
          .then((data) => {
            if (data && data.csrf_token) {
              const meta = document.querySelector('meta[name="csrf-token"]');
              if (meta) meta.content = data.csrf_token;
              if (window.axios) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.csrf_token;
              }
            }
          })
          .catch(() => {});
      } catch (e) {}
    }
  }, 5 * 60 * 1000);
}

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

