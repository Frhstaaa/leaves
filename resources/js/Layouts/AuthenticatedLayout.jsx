import React, { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
  LayoutDashboard,
  Home,
  FilePlus,
  History,
  CheckSquare,
  Users,
  Building,
  FileSpreadsheet,
  LogOut,
  User,
  X,
  Briefcase,
  ChevronRight,
  ShieldCheck,
  Check,
  Camera,
  Upload,
  Bell,
  Clock,
  CheckCircle,
  XCircle,
  FileText,
  AlertCircle,
  Menu,
  Grid,
  Receipt,
  ChevronDown,
  Settings as SettingsIcon,
  Smartphone
} from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { motion, AnimatePresence } from 'framer-motion';

import { showConfirm, showToast } from '@/Utils/swal';
import { DashboardSkeleton, TableSkeleton, CardListSkeleton } from '@/components/PageSkeleton';
import PwaInstallModal from '@/components/PwaInstallModal';

export function UserAvatar({ user, size = 'w-8 h-8', textSize = 'text-xs' }) {
  const [hasError, setHasError] = useState(false);

  const rawAvatar = user?.avatar || user?.avatar_url;

  if (rawAvatar && !hasError) {
    const avatarSrc = (rawAvatar.startsWith('http') || rawAvatar.startsWith('/'))
      ? rawAvatar
      : `/storage/${rawAvatar}`;

    return (
      <img
        src={avatarSrc}
        alt={user?.name || 'User Profile'}
        onError={() => setHasError(true)}
        className={`${size} rounded-full object-cover ring-2 ring-emerald-500/30 shrink-0 shadow-sm`}
      />
    );
  }

  return (
    <div className={`${size} rounded-full bg-emerald-600 text-white flex items-center justify-center font-extrabold ${textSize} shadow-sm ring-2 ring-emerald-500/30 shrink-0`}>
      {user?.name ? user.name.charAt(0) : 'U'}
    </div>
  );
}

