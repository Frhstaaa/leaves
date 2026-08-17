import React, { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
  LayoutDashboard,
  FilePlus,
  History,
  CheckSquare,
  Users,
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
  AlertCircle
} from 'lucide-react';

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
  const { auth, flash, notifications } = usePage().props;
  const user = auth?.user || {};

  // Modals State
  const [actionMenuOpen, setActionMenuOpen] = useState(false);
  const [myProfileOpen, setMyProfileOpen] = useState(false);
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [uploading, setUploading] = useState(false);

  const isEmployee = user.role === 'employee';
  const isManager = user.role === 'manager';
  const isAdmin = user.role === 'admin';

  // Listen to global open profile & notification events
  useEffect(() => {
    const handleOpenProfile = () => setActionMenuOpen(true);
    const handleOpenNotifications = () => setNotificationsOpen(true);

    window.addEventListener('open-profile-menu', handleOpenProfile);
    window.addEventListener('open-notifications-menu', handleOpenNotifications);

    return () => {
      window.removeEventListener('open-profile-menu', handleOpenProfile);
      window.removeEventListener('open-notifications-menu', handleOpenNotifications);
    };
  }, []);

  const handleLogout = (e) => {
    e.preventDefault();
    router.post(route('logout'));
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
  const isHrd = url.startsWith('/hrd');

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
      name: 'Persetujuan Team',
      shortName: 'Approval',
      href: route('approvals.index'),
      icon: CheckSquare,
      active: isApproval,
      show: isManager || isAdmin,
    },
    {
      name: 'Rekapitulasi HRD',
      shortName: 'HRD',
      href: route('hrd.index'),
      icon: FileSpreadsheet,
      active: isHrd,
      show: isAdmin,
    },
  ];

  return (
    <div className="min-h-screen bg-[#F5FAF7] text-slate-900 flex flex-col md:flex-row font-sans pb-20 md:pb-0">

      {/* DESKTOP SIDEBAR */}
      <aside className="hidden md:flex w-64 flex-col shrink-0 min-h-screen sticky top-0 h-screen bg-white border-r border-slate-200 shadow-sm">
        {/* Brand Header */}
        <div className="p-5 border-b border-slate-200 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-black text-white text-base shadow-lg shadow-emerald-600/20">
              SG
            </div>
            <div>
              <h2 className="font-extrabold text-base text-slate-900 tracking-tight leading-none">Form SGIN</h2>
              <span className="text-[11px] font-bold text-emerald-600 tracking-wide uppercase">Cuti & Ketidakhadiran</span>
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
                {item.active && <ChevronRight size={16} className="text-white/80" />}
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
        {/* Top Desktop Bar */}
        <header className="hidden md:flex items-center justify-between px-8 py-4 bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
          <div>
            <h1 className="text-lg font-bold text-slate-900">{title || 'Dashboard'}</h1>
            <p className="text-xs text-slate-500">Sistem Permohonan Tidak Hadir Bekerja & Cuti Real-time</p>
          </div>

          <div className="flex items-center space-x-4">
            <button
              onClick={() => setNotificationsOpen(true)}
              className="p-2 rounded-full bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-600 relative transition-colors"
            >
              <Bell size={18} />
              <span className="absolute top-1 right-1 w-2 h-2 bg-emerald-500 rounded-full"></span>
            </button>

            <div className="flex items-center space-x-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-xs text-slate-700 font-medium">
              <Briefcase size={14} className="text-emerald-600" />
              <span>{user.department_name || user.department?.name || 'General'}</span>
            </div>

            <div className="h-4 w-[1px] bg-slate-200"></div>

            {/* Profile Avatar Trigger */}
            <button
              onClick={() => setActionMenuOpen(true)}
              className="flex items-center space-x-2 text-xs font-bold text-slate-800 hover:text-emerald-600 transition-colors"
            >
              <UserAvatar user={user} size="w-7 h-7" textSize="text-xs" />
              <span>{user.name}</span>
            </button>
          </div>
        </header>

        {/* Flash Notifications */}
        {flash?.success && (
          <div className="mx-4 sm:mx-6 mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-center justify-between animate-fade-in shadow-sm">
            <div className="flex items-center space-x-2">
              <CheckSquare size={18} className="text-emerald-600 shrink-0" />
              <span>{flash.success}</span>
            </div>
          </div>
        )}

        {flash?.error && (
          <div className="mx-4 sm:mx-6 mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-medium flex items-center justify-between animate-fade-in shadow-sm">
            <div className="flex items-center space-x-2">
              <X size={18} className="text-rose-600 shrink-0" />
              <span>{flash.error}</span>
            </div>
          </div>
        )}

        {/* Body Content */}
        <main className="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
          {children}
        </main>
      </div>

      {/* MOBILE FIXED BOTTOM NAVIGATION BAR WITH VECTOR PROFILE ICON */}
      <div className="md:hidden fixed bottom-0 inset-x-0 z-50 bg-white/95 backdrop-blur-2xl border-t border-slate-200 px-2 py-2 flex items-center justify-around shadow-lg">
        {navItems.filter(item => item.show).map((item) => {
          const Icon = item.icon;
          return (
            <Link
              key={item.name}
              href={item.href}
              className={`
                flex flex-col items-center justify-center space-y-1 px-2.5 py-1.5 rounded-xl transition-all duration-200
                ${item.active
                  ? 'text-emerald-600 font-extrabold bg-emerald-50 border border-emerald-200'
                  : 'text-slate-500 hover:text-slate-900'}
              `}
            >
              <Icon size={20} className={item.active ? 'text-emerald-600' : 'text-slate-400'} />
              <span className="text-[10px] tracking-tight">{item.shortName}</span>
            </Link>
          );
        })}

        {/* Dedicated Profile Menu Item in Bottom Bar (Pure Vector Icon Only) */}
        <button
          onClick={() => setActionMenuOpen(true)}
          className="flex flex-col items-center justify-center space-y-1 px-2.5 py-1.5 rounded-xl transition-all duration-200 text-slate-500 hover:text-slate-900"
        >
          <User size={20} className="text-slate-400" />
          <span className="text-[10px] tracking-tight font-medium">Profil</span>
        </button>
      </div>

      {/* NOTIFICATIONS & PEMBERITAHUAN MODAL */}
      {notificationsOpen && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 animate-fade-in">
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" onClick={() => setNotificationsOpen(false)} />
          <div className="relative z-10 w-full max-w-md p-6 rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center space-x-2">
                <Bell size={20} className="text-emerald-600" />
                <h3 className="text-base font-extrabold text-slate-900">Pemberitahuan & Notifikasi</h3>
              </div>
              <button
                onClick={() => setNotificationsOpen(false)}
                className="p-1.5 rounded-lg bg-slate-100 text-slate-400 hover:text-slate-800"
              >
                <X size={18} />
              </button>
            </div>

            <div className="space-y-3 max-h-80 overflow-y-auto pr-1">
              {/* Notification Item 1: Pending Notification */}
              <div className="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 space-y-1">
                <div className="flex items-center justify-between">
                  <span className="text-[10px] font-bold text-amber-800 uppercase flex items-center space-x-1">
                    <Clock size={12} />
                    <span>Pengajuan Cuti Diteruskan</span>
                  </span>
                  <span className="text-[10px] text-amber-700">Baru Saja</span>
                </div>
                <p className="text-xs font-bold text-slate-900">Pengajuan Cuti Anda Berhasil Dikirim</p>
                <p className="text-[11px] text-slate-600 leading-relaxed">
                  Permohonan telah sampai ke sistem dan sedang menunggu persetujuan (approval) dari Kepala Departemen Anda.
                </p>
              </div>

              {/* Notification Item 2: Status Disetujui */}
              <div className="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 space-y-1">
                <div className="flex items-center justify-between">
                  <span className="text-[10px] font-bold text-emerald-800 uppercase flex items-center space-x-1">
                    <CheckCircle size={12} />
                    <span>Persetujuan Disetujui</span>
                  </span>
                  <span className="text-[10px] text-emerald-700">Kemarin</span>
                </div>
                <p className="text-xs font-bold text-slate-900">Pengajuan Cuti Telah Disetujui</p>
                <p className="text-[11px] text-slate-600 leading-relaxed">
                  Pengajuan permohonan cuti Anda sebelumnya telah resmi disetujui oleh Manager Departemen.
                </p>
              </div>

              {/* Manager Alert Notification */}
              {(isManager || isAdmin) && (
                <div className="p-3.5 rounded-2xl bg-teal-50 border border-teal-200 space-y-1">
                  <div className="flex items-center justify-between">
                    <span className="text-[10px] font-bold text-teal-800 uppercase flex items-center space-x-1">
                      <AlertCircle size={12} />
                      <span>Panel Approval Team</span>
                    </span>
                    <span className="text-[10px] text-teal-700">Info Tim</span>
                  </div>
                  <p className="text-xs font-bold text-slate-900">Persetujuan Cuti Anggota Tim</p>
                  <p className="text-[11px] text-slate-600 leading-relaxed">
                    Sebagai Manager/HRD, Anda dapat meninjau dan menyetujui pengajuan anggota tim di menu <strong className="text-teal-800">Persetujuan Team</strong>.
                  </p>
                </div>
              )}
            </div>

            <div className="pt-2 flex items-center space-x-2">
              <Link
                href={route('leave-requests.index')}
                onClick={() => setNotificationsOpen(false)}
                className="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs text-center transition-colors"
              >
                Lihat Riwayat Cuti
              </Link>
              <button
                onClick={() => setNotificationsOpen(false)}
                className="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}

      {/* PROFILE ACTION MENU MODAL */}
      {actionMenuOpen && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 animate-fade-in">
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" onClick={() => setActionMenuOpen(false)} />
          <div className="relative z-10 w-full max-w-sm p-6 rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center space-x-3">
                <UserAvatar user={user} size="w-10 h-10" textSize="text-base" />
                <div>
                  <h3 className="text-sm font-bold text-slate-900">{user.name}</h3>
                  <p className="text-xs text-slate-500">{user.email}</p>
                </div>
              </div>
              <button
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
                onClick={() => {
                  setActionMenuOpen(false);
                  setMyProfileOpen(true);
                }}
                className="w-full p-3.5 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-slate-100 flex items-center justify-between transition-all"
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

              {/* Opsi 2: Logout */}
              <button
                onClick={handleLogout}
                className="w-full p-3.5 rounded-2xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 flex items-center justify-between transition-all"
              >
                <div className="flex items-center space-x-3">
                  <div className="p-2 rounded-xl bg-rose-100 text-rose-700">
                    <LogOut size={20} />
                  </div>
                  <div className="text-left">
                    <h4 className="text-xs font-bold text-rose-800">Keluar (Logout)</h4>
                    <p className="text-[10px] text-rose-600">Keluar dari akun Form SGIN</p>
                  </div>
                </div>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* FULL MY PROFILE MODAL ("Profil Saya & Update Foto") */}
      {myProfileOpen && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 animate-fade-in">
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" onClick={() => setMyProfileOpen(false)} />
          <div className="relative z-10 w-full max-w-md p-6 rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-5">
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
          </div>
        </div>
      )}
    </div>
  );
}
