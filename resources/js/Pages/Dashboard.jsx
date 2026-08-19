import React from 'react';
import { Link } from '@inertiajs/react';
import AuthenticatedLayout, { UserAvatar } from '@/Layouts/AuthenticatedLayout';
import { motion } from 'framer-motion';
import {
  Calendar,
  Clock,
  CheckCircle,
  XCircle,
  FilePlus,
  ArrowUpRight,
  User,
  Users,
  Building,
  AlertCircle,
  FileSpreadsheet,
  CalendarDays,
  ChevronRight,
  Plus,
  FileText,
  HeartPulse,
  Briefcase,
  Layers,
  Bell
} from 'lucide-react';

const containerVariants = {
  hidden: { opacity: 0 },
  show: {
    opacity: 1,
    transition: {
      staggerChildren: 0.06
    }
  }
};

const itemVariants = {
  hidden: { opacity: 0, y: 12 },
  show: { opacity: 1, y: 0, transition: { duration: 0.3, ease: 'easeOut' } }
};

export default function Dashboard({ user, stats, recentRequests, managerPendingCount, teamRequests, hrdMetrics, onOpenProfileMenu }) {
  const isManager = user.role === 'manager';
  const isAdmin = user.role === 'admin';

  return (
    <AuthenticatedLayout title="Dashboard Utama">
      <motion.div
        variants={containerVariants}
        initial="hidden"
        animate="show"
        className="space-y-6"
      >

        {/* Mobile / Compact Greeting Header */}
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
              Halo, {user.name?.split(' ')[0] || user.name} 👋
            </h2>
            <p className="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">
              Selamat datang kembali!
            </p>
          </div>
          <div className="flex items-center space-x-2">
            <button
              type="button"
              onClick={() => window.dispatchEvent(new CustomEvent('open-notifications-menu'))}
              className="p-2 sm:p-2.5 rounded-full bg-white border border-slate-200 text-slate-600 hover:text-emerald-600 shadow-sm relative transition-transform hover:scale-105"
            >
              <Bell size={18} />
              {managerPendingCount > 0 && (
                <span className="absolute top-1 right-1 w-2.5 h-2.5 bg-rose-500 rounded-full ring-2 ring-white animate-pulse"></span>
              )}
            </button>

            {/* Profile Avatar (Samping Notification Bell) */}
            <button
              type="button"
              onClick={() => window.dispatchEvent(new CustomEvent('open-profile-menu'))}
              className="flex items-center space-x-2 p-1 sm:px-3 sm:py-1.5 rounded-full bg-white border border-slate-200 hover:bg-slate-50 transition-transform hover:scale-105 shadow-sm"
            >
              <UserAvatar user={user} size="w-8 h-8" textSize="text-xs" />
              <span className="text-xs font-extrabold text-slate-800 hidden sm:inline max-w-[90px] truncate">
                {user.name?.split(' ')[0]}
              </span>
            </button>
          </div>
        </div>

        {/* Manager / Admin Alert Banner */}
        {(isManager || isAdmin) && managerPendingCount > 0 && (
          <div className="p-4 sm:p-5 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 animate-fade-in shadow-sm">
            <div className="flex items-start space-x-3">
              <div className="p-2.5 rounded-xl bg-amber-100 text-amber-700 shrink-0 mt-0.5 sm:mt-0">
                <AlertCircle size={20} />
              </div>
              <div>
                <h4 className="text-xs sm:text-sm font-bold text-amber-900">Perhatian: Ada {managerPendingCount} Pengajuan Menunggu Persetujuan!</h4>
                <p className="text-[11px] sm:text-xs text-amber-800 leading-relaxed">Anggota tim Anda membutuhkan persetujuan (approval) untuk pengajuan izin/cuti mereka.</p>
              </div>
            </div>
            <Link
              href={route('approvals.index')}
              className="w-full sm:w-auto px-4 py-2 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 text-center transition-colors shrink-0 shadow-sm"
            >
              Tinjau Sekarang
            </Link>
          </div>
        )}

        {/* Hero Card Banner (Emerald Accent matching mockup) */}
        <div className="p-6 rounded-3xl bg-gradient-to-r from-[#0FA172] to-[#1CB67C] text-white shadow-lg shadow-emerald-600/20 relative overflow-hidden">
          <div className="absolute -right-8 -bottom-8 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

          <div className="relative z-10 flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white shrink-0 shadow-inner">
                <FileText size={24} />
              </div>
              <div>
                <h3 className="text-base sm:text-lg font-black tracking-tight text-white">Buat Pengajuan Baru</h3>
                <p className="text-xs text-emerald-100 mt-0.5 font-medium">Ajukan permohonan/pemberitahuan dengan mudah dan cepat</p>
              </div>
            </div>

            <Link
              href={route('leave-requests.create')}
              className="w-12 h-12 sm:w-auto sm:h-auto sm:px-5 sm:py-3 rounded-full sm:rounded-2xl bg-white text-emerald-700 hover:bg-slate-50 font-extrabold text-xs shadow-md flex items-center justify-center space-x-2 transition-transform hover:scale-105 shrink-0"
            >
              <Plus size={22} className="sm:size-18" />
              <span className="hidden sm:inline">Buat Pengajuan</span>
            </Link>
          </div>
        </div>



        {/* Ringkasan Stats Grid matching mockup */}
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider">Ringkasan Pengajuan</h3>
            <Link href={route('leave-requests.index')} className="text-xs font-bold text-emerald-600 hover:underline">
              Lihat Semua
            </Link>
          </div>

          <div className="grid grid-cols-4 gap-2 sm:gap-4">
            {/* TOTAL */}
            <div className="p-3 sm:p-4 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col items-center text-center space-y-1">
              <span className="px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 font-extrabold text-[9px] uppercase tracking-wider">
                TOTAL
              </span>
              <span className="text-lg sm:text-2xl font-black text-slate-900">
                {(stats.pending_requests || 0) + (stats.approved_requests || 0) + (stats.rejected_requests || 0)}
              </span>
              <span className="text-[10px] text-slate-400 font-medium">Pengajuan</span>
            </div>

            {/* PROSES */}
            <div className="p-3 sm:p-4 rounded-2xl bg-white border border-amber-200/60 shadow-sm flex flex-col items-center text-center space-y-1">
              <span className="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-extrabold text-[9px] uppercase tracking-wider">
                PROSES
              </span>
              <span className="text-lg sm:text-2xl font-black text-amber-600">{stats.pending_requests || 0}</span>
              <span className="text-[10px] text-slate-400 font-medium">Proses</span>
            </div>

            {/* SELESAI */}
            <div className="p-3 sm:p-4 rounded-2xl bg-white border border-emerald-200/60 shadow-sm flex flex-col items-center text-center space-y-1">
              <span className="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-extrabold text-[9px] uppercase tracking-wider">
                SELESAI
              </span>
              <span className="text-lg sm:text-2xl font-black text-emerald-600">{stats.approved_requests || 0}</span>
              <span className="text-[10px] text-slate-400 font-medium">Selesai</span>
            </div>

            {/* TOLAK */}
            <div className="p-3 sm:p-4 rounded-2xl bg-white border border-rose-200/60 shadow-sm flex flex-col items-center text-center space-y-1">
              <span className="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-extrabold text-[9px] uppercase tracking-wider">
                TOLAK
              </span>
              <span className="text-lg sm:text-2xl font-black text-rose-600">{stats.rejected_requests || 0}</span>
              <span className="text-[10px] text-slate-400 font-medium">Ditolak</span>
            </div>
          </div>
        </div>

        {/* Riwayat Pengajuan Terakhir List (Matching mockup card layout) */}
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold text-slate-900">Riwayat Pengajuan Terakhir</h3>
            <Link href={route('leave-requests.index')} className="text-xs font-bold text-emerald-600 hover:underline">
              Lihat Semua
            </Link>
          </div>

          {recentRequests && recentRequests.length > 0 ? (
            <div className="space-y-2.5">
              {recentRequests.map((req) => (
                <Link
                  key={req.id}
                  href={route('leave-requests.index')}
                  className="p-4 rounded-2xl bg-white border border-slate-200 hover:border-emerald-300 hover:shadow-md flex items-center justify-between transition-all group"
                >
                  <div className="flex items-center space-x-3.5 min-w-0">
                    <div className="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                      <FileText size={20} />
                    </div>
                    <div className="min-w-0">
                      <div className="flex items-center space-x-2">
                        <span className="font-mono text-xs font-bold text-slate-900">{req.request_number}</span>
                      </div>
                      <p className="text-xs font-semibold text-slate-500 truncate">{req.category?.name}</p>
                      <p className="text-[11px] text-slate-400 font-medium mt-0.5">
                        {req.start_date} &bull; {req.amount} {req.unit}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center space-x-3 shrink-0">
                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold ${
                      req.status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                      req.status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200' :
                      'bg-amber-100 text-amber-800 border border-amber-200'
                    }`}>
                      {req.status === 'approved' ? 'Disetujui' : req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                    </span>
                    <ChevronRight size={18} className="text-slate-400 group-hover:text-emerald-600" />
                  </div>
                </Link>
              ))}
            </div>
          ) : (
            <div className="p-8 rounded-2xl bg-white border border-slate-200 text-center text-slate-400 space-y-2">
              <Calendar size={36} className="mx-auto opacity-40 text-slate-400" />
              <p className="text-xs font-semibold">Belum ada riwayat pengajuan cuti.</p>
              <Link href={route('leave-requests.create')} className="text-xs text-emerald-600 font-bold hover:underline inline-block">
                + Buat Pengajuan Pertama
              </Link>
            </div>
          )}
        </div>

      </motion.div>
    </AuthenticatedLayout>
  );
}