export default function AuthenticatedLayout({ children, title }) {
  const { auth, flash, notifications, app_settings } = usePage().props;
  const user = auth?.user || {};

  const appName = app_settings?.app_name || 'Form SGIN';
  const appSubname = app_settings?.app_subname || 'Cuti & Ketidakhadiran';
  const appLogo = app_settings?.app_logo
    ? (app_settings.app_logo.startsWith('http') ? app_settings.app_logo : `/storage/${app_settings.app_logo}`)
    : null;

  // Modals State
  const [actionMenuOpen, setActionMenuOpen] = useState(false);
  const [myProfileOpen, setMyProfileOpen] = useState(false);
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [uploading, setUploading] = useState(false);

  // Skeleton Loading & Navigation State (Debounced for zero-flicker speed)
  const [isNavigating, setIsNavigating] = useState(false);
  const [showSkeleton, setShowSkeleton] = useState(false);

  const isSuperadmin = user.is_superadmin || user.role === 'superadmin';
  const isAdmin = user.is_admin || user.role === 'admin' || isSuperadmin;
  const isManager = user.is_manager || user.role === 'manager';

  // Navigation transition listener with 120ms debounce for smooth skeleton loading
  useEffect(() => {
    let timer = null;

    const unbindStart = router.on('start', () => {
      setIsNavigating(true);
      timer = setTimeout(() => {
        setShowSkeleton(true);
      }, 120);
    });

    const unbindFinish = router.on('finish', () => {
      if (timer) clearTimeout(timer);
      setIsNavigating(false);
      setShowSkeleton(false);
    });

    const unbindError = router.on('error', () => {
      if (timer) clearTimeout(timer);
      setIsNavigating(false);
      setShowSkeleton(false);
    });

    return () => {
      if (timer) clearTimeout(timer);
      unbindStart();
      unbindFinish();
      unbindError();
    };
  }, []);

  // Flash message toast notification listener
  useEffect(() => {
    if (flash?.success) {
      showToast(flash.success, 'success');
    }
    if (flash?.error) {
      showToast(flash.error, 'error');
    }
  }, [flash]);

  // Global event listener for custom events
  useEffect(() => {
    const handleOpenProfile = () => setMyProfileOpen(true);
    const handleOpenNotifications = () => setNotificationsOpen(true);

    window.addEventListener('open-profile-menu', handleOpenProfile);
    window.addEventListener('open-notifications-menu', handleOpenNotifications);

    return () => {
      window.removeEventListener('open-profile-menu', handleOpenProfile);
      window.removeEventListener('open-notifications-menu', handleOpenNotifications);
    };
  }, []);

  const handleLogout = async (e) => {
    e.preventDefault();
    const confirmed = await showConfirm({
      title: 'Keluar dari Sistem?',
      text: 'Anda akan keluar dari sesi login akun Form SGIN ini.',
      icon: 'question',
      confirmText: 'Ya, Keluar',
      cancelText: 'Batal',
    });
    if (confirmed) {
      router.post(route('logout'));
    }
  };

  const handleAvatarUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('avatar', file);

    setUploading(true);
    router.post(route('profile.avatar'), formData, {
      forceFormData: true,
      onFinish: () => setUploading(false),
    });
  };

  const { url } = usePage();

  // Precise route matching based on exact URL paths to prevent double active highlights
  const isDashboard = url === '/dashboard' || url.startsWith('/dashboard');
  const isCreateRequest = url.startsWith('/leave-requests/create');
  const isHistoryRequest = (url === '/leave-requests' || url.startsWith('/leave-requests?')) || (url.startsWith('/leave-requests') && !url.startsWith('/leave-requests/create'));
  const isApproval = url.startsWith('/approvals');
  const isPayslipsPage = (url === '/payslips' || url.startsWith('/payslips?')) && !url.startsWith('/hrd/payslips');
  const isHrdDepartments = url.startsWith('/hrd/departments');
  const isHrdEmployees = url.startsWith('/hrd/employees');
  const isHrdPayslips = url.startsWith('/hrd/payslips');
  const isHrdIndex = (url === '/hrd' || url.startsWith('/hrd?')) && !url.startsWith('/hrd/employees') && !url.startsWith('/hrd/departments') && !url.startsWith('/hrd/payslips');
  const isRolesPage = url.startsWith('/superadmin/roles');
  const pendingApprovalsCount = user?.pending_approvals_count || 0;
  const pendingApprovalsList = user?.pending_approvals_list || [];
  const myRecentRequests = user?.my_recent_requests || [];

  const navItems = [
    {
      name: 'Dashboard',
      shortName: 'Home',
      href: route('dashboard'),
      icon: LayoutDashboard,
      active: isDashboard,
      show: true,
    },
    {
      name: 'Buat Pengajuan',
      shortName: 'Pengajuan',
      href: route('leave-requests.create'),
      icon: FilePlus,
      active: isCreateRequest,
      show: true,
    },
    {
      name: 'Riwayat Cuti',
      shortName: 'Riwayat',
      href: route('leave-requests.index'),
      icon: History,
      active: isHistoryRequest,
      show: true,
    },
    {
      name: 'Slip Gaji Saya',
      shortName: 'Slip Gaji',
      href: route('payslips.index'),
      icon: Receipt,
      active: isPayslipsPage,
      show: true,
    },
    {
      name: 'Persetujuan Team',
      shortName: 'Approval',
      href: route('approvals.index'),
      icon: CheckSquare,
      active: isApproval,
      show: isManager || isAdmin,
      badge: pendingApprovalsCount > 0 ? pendingApprovalsCount : null,
    },
    {
      name: 'Setup Departemen',
      shortName: 'Departemen',
      href: route('hrd.departments'),
      icon: Building,
      active: isHrdDepartments,
      show: isAdmin,
    },
    {
      name: 'Data Karyawan',
      shortName: 'Karyawan',
      href: route('hrd.employees'),
      icon: Users,
      active: isHrdEmployees,
      show: isAdmin,
    },
    {
      name: 'Distribusi Slip Gaji',
      shortName: 'Slip Gaji',
      href: route('hrd.payslips'),
      icon: Receipt,
      active: isHrdPayslips,
      show: isAdmin,
    },
    {
      name: 'Rekapitulasi HRD',
      shortName: 'Rekap',
      href: route('hrd.index'),
      icon: FileSpreadsheet,
      active: isHrdIndex,
      show: isAdmin,
    },
    {
      name: 'Pengaturan Aplikasi',
      shortName: 'Setup App',
      href: route('hrd.settings'),
      icon: SettingsIcon,
      active: url.startsWith('/hrd/settings'),
      show: isAdmin,
    },
    {
      name: 'Hak Akses & Role',
      shortName: 'Role Spatie',
      href: route('superadmin.roles.index'),
      icon: ShieldCheck,
      active: isRolesPage,
      show: isSuperadmin || isAdmin,
    },
  ];

  return (
    <div className="min-h-screen bg-[#F5FAF7] text-slate-900 flex flex-col md:flex-row font-sans pb-20 md:pb-0">

      {/* DESKTOP SIDEBAR */}
      <aside className="hidden md:flex w-64 flex-col shrink-0 min-h-screen sticky top-0 h-screen bg-white border-r border-slate-200 shadow-sm">
        {/* Brand Header */}
        <div className="p-5 border-b border-slate-200 flex items-center justify-between">
          <div className="flex items-center space-x-3 min-w-0">
            {appLogo ? (
              <img
                src={appLogo}
                alt={appName}
                className="w-10 h-10 rounded-xl object-cover shadow-md shadow-emerald-600/20 border border-emerald-500/20 shrink-0"
              />
            ) : (
              <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-black text-white text-base shadow-lg shadow-emerald-600/20 shrink-0">
                SG
              </div>
            )}
            <div className="min-w-0">
              <h2 className="font-extrabold text-base text-slate-900 tracking-tight leading-none truncate">{appName}</h2>
              <span className="text-[11px] font-bold text-emerald-600 tracking-wide uppercase truncate block mt-0.5">{appSubname}</span>
            </div>
          </div>
        </div>

        {/* User Card Trigger for Action Menu */}
        <button
          onClick={() => setActionMenuOpen(true)}
          className="p-3.5 mx-3 my-4 rounded-2xl bg-slate-50 hover:bg-slate-100 border border-slate-200 flex items-center space-x-3 text-left transition-all group"
        >
          <UserAvatar user={user} size="w-10 h-10" textSize="text-base" />
          <div className="min-w-0 flex-1">
            <h3 className="text-sm font-bold text-slate-900 truncate">{user.name}</h3>
            <p className="text-xs text-slate-500 truncate">{user.nik || 'NIK: -'}</p>
            <span className="text-[10px] text-emerald-600 font-bold block mt-0.5 group-hover:underline">Opsi Profil & Upload Foto &bull;</span>
          </div>
        </button>

        {/* Desktop Navigation Menu */}
        <nav className="flex-1 px-3 py-2 space-y-1.5 overflow-y-auto">
          {navItems.filter(item => item.show).map((item) => {
            const Icon = item.icon;
            return (
              <Link
                key={item.name}
                href={item.href}
                className={`
                  flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 group
                  ${item.active
                    ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20 font-extrabold'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'}
                `}
              >
                <div className="flex items-center space-x-3">
                  <Icon size={19} className={item.active ? 'text-white' : 'text-slate-400 group-hover:text-emerald-600'} />
                  <span>{item.name}</span>
                </div>
                <div className="flex items-center space-x-1.5">
                  {item.badge && (
                    <span className="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white shadow-xs animate-pulse">
                      {item.badge}
                    </span>
                  )}
                  {item.active && <ChevronRight size={16} className="text-white/80" />}
                </div>
              </Link>
            );
          })}
        </nav>

        {/* Footer Logout */}
        <div className="p-4 border-t border-slate-200">
          <button
            onClick={handleLogout}
            className="w-full flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-all duration-200"
          >
            <LogOut size={16} />
            <span>Keluar (Logout)</span>
          </button>
        </div>
      </aside>

      {/* MAIN CONTENT AREA */}
      <div className="flex-1 flex flex-col min-w-0">
        {/* Top Mobile Bar */}
        <header className="md:hidden flex items-center justify-between px-4 py-2.5 bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 shadow-xs">
          <div className="flex items-center space-x-2.5 min-w-0">
            {appLogo ? (
              <img
                src={appLogo}
                alt={appName}
                className="w-8 h-8 rounded-xl object-cover shadow-md shadow-emerald-600/20 border border-emerald-500/20 shrink-0"
              />
            ) : (
              <div className="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-black text-white text-xs shadow-md shadow-emerald-600/20 shrink-0">
                SG
              </div>
            )}
            <div className="min-w-0">
              <h1 className="text-xs sm:text-sm font-extrabold text-slate-900 truncate leading-tight">{title || appName}</h1>
              <p className="text-[10px] text-slate-500 truncate">{user.name} &bull; {user.department_name || 'General'}</p>
            </div>
          </div>

          <div className="flex items-center space-x-2 shrink-0">
            <button
              onClick={() => setNotificationsOpen(true)}
              className="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 relative transition-colors"
              title="Notifikasi & Tugas Approval"
            >
              <Bell size={18} />
              {pendingApprovalsCount > 0 ? (
                <span className="absolute -top-1 -right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-white shadow-sm ring-2 ring-white animate-pulse">
                  {pendingApprovalsCount > 9 ? '9+' : pendingApprovalsCount}
                </span>
              ) : (
                <span className="absolute top-1 right-1 w-2 h-2 bg-emerald-500 rounded-full"></span>
              )}
            </button>

            {/* Profile Avatar Button */}
            <button
              type="button"
              onClick={() => setActionMenuOpen(true)}
              className="rounded-full ring-2 ring-emerald-500/30 hover:ring-emerald-500 transition-all active:scale-95 shrink-0"
              title="Menu Profil"
            >
              <UserAvatar user={user} size="w-8 h-8" textSize="text-xs" />
            </button>
          </div>
        </header>

        {/* Top Desktop Bar */}
        <header className="hidden md:flex items-center justify-between px-8 py-4 bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
          <div>
            <h1 className="text-lg font-bold text-slate-900">{title || 'Dashboard'}</h1>
            <p className="text-xs text-slate-500">Sistem Permohonan Tidak Hadir Bekerja & Cuti Real-time</p>
          </div>

          <div className="flex items-center space-x-4">
            <button
              onClick={() => setNotificationsOpen(true)}
              className="p-2.5 rounded-full bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-600 relative transition-colors"
              title="Notifikasi & Tugas Approval"
            >
              <Bell size={18} />
              {pendingApprovalsCount > 0 ? (
                <span className="absolute -top-1 -right-1 flex h-5 min-w-5 px-1 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white shadow-sm ring-2 ring-white animate-pulse">
                  {pendingApprovalsCount > 9 ? '9+' : pendingApprovalsCount}
                </span>
              ) : (
                <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-emerald-500 rounded-full"></span>
              )}
            </button>

            <div className="flex items-center space-x-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-xs text-slate-700 font-medium">
              <Briefcase size={14} className="text-emerald-600" />
              <span>{user.department_name || user.department?.name || 'General'}</span>
            </div>

            <div className="h-4 w-[1px] bg-slate-200"></div>

            {/* Profile Avatar Trigger with Shadcn DropdownMenu */}
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <button
                  type="button"
                  className="flex items-center space-x-2.5 px-2.5 py-1.5 rounded-2xl hover:bg-slate-100 text-xs font-bold text-slate-800 hover:text-emerald-700 transition-colors outline-none cursor-pointer"
                >
                  <UserAvatar user={user} size="w-8 h-8" textSize="text-xs" />
                  <div className="text-left hidden sm:block">
                    <span className="block font-bold text-slate-900 leading-tight truncate max-w-[120px]">{user.name}</span>
                    <span className="text-[10px] text-slate-400 font-semibold uppercase">{user.role}</span>
                  </div>
                  <ChevronDown size={14} className="text-slate-400" />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>Akun Saya ({user.nik || 'EMP'})</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={() => setMyProfileOpen(true)}>
                  <User className="mr-2 h-4 w-4 text-emerald-600" />
                  <span>Lihat Profil Lengkap</span>
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => setActionMenuOpen(true)}>
                  <Camera className="mr-2 h-4 w-4 text-blue-600" />
                  <span>Ganti Foto & Opsi Akun</span>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={handleLogout} className="text-rose-600 focus:bg-rose-50 focus:text-rose-700">
                  <LogOut className="mr-2 h-4 w-4" />
                  <span>Keluar (Logout)</span>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </header>

        {/* Flash Notifications */}
        <AnimatePresence>
          {flash?.success && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className="mx-4 sm:mx-6 mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-center justify-between shadow-sm"
            >
              <div className="flex items-center space-x-2">
                <CheckSquare size={18} className="text-emerald-600 shrink-0" />
                <span>{flash.success}</span>
              </div>
            </motion.div>
          )}

          {flash?.error && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className="mx-4 sm:mx-6 mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-medium flex items-center justify-between shadow-sm"
            >
              <div className="flex items-center space-x-2">
                <X size={18} className="text-rose-600 shrink-0" />
                <span>{flash.error}</span>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Top Shimmer Progress Bar for Seamless Page Transitions */}
        {isNavigating && (
          <div className="fixed top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-600 z-[9999] shadow-xs">
            <div className="h-full w-full bg-white/40 skeleton-shimmer" />
          </div>
        )}

        {/* Body Content with Skeleton Fallback */}
        <main className="flex-1 min-w-0 p-4 sm:p-6 md:p-8 w-full pb-28 md:pb-8">
          <AnimatePresence mode="wait">
            {showSkeleton ? (
              <motion.div
                key="skeleton-view"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.15 }}
              >
                {isDashboard ? (
                  <DashboardSkeleton />
                ) : isApproval || isHrdDepartments ? (
                  <CardListSkeleton count={4} />
                ) : (
                  <TableSkeleton rows={5} />
                )}
              </motion.div>
            ) : (
              <motion.div
                key={url}
                initial={{ opacity: 0, y: 6 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -6 }}
                transition={{ duration: 0.2, ease: 'easeOut' }}
              >
                {children}
              </motion.div>
            )}
          </AnimatePresence>
        </main>
      </div>

      {/* MOBILE BOTTOM BAR ATTACHED DESIGN (MENU FLOATING IN CENTER, HOME ON FAR LEFT) */}
      <motion.div
        initial={{ y: 50, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        transition={{ type: 'spring', stiffness: 350, damping: 28 }}
        className="md:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur-2xl border-t border-slate-200/80 shadow-[0_-4px_25px_rgba(0,0,0,0.06)] px-3 pt-2 pb-2.5 flex items-center justify-around"
      >
        {/* 1. Home / Dashboard (PALING KIRI) */}
        <Link
          href={route('dashboard')}
          className={`flex flex-col items-center justify-center space-y-0.5 px-2.5 py-1 transition-all ${
            isDashboard
              ? 'text-emerald-600 font-extrabold'
              : 'text-slate-400 hover:text-slate-700 font-medium'
          }`}
        >
          <Home size={22} className={isDashboard ? 'text-emerald-600' : 'text-slate-400'} />
          <span className="text-[10px]">Home</span>
          {isDashboard && (
            <motion.span layoutId="activeDot" className="w-1.5 h-1.5 rounded-full bg-emerald-600 mt-0.5"></motion.span>
          )}
        </Link>

        {/* 2. Pengajuan */}
        <Link
          href={route('leave-requests.create')}
          className={`flex flex-col items-center justify-center space-y-0.5 px-2.5 py-1 transition-all ${
            isCreateRequest
              ? 'text-emerald-600 font-extrabold'
              : 'text-slate-400 hover:text-slate-700 font-medium'
          }`}
        >
          <FilePlus size={22} className={isCreateRequest ? 'text-emerald-600' : 'text-slate-400'} />
          <span className="text-[10px]">Pengajuan</span>
          {isCreateRequest && (
            <motion.span layoutId="activeDot" className="w-1.5 h-1.5 rounded-full bg-emerald-600 mt-0.5"></motion.span>
          )}
        </Link>

        {/* 3. Menu Drawer Trigger (DI TENGAH - FLOATING CIRCLE BUTTON) */}
        <div className="relative -mt-7 flex flex-col items-center">
          <motion.button
            whileHover={{ scale: 1.08 }}
            whileTap={{ scale: 0.92 }}
            onClick={() => setMobileMenuOpen(true)}
            className={`w-14 h-14 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-xl shadow-emerald-600/35 border-4 border-white flex items-center justify-center transition-transform relative ${
              isApproval || isHrdEmployees || isHrdIndex || isRolesPage ? 'ring-4 ring-emerald-500/20' : ''
            }`}
          >
            <Grid size={24} className="text-white" />
            {pendingApprovalsCount > 0 && (
              <span className="absolute -top-1 -right-1 flex h-5 min-w-5 px-1 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white shadow-md ring-2 ring-white animate-pulse">
                {pendingApprovalsCount > 9 ? '9+' : pendingApprovalsCount}
              </span>
            )}
          </motion.button>
          <span className={`text-[10px] mt-0.5 font-extrabold ${isApproval || isHrdEmployees || isHrdIndex || isRolesPage ? 'text-emerald-600' : 'text-slate-500'}`}>
            Menu
          </span>
        </div>

        {/* 4. Riwayat (DISAMPING MENU) */}
        <Link
          href={route('leave-requests.index')}
          className={`flex flex-col items-center justify-center space-y-0.5 px-2.5 py-1 transition-all ${
            isHistoryRequest
              ? 'text-emerald-600 font-extrabold'
              : 'text-slate-400 hover:text-slate-700 font-medium'
          }`}
        >
          <History size={22} className={isHistoryRequest ? 'text-emerald-600' : 'text-slate-400'} />
          <span className="text-[10px]">Riwayat</span>
          {isHistoryRequest && (
            <motion.span layoutId="activeDot" className="w-1.5 h-1.5 rounded-full bg-emerald-600 mt-0.5"></motion.span>
          )}
        </Link>

        {/* 5. Profil (PALING KANAN) */}
        <button
          onClick={() => setActionMenuOpen(true)}
          className="flex flex-col items-center justify-center space-y-0.5 px-2.5 py-1 transition-all text-slate-400 hover:text-slate-700 font-medium"
        >
          <User size={22} className="text-slate-400" />
          <span className="text-[10px]">Profil</span>
        </button>
      </motion.div>

      {/* MOBILE ALL-MENU DRAWER / MODAL WITH FRAMER MOTION */}
      <AnimatePresence>
        {mobileMenuOpen && (
          <div className="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 md:hidden">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.18 }}
              className="fixed inset-0 bg-slate-950/60"
              onClick={() => setMobileMenuOpen(false)}
            />
            <motion.div
              initial={{ y: '100%', opacity: 0.8 }}
              animate={{ y: 0, opacity: 1 }}
              exit={{ y: '100%', opacity: 0 }}
              transition={{ type: 'spring', damping: 30, stiffness: 360, mass: 0.8 }}
              style={{ willChange: 'transform, opacity' }}
              className="relative z-10 w-full max-w-lg p-5 sm:p-6 rounded-t-3xl sm:rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4 max-h-[82vh] overflow-y-auto pb-10 sm:pb-6 transform-gpu"
            >
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div className="flex items-center space-x-2.5">
                  <div className="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black">
                    <Grid size={18} />
                  </div>
                  <div>
                    <h3 className="text-sm font-extrabold text-slate-900">Semua Menu Navigasi</h3>
                    <p className="text-[11px] text-slate-500">Pilih fitur Form SGIN yang ingin diakses</p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setMobileMenuOpen(false)}
                  className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800"
                >
                  <X size={18} />
                </button>
              </div>

              {/* Menu Items Grid - Compact Horizontal Card Design */}
              <div className="grid grid-cols-2 gap-2">
                {navItems.filter(item => item.show).map((item, idx) => {
                  const Icon = item.icon;
                  return (
                    <motion.div
                      key={item.name}
                      initial={{ opacity: 0, scale: 0.95 }}
                      animate={{ opacity: 1, scale: 1 }}
                      transition={{ duration: 0.15, delay: Math.min(idx * 0.02, 0.15) }}
                    >
                      <Link
                        href={item.href}
                        onClick={() => setMobileMenuOpen(false)}
                        className={`
                          p-2.5 rounded-2xl border flex items-center space-x-2.5 transition-all duration-150 group relative w-full
                          ${item.active
                            ? 'bg-emerald-50/90 border-emerald-300 text-emerald-900 shadow-sm'
                            : 'bg-slate-50 border-slate-200/80 text-slate-800 hover:bg-slate-100 active:scale-[0.98]'}
                        `}
                      >
                        <div className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-transform group-active:scale-95 relative ${
                          item.active ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200/60 shadow-sm'
                        }`}>
                          <Icon size={16} />
                          {item.badge && (
                            <span className="absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-500 rounded-full ring-2 ring-white animate-pulse" />
                          )}
                        </div>

                        <div className="min-w-0 flex-1">
                          <div className="flex items-center justify-between">
                            <h4 className="text-xs font-bold text-slate-900 truncate leading-tight">{item.name}</h4>
                            {item.badge && (
                              <span className="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-rose-500 text-white shadow-xs ml-1 shrink-0 animate-pulse">
                                {item.badge}
                              </span>
                            )}
                          </div>
                          <span className="text-[10px] text-slate-500 font-semibold block truncate mt-0.5">{item.shortName}</span>
                        </div>

                        {item.active && (
                          <span className="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"></span>
                        )}
                      </Link>
                    </motion.div>
                  );
                })}
              </div>

              {/* Action Buttons Footer */}
              <div className="pt-2 border-t border-slate-100 flex items-center space-x-2">
                <button
                  type="button"
                  onClick={() => {
                    setMobileMenuOpen(false);
                    setActionMenuOpen(true);
                  }}
                  className="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs flex items-center justify-center space-x-1.5 transition-colors active:scale-[0.98]"
                >
                  <User size={15} className="text-slate-600" />
                  <span>Opsi Profil Saya</span>
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* NOTIFICATIONS MODAL WITH FRAMER MOTION */}
      <AnimatePresence>
        {notificationsOpen && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.15 }}
              className="fixed inset-0 bg-slate-950/60"
              onClick={() => setNotificationsOpen(false)}
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              transition={{ type: 'spring', stiffness: 400, damping: 30 }}
              style={{ willChange: 'transform, opacity' }}
              className="relative z-10 w-full max-w-md p-5 sm:p-6 rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto overflow-x-hidden transform-gpu"
            >
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div className="flex items-center space-x-2">
                  <div className="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                    <Bell size={18} />
                  </div>
                  <div>
                    <h3 className="text-sm sm:text-base font-extrabold text-slate-900">Pemberitahuan & Notifikasi</h3>
                    <p className="text-[11px] text-slate-500">Shortcut & status aktivitas persetujuan</p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setNotificationsOpen(false)}
                  className="p-1.5 rounded-lg bg-slate-100 text-slate-400 hover:text-slate-800"
                >
                  <X size={18} />
                </button>
              </div>

              <div className="space-y-3">
                {/* SECTION 1: TUGAS APPROVAL PENDING (JIKA ADA) */}
                {pendingApprovalsCount > 0 && (
                  <div className="p-3 rounded-2xl bg-rose-50 border border-rose-200/80 flex items-center justify-between">
                    <div className="flex items-center space-x-2 min-w-0">
                      <span className="flex h-2 w-2 relative shrink-0">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                      </span>
                      <p className="text-xs font-bold text-rose-900 truncate">
                        {pendingApprovalsCount} Pengajuan Menunggu Tindakan Anda
                      </p>
                    </div>
                    <Link
                      href={route('approvals.index', { status: 'pending' })}
                      onClick={() => setNotificationsOpen(false)}
                      className="px-2.5 py-1 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] shrink-0 shadow-xs"
                    >
                      Buka Semua
                    </Link>
                  </div>
                )}

                {/* LIST ITEM PENGAJUAN PENDING FOR THIS APPROVER */}
                {pendingApprovalsList.length > 0 ? (
                  <div className="space-y-2.5">
                    <p className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                      ⚡ Shortcut Pengajuan Masuk (Siap Di-ACC / Ditolak):
                    </p>
                    {pendingApprovalsList.map((req) => {
                      const stageLabel = req.current_stage === 'approval_1'
                        ? 'Approval 1 (Supervisor)'
                        : req.current_stage === 'approval_2'
                        ? 'Approval 2 (Manager)'
                        : 'HRD / PGA Admin';

                      const stageColor = req.current_stage === 'approval_1'
                        ? 'bg-amber-100 text-amber-900 border-amber-300'
                        : req.current_stage === 'approval_2'
                        ? 'bg-purple-100 text-purple-900 border-purple-300'
                        : 'bg-teal-100 text-teal-900 border-teal-300';

                      return (
                        <div
                          key={req.id}
                          className="p-3.5 rounded-2xl bg-white border border-slate-200 hover:border-emerald-300 shadow-sm transition-all space-y-2"
                        >
                          <div className="flex items-center justify-between gap-2">
                            <div className="flex items-center space-x-2 min-w-0">
                              <UserAvatar user={req.user} size="w-7 h-7" textSize="text-xs" />
                              <div className="min-w-0">
                                <h4 className="text-xs font-black text-slate-900 truncate">{req.user?.name}</h4>
                                <p className="text-[10px] text-slate-500 truncate">{req.user?.nik} &bull; {req.user?.department?.name || 'General'}</p>
                              </div>
                            </div>
                            <span className={`px-2 py-0.5 rounded-full text-[9px] font-extrabold border shrink-0 ${stageColor}`}>
                              {stageLabel}
                            </span>
                          </div>

                          <div className="p-2 rounded-xl bg-slate-50 border border-slate-100 text-[11px] space-y-0.5">
                            <div className="flex items-center justify-between font-bold text-slate-800">
                              <span>{req.category?.name || 'Cuti'}</span>
                              <span className="text-emerald-700">{req.amount} {req.unit}</span>
                            </div>
                            <p className="text-[10px] text-slate-500">
                              {req.start_date} s/d {req.end_date}
                            </p>
                            {req.reason && (
                              <p className="text-[10px] text-slate-600 italic line-clamp-1 mt-0.5">
                                "{req.reason}"
                              </p>
                            )}
                          </div>

                          <Link
                            href={route('approvals.index', { search: req.request_number, status: 'pending' })}
                            onClick={() => setNotificationsOpen(false)}
                            className="w-full py-1.5 px-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs flex items-center justify-center space-x-1.5 shadow-sm shadow-emerald-600/20 transition-all"
                          >
                            <span>⚡ Proses Approval ({req.request_number})</span>
                            <ChevronRight size={13} />
                          </Link>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  (isManager || isAdmin) && (
                    <div className="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-center space-y-1">
                      <CheckCircle size={24} className="text-emerald-600 mx-auto" />
                      <p className="text-xs font-bold text-emerald-950">Semua Approval Selesai</p>
                      <p className="text-[11px] text-emerald-700">
                        Tidak ada pengajuan cuti yang memerlukan persetujuan Anda saat ini.
                      </p>
                    </div>
                  )
                )}

                {/* SECTION 2: RIWAYAT PENGAJUAN SAYA (UNTUK SEMUA USER) */}
                {myRecentRequests.length > 0 && (
                  <div className="space-y-2 pt-2 border-t border-slate-100">
                    <p className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                      📋 Status Pengajuan Cuti Anda:
                    </p>
                    {myRecentRequests.map((myReq) => {
                      const statusColor = myReq.status === 'approved'
                        ? 'bg-emerald-100 text-emerald-900 border-emerald-300'
                        : myReq.status === 'rejected'
                        ? 'bg-rose-100 text-rose-900 border-rose-300'
                        : 'bg-amber-100 text-amber-900 border-amber-300';

                      const statusText = myReq.status === 'approved'
                        ? 'Disetujui'
                        : myReq.status === 'rejected'
                        ? 'Ditolak'
                        : 'Menunggu Persetujuan';

                      return (
                        <div key={myReq.id} className="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                          <div>
                            <span className="font-bold text-slate-900 block text-[11px]">{myReq.category?.name} ({myReq.amount} {myReq.unit})</span>
                            <span className="text-[10px] text-slate-500">{myReq.start_date} s/d {myReq.end_date}</span>
                          </div>
                          <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold border ${statusColor}`}>
                            {statusText}
                          </span>
                        </div>
                      );
                    })}
                  </div>
                )}
              </div>

              <div className="pt-2 flex items-center space-x-2 border-t border-slate-100">
                <Link
                  href={route('leave-requests.index')}
                  onClick={() => setNotificationsOpen(false)}
                  className="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs text-center transition-colors"
                >
                  Riwayat Pengajuan
                </Link>
                {(isManager || isAdmin) && (
                  <Link
                    href={route('approvals.index')}
                    onClick={() => setNotificationsOpen(false)}
                    className="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs text-center shadow-md shadow-emerald-600/20 transition-all"
                  >
                    Halaman Approval
                  </Link>
                )}
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* PROFILE ACTION MENU MODAL WITH FRAMER MOTION */}
      <AnimatePresence>
        {actionMenuOpen && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.15 }}
              className="fixed inset-0 bg-slate-950/60"
              onClick={() => setActionMenuOpen(false)}
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              transition={{ type: 'spring', stiffness: 400, damping: 30 }}
              style={{ willChange: 'transform, opacity' }}
              className="relative z-10 w-full max-w-sm p-6 rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4 transform-gpu"
            >
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div className="flex items-center space-x-3">
                  <UserAvatar user={user} size="w-10 h-10" textSize="text-base" />
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">{user.name}</h3>
                    <p className="text-xs text-slate-500">{user.email}</p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setActionMenuOpen(false)}
                  className="p-1.5 rounded-lg bg-slate-100 text-slate-400 hover:text-slate-800"
                >
                  <X size={18} />
                </button>
              </div>

              <p className="text-xs font-bold text-slate-400 uppercase tracking-wider">Pilih Aksi:</p>

              <div className="space-y-2">
                {/* Opsi 1: Profil Saya */}
                <button
                  type="button"
                  onClick={() => {
                    setActionMenuOpen(false);
                    setMyProfileOpen(true);
                  }}
                  className="w-full p-3.5 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-slate-100 flex items-center justify-between transition-all active:scale-[0.98]"
                >
                  <div className="flex items-center space-x-3">
                    <div className="p-2 rounded-xl bg-emerald-100 text-emerald-700">
                      <User size={20} />
                    </div>
                    <div className="text-left">
                      <h4 className="text-xs font-bold text-slate-900">Profil Saya & Upload Foto</h4>
                      <p className="text-[10px] text-slate-500">Lihat data NIK, departemen & ganti foto profil</p>
                    </div>
                  </div>
                  <ChevronRight size={16} className="text-slate-400" />
                </button>

                {/* Opsi 2: Install PWA App */}
                <button
                  type="button"
                  onClick={() => {
                    setActionMenuOpen(false);
                    window.dispatchEvent(new CustomEvent('open-pwa-install-modal'));
                  }}
                  className="w-full p-3.5 rounded-2xl border border-emerald-200 bg-emerald-50/80 hover:bg-emerald-100/80 flex items-center justify-between transition-all active:scale-[0.98]"
                >
                  <div className="flex items-center space-x-3">
                    <div className="p-2 rounded-xl bg-emerald-600 text-white shadow-xs">
                      <Smartphone size={20} />
                    </div>
                    <div className="text-left">
                      <h4 className="text-xs font-bold text-emerald-950">Pasang Aplikasi (PWA)</h4>
                      <p className="text-[10px] text-emerald-700">Install di HP / Desktop untuk akses 1-klik</p>
                    </div>
                  </div>
                  <ChevronRight size={16} className="text-emerald-500" />
                </button>

                {/* Opsi 3: Logout */}
                <button
                  type="button"
                  onClick={handleLogout}
                  className="w-full p-3.5 rounded-2xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 flex items-center justify-between transition-all active:scale-[0.98]"
                >
                  <div className="flex items-center space-x-3">
                    <div className="p-2 rounded-xl bg-rose-100 text-rose-700">
                      <LogOut size={20} />
                    </div>
                    <div className="text-left">
                      <h4 className="text-xs font-bold text-rose-800">Keluar (Logout)</h4>
                      <p className="text-[10px] text-rose-600">Keluar dari akun {appName}</p>
                    </div>
                  </div>
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* FULL MY PROFILE MODAL WITH FRAMER MOTION */}
      <AnimatePresence>
        {myProfileOpen && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.15 }}
              className="fixed inset-0 bg-slate-950/60"
              onClick={() => setMyProfileOpen(false)}
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              transition={{ type: 'spring', stiffness: 400, damping: 30 }}
              style={{ willChange: 'transform, opacity' }}
              className="relative z-10 w-full max-w-md p-6 rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-5 transform-gpu"
            >
              <div className="flex items-center justify-between border-b border-slate-100 pb-3.5">
                <div className="flex items-center space-x-2">
                  <User size={20} className="text-emerald-600" />
                  <h3 className="text-base font-extrabold text-slate-900">Profil Karyawan Saya</h3>
                </div>
                <button
                  onClick={() => setMyProfileOpen(false)}
                  className="p-1.5 rounded-lg bg-slate-100 text-slate-400 hover:text-slate-800"
                >
                  <X size={18} />
                </button>
              </div>

              {/* Profile Avatar Header with Photo Upload Button */}
              <div className="p-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 text-white flex items-center justify-between shadow-md shadow-emerald-600/20">
                <div className="flex items-center space-x-3.5 min-w-0">
                  <div className="relative group shrink-0">
                    <UserAvatar user={user} size="w-14 h-14" textSize="text-xl" />

                    {/* Upload Overlay Button */}
                    <label className="absolute inset-0 bg-slate-950/50 hover:bg-slate-950/70 rounded-full flex flex-col items-center justify-center cursor-pointer opacity-80 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                      <Camera size={16} className="text-white" />
                      <input
                        type="file"
                        accept="image/*"
                        onChange={handleAvatarUpload}
                        disabled={uploading}
                        className="hidden"
                      />
                    </label>
                  </div>

                  <div className="min-w-0">
                    <h2 className="text-base font-black tracking-tight truncate">{user.name}</h2>
                    <p className="text-xs text-emerald-100 truncate">{user.email}</p>
                    <span className="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-white text-emerald-800">
                      {user.role}
                    </span>
                  </div>
                </div>

                <label className="px-3 py-2 rounded-xl bg-white/20 hover:bg-white/30 text-white font-bold text-xs flex items-center space-x-1 cursor-pointer transition-colors shrink-0">
                  <Upload size={14} />
                  <span className="text-[11px]">{uploading ? 'Mengunggah...' : 'Ganti Foto'}</span>
                  <input
                    type="file"
                    accept="image/*"
                    onChange={handleAvatarUpload}
                    disabled={uploading}
                    className="hidden"
                  />
                </label>
              </div>

              {/* Complete User Details Grid */}
              <div className="space-y-2.5 text-xs">
                <div className="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                  <span className="text-slate-500 font-medium">Nama Lengkap</span>
                  <span className="font-bold text-slate-900">{user.name}</span>
                </div>

                <div className="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                  <span className="text-slate-500 font-medium">No Induk Karyawan (NIK)</span>
                  <span className="font-mono font-bold text-emerald-700">{user.nik || 'EMP-201'}</span>
                </div>

                <div className="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                  <span className="text-slate-500 font-medium">Email Terdaftar</span>
                  <span className="font-semibold text-slate-800">{user.email}</span>
                </div>

                <div className="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                  <span className="text-slate-500 font-medium">Departemen / Divisi</span>
                  <span className="font-bold text-slate-900">{user.department_name || user.department?.name || 'Information Technology'}</span>
                </div>

                <div className="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                  <span className="text-slate-500 font-medium">Jabatan / Role</span>
                  <span className="uppercase font-bold text-purple-700">{user.role}</span>
                </div>

                <div className="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                  <span className="text-slate-500 font-medium">Status Akun Karyawan</span>
                  <span className="font-bold text-emerald-600 flex items-center space-x-1">
                    <Check size={14} />
                    <span>Aktif (Terverifikasi)</span>
                  </span>
                </div>
              </div>

              <div className="pt-2">
                <button
                  onClick={() => setMyProfileOpen(false)}
                  className="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs transition-colors shadow-md shadow-emerald-600/20"
                >
                  Tutup Profil
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* PWA FLOATING INSTALL PROMPT MODAL */}
      <PwaInstallModal />
    </div>
  );
}
