import React from 'react';
import { Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { motion } from 'framer-motion';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import {
  Calendar,
  Clock,
  CheckCircle2,
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
  Bell,
  Sparkles,
  ShieldCheck,
  CheckSquare,
  BarChart3,
  TrendingUp,
  Activity
} from 'lucide-react';

const containerVariants = {
  hidden: { opacity: 0 },
  show: {
    opacity: 1,
    transition: {
      staggerChildren: 0.05
    }
  }
};

const itemVariants = {
  hidden: { opacity: 0, y: 12 },
  show: { opacity: 1, y: 0, transition: { duration: 0.25, ease: 'easeOut' } }
};

export default function Dashboard({
  user,
  stats = {},
  recentRequests = [],
  managerPendingCount = 0,
  teamRequests = [],
  hrdMetrics = {}
}) {
  const isManager = user.role === 'manager';
  const isAdmin = user.role === 'admin' || user.role === 'superadmin';

  const totalQuota = stats.total_quota || 12;
  const remainingQuota = stats.remaining_quota ?? (totalQuota - (stats.used_quota || 0));
  const usedQuota = stats.used_quota || (totalQuota - remainingQuota);
  const quotaPercent = Math.min(Math.round((remainingQuota / totalQuota) * 100), 100);

  const totalRequests = (stats.pending_requests || 0) + (stats.approved_requests || 0) + (stats.rejected_requests || 0);

  return (
    <AuthenticatedLayout title="Dashboard Utama">
      <motion.div
        variants={containerVariants}
        initial="hidden"
        animate="show"
        className="space-y-5 sm:space-y-6"
      >
        {/* ========================================================================= */}
        {/* 1. TOP GREETING & USER PROFILE SUMMARY (MOBILE & DESKTOP OPTIMIZED)       */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="flex items-center justify-between gap-3">
          <div className="flex items-center space-x-3 sm:space-x-4 min-w-0">
            <Avatar className="w-12 h-12 sm:w-14 sm:h-14 border-2 border-emerald-500/30 ring-2 ring-emerald-500/10">
              <AvatarImage src={user.avatar_url} alt={user.name} />
              <AvatarFallback className="text-base font-black">
                {user.name?.charAt(0) || 'U'}
              </AvatarFallback>
            </Avatar>

            <div className="min-w-0">
              <div className="flex items-center space-x-2">
                <h2 className="text-lg sm:text-2xl font-black text-slate-900 tracking-tight truncate">
                  Halo, {user.name?.split(' ')[0] || user.name}
                </h2>
                <Badge variant={isAdmin ? 'purple' : isManager ? 'info' : 'success'} className="capitalize text-[10px] hidden sm:inline-flex">
                  {user.role}
                </Badge>
              </div>
              <p className="text-xs text-slate-500 font-medium truncate mt-0.5">
                {user.nik || 'NIK: -'} &bull; {user.department?.name || 'Departemen General'}
              </p>
            </div>
          </div>

          {/* Quick Header Actions */}
          <div className="flex items-center space-x-2 shrink-0">
            <Button
              variant="outline"
              size="icon"
              className="relative rounded-2xl md:hidden"
              onClick={() => window.dispatchEvent(new CustomEvent('open-notifications-menu'))}
            >
              <Bell size={18} className="text-slate-600" />
              {managerPendingCount > 0 && (
                <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white animate-pulse" />
              )}
            </Button>

            <Link href={route('leave-requests.create')} className="hidden sm:inline-flex">
              <Button variant="default" className="rounded-2xl space-x-2">
                <Plus size={16} />
                <span>Buat Pengajuan</span>
              </Button>
            </Link>
          </div>
        </motion.div>

        {/* ========================================================================= */}
        {/* 2. MANAGER / ADMIN ALERT CARD (SHADCN CARD WITH WARNING BADGE)            */}
        {/* ========================================================================= */}
        {(isManager || isAdmin) && managerPendingCount > 0 && (
          <motion.div variants={itemVariants}>
            <Card className="border-amber-300 bg-amber-50/70 shadow-xs overflow-hidden">
              <CardContent className="p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div className="flex items-start space-x-3.5">
                  <div className="p-2.5 rounded-2xl bg-amber-100 text-amber-800 shrink-0 mt-0.5 sm:mt-0 shadow-2xs">
                    <AlertCircle size={22} />
                  </div>
                  <div>
                    <div className="flex items-center space-x-2">
                      <h4 className="text-xs sm:text-sm font-extrabold text-amber-950">
                        Persetujuan Cuti Menunggu Tindakan
                      </h4>
                      <Badge variant="warning" className="font-black text-[10px]">
                        {managerPendingCount} Pengajuan
                      </Badge>
                    </div>
                    <p className="text-[11px] sm:text-xs text-amber-900/80 leading-relaxed mt-0.5">
                      Terdapat pengajuan cuti anggota tim yang memerlukan persetujuan (approval) Anda.
                    </p>
                  </div>
                </div>

                <Link href={route('approvals.index')} className="w-full sm:w-auto shrink-0">
                  <Button variant="default" size="sm" className="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20">
                    <span>Tinjau Sekarang</span>
                    <ChevronRight size={14} className="ml-1" />
                  </Button>
                </Link>
              </CardContent>
            </Card>
          </motion.div>
        )}

        {/* ========================================================================= */}
        {/* 3. HERO BANNER + QUOTA PROGRESS CARD                                      */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
          {/* Main Action Banner */}
          <Card className="lg:col-span-2 border-0 bg-gradient-to-r from-[#0FA172] to-[#1CB67C] text-white shadow-lg shadow-emerald-600/20 relative overflow-hidden flex flex-col justify-between">
            <div className="absolute -right-8 -bottom-8 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none" />
            <div className="absolute -left-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none" />

            <CardHeader className="relative z-10 p-5 sm:p-7 pb-2 sm:pb-3">
              <div className="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-white/20 border border-white/30 text-white text-[11px] font-bold uppercase tracking-wider mb-2 backdrop-blur-md w-fit">
                <Sparkles size={13} className="text-emerald-100" />
                <span>Form Cuti Digital SGIN</span>
              </div>
              <CardTitle className="text-xl sm:text-2xl font-black text-white">
                Ajukan Cuti, Izin, atau Sakit
              </CardTitle>
              <CardDescription className="text-xs sm:text-sm text-emerald-50 mt-1 max-w-lg font-medium">
                Proses pengajuan cepat dengan alur persetujuan bertingkat dan notifikasi instan.
              </CardDescription>
            </CardHeader>

            <CardFooter className="relative z-10 p-5 sm:p-7 pt-3 flex items-center justify-between gap-3">
              <Link href={route('leave-requests.create')} className="w-full sm:w-auto">
                <Button size="lg" className="w-full sm:w-auto bg-white text-emerald-800 hover:bg-emerald-50 font-black shadow-md">
                  <Plus size={18} className="mr-1.5 text-emerald-700" />
                  <span>Buat Pengajuan Baru</span>
                </Button>
              </Link>

              <Link href={route('leave-requests.index')} className="hidden sm:inline-flex">
                <Button variant="ghost" className="text-white hover:bg-white/20 hover:text-white font-bold">
                  <span>Lihat Riwayat</span>
                  <ChevronRight size={16} className="ml-1" />
                </Button>
              </Link>
            </CardFooter>
          </Card>

          {/* Quota Progress Card */}
          <Card className="border-slate-200 bg-white flex flex-col justify-between p-5 sm:p-6 shadow-xs">
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                  Kuota Cuti Tahunan
                </span>
                <Badge variant="success" className="font-extrabold text-[10px]">
                  Tahun {new Date().getFullYear()}
                </Badge>
              </div>

              <div>
                <div className="flex items-baseline space-x-1.5">
                  <span className="text-3xl sm:text-4xl font-black text-slate-900">{remainingQuota}</span>
                  <span className="text-xs font-bold text-slate-400">/ {totalQuota} Hari Tersisa</span>
                </div>
                <p className="text-[11px] text-slate-500 mt-0.5">
                  {usedQuota} hari telah digunakan tahun ini.
                </p>
              </div>

              {/* Progress Bar */}
              <div className="space-y-1.5 pt-1">
                <div className="flex justify-between text-[11px] font-bold text-slate-600">
                  <span>Sisa Kuota</span>
                  <span className="text-emerald-700 font-extrabold">{quotaPercent}%</span>
                </div>
                <Progress value={quotaPercent} className="h-2.5 bg-slate-100" indicatorClassName="bg-emerald-600" />
              </div>
            </div>

            <div className="pt-4 mt-3 border-t border-slate-100 flex items-center justify-between text-xs">
              <span className="text-slate-400 font-medium">Status Hak Cuti</span>
              <span className="font-bold text-emerald-700 flex items-center space-x-1">
                <CheckCircle2 size={13} />
                <span>Aktif Digunakan</span>
              </span>
            </div>
          </Card>
        </motion.div>

        {/* ========================================================================= */}
        {/* 4. STATS METRIC CARDS (SHADCN CARDS WITH BADGES & HOVER ANIMATION)        */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="space-y-2.5">
          <div className="flex items-center justify-between">
            <h3 className="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
              Ringkasan Permohonan Cuti Pribadi
            </h3>
            <Link href={route('leave-requests.index')} className="text-xs font-bold text-emerald-600 hover:underline">
              Lihat Detail &rarr;
            </Link>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4">
            {/* TOTAL */}
            <Card className="hover:shadow-md transition-all border-slate-200">
              <CardContent className="p-3.5 sm:p-5 flex items-center space-x-3">
                <div className="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold shrink-0">
                  <Layers size={20} />
                </div>
                <div className="min-w-0">
                  <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase truncate">Total Diajukan</p>
                  <h4 className="text-lg sm:text-2xl font-black text-slate-900">{totalRequests}</h4>
                </div>
              </CardContent>
            </Card>

            {/* PENDING / PROSES */}
            <Card className="hover:shadow-md transition-all border-amber-200/80 bg-amber-50/20">
              <CardContent className="p-3.5 sm:p-5 flex items-center space-x-3">
                <div className="w-10 h-10 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold shrink-0">
                  <Clock size={20} />
                </div>
                <div className="min-w-0">
                  <p className="text-[10px] sm:text-xs font-extrabold text-amber-700 uppercase truncate">Menunggu</p>
                  <h4 className="text-lg sm:text-2xl font-black text-amber-700">{stats.pending_requests || 0}</h4>
                </div>
              </CardContent>
            </Card>

            {/* APPROVED / SELESAI */}
            <Card className="hover:shadow-md transition-all border-emerald-200/80 bg-emerald-50/20">
              <CardContent className="p-3.5 sm:p-5 flex items-center space-x-3">
                <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0">
                  <CheckCircle2 size={20} />
                </div>
                <div className="min-w-0">
                  <p className="text-[10px] sm:text-xs font-extrabold text-emerald-700 uppercase truncate">Disetujui</p>
                  <h4 className="text-lg sm:text-2xl font-black text-emerald-700">{stats.approved_requests || 0}</h4>
                </div>
              </CardContent>
            </Card>

            {/* REJECTED / DITOLAK */}
            <Card className="hover:shadow-md transition-all border-rose-200/80 bg-rose-50/20">
              <CardContent className="p-3.5 sm:p-5 flex items-center space-x-3">
                <div className="w-10 h-10 rounded-2xl bg-rose-100 text-rose-800 flex items-center justify-center font-bold shrink-0">
                  <XCircle size={20} />
                </div>
                <div className="min-w-0">
                  <p className="text-[10px] sm:text-xs font-extrabold text-rose-700 uppercase truncate">Ditolak</p>
                  <h4 className="text-lg sm:text-2xl font-black text-rose-700">{stats.rejected_requests || 0}</h4>
                </div>
              </CardContent>
            </Card>
          </div>
        </motion.div>

        {/* ========================================================================= */}
        {/* 5. HRD / ADMIN COMPANY-WIDE INSIGHT METRICS (IF ADMIN/SUPERADMIN)          */}
        {/* ========================================================================= */}
        {isAdmin && Object.keys(hrdMetrics).length > 0 && (
          <motion.div variants={itemVariants} className="space-y-2.5">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-2">
                <Badge variant="purple" className="font-extrabold">HRD Analytics</Badge>
                <h3 className="text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                  Ringkasan Karyawan & Cuti Perusahaan
                </h3>
              </div>
              <Link href={route('hrd.index')} className="text-xs font-bold text-purple-700 hover:underline">
                Buka Rekap HRD &rarr;
              </Link>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4">
              <Card className="p-3.5 sm:p-4 border-slate-200">
                <div className="flex items-center space-x-3">
                  <div className="p-2.5 rounded-xl bg-purple-50 text-purple-700">
                    <Users size={18} />
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 font-bold uppercase block">Total Karyawan</span>
                    <span className="text-base sm:text-xl font-black text-slate-900">{hrdMetrics.total_employees || 0}</span>
                  </div>
                </div>
              </Card>

              <Card className="p-3.5 sm:p-4 border-slate-200">
                <div className="flex items-center space-x-3">
                  <div className="p-2.5 rounded-xl bg-blue-50 text-blue-700">
                    <Building size={18} />
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 font-bold uppercase block">Departemen</span>
                    <span className="text-base sm:text-xl font-black text-slate-900">{hrdMetrics.total_departments || 0}</span>
                  </div>
                </div>
              </Card>

              <Card className="p-3.5 sm:p-4 border-slate-200">
                <div className="flex items-center space-x-3">
                  <div className="p-2.5 rounded-xl bg-emerald-50 text-emerald-700">
                    <CalendarDays size={18} />
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 font-bold uppercase block">Cuti Hari Ini</span>
                    <span className="text-base sm:text-xl font-black text-emerald-700">{hrdMetrics.on_leave_today || 0} Org</span>
                  </div>
                </div>
              </Card>

              <Card className="p-3.5 sm:p-4 border-slate-200">
                <div className="flex items-center space-x-3">
                  <div className="p-2.5 rounded-xl bg-amber-50 text-amber-700">
                    <Clock size={18} />
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 font-bold uppercase block">Pending All</span>
                    <span className="text-base sm:text-xl font-black text-amber-700">{hrdMetrics.pending_company_wide || 0}</span>
                  </div>
                </div>
              </Card>
            </div>
          </motion.div>
        )}

        {/* ========================================================================= */}
        {/* 6. RECENT LEAVE REQUESTS & QUICK NAVIGATION SHORTCUTS                     */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="grid grid-cols-1 lg:grid-cols-3 gap-5">
          {/* Recent Requests (Left 2 cols) */}
          <Card className="lg:col-span-2 border-slate-200 shadow-xs">
            <CardHeader className="p-4 sm:p-6 pb-3 flex flex-row items-center justify-between space-y-0">
              <div>
                <CardTitle className="text-base sm:text-lg">Riwayat Pengajuan Terakhir</CardTitle>
                <CardDescription className="text-xs mt-0.5">Daftar 5 pengajuan cuti dan izin terakhir Anda</CardDescription>
              </div>
              <Link href={route('leave-requests.index')}>
                <Button variant="ghost" size="sm" className="text-emerald-700 font-bold text-xs">
                  Semua
                </Button>
              </Link>
            </CardHeader>

            <Separator />

            <CardContent className="p-3 sm:p-6 pt-3 sm:pt-4 space-y-2.5">
              {recentRequests && recentRequests.length > 0 ? (
                recentRequests.map((req) => (
                  <Link
                    key={req.id}
                    href={route('leave-requests.index')}
                    className="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:bg-emerald-50/50 hover:border-emerald-300 flex items-center justify-between gap-3 transition-all group"
                  >
                    <div className="flex items-center space-x-3 min-w-0">
                      <div className="w-10 h-10 rounded-xl bg-white text-emerald-700 border border-slate-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                        <FileText size={18} />
                      </div>
                      <div className="min-w-0">
                        <div className="flex items-center space-x-2">
                          <span className="font-mono text-xs font-extrabold text-slate-900">{req.request_number}</span>
                        </div>
                        <p className="text-xs font-bold text-slate-700 truncate">{req.category?.name}</p>
                        <p className="text-[11px] text-slate-400 font-medium mt-0.5">
                          {req.start_date} s/d {req.end_date} &bull; <strong className="text-slate-700">{req.amount} {req.unit}</strong>
                        </p>
                      </div>
                    </div>

                    <div className="flex items-center space-x-2 shrink-0">
                      <Badge
                        variant={
                          req.status === 'approved' ? 'success' :
                          req.status === 'rejected' ? 'destructive' :
                          'warning'
                        }
                        className="font-extrabold"
                      >
                        {req.status === 'approved' ? 'Disetujui' : req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                      </Badge>
                      <ChevronRight size={16} className="text-slate-400 group-hover:text-emerald-700 transition-colors" />
                    </div>
                  </Link>
                ))
              ) : (
                <div className="py-10 text-center text-slate-400 space-y-2">
                  <Calendar size={36} className="mx-auto opacity-30 text-slate-400" />
                  <p className="text-xs font-semibold">Belum ada riwayat pengajuan cuti.</p>
                  <Link href={route('leave-requests.create')}>
                    <Button variant="outline" size="sm" className="mt-2 text-emerald-700 border-emerald-300">
                      + Buat Pengajuan Pertama
                    </Button>
                  </Link>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Quick Shortcuts & Navigation Panel (Right 1 col) */}
          <Card className="border-slate-200 shadow-xs flex flex-col justify-between">
            <CardHeader className="p-4 sm:p-6 pb-3">
              <CardTitle className="text-base sm:text-lg">Akses Cepat Fitur</CardTitle>
              <CardDescription className="text-xs mt-0.5">Pintasan menu utama Form SGIN</CardDescription>
            </CardHeader>

            <Separator />

            <CardContent className="p-4 sm:p-6 pt-4 space-y-2">
              <Link
                href={route('leave-requests.create')}
                className="w-full p-3 rounded-2xl border border-slate-200 bg-slate-50/60 hover:bg-emerald-50 hover:border-emerald-300 flex items-center justify-between transition-all group"
              >
                <div className="flex items-center space-x-3">
                  <div className="p-2 rounded-xl bg-emerald-100 text-emerald-700">
                    <FilePlus size={16} />
                  </div>
                  <div className="text-left">
                    <h4 className="text-xs font-bold text-slate-900 group-hover:text-emerald-900">Form Pengajuan</h4>
                    <p className="text-[10px] text-slate-500">Ajukan cuti/izin/sakit</p>
                  </div>
                </div>
                <ArrowUpRight size={16} className="text-slate-400 group-hover:text-emerald-600" />
              </Link>

              <Link
                href={route('leave-requests.index')}
                className="w-full p-3 rounded-2xl border border-slate-200 bg-slate-50/60 hover:bg-slate-100 flex items-center justify-between transition-all group"
              >
                <div className="flex items-center space-x-3">
                  <div className="p-2 rounded-xl bg-teal-100 text-teal-700">
                    <Calendar size={16} />
                  </div>
                  <div className="text-left">
                    <h4 className="text-xs font-bold text-slate-900">Riwayat Cuti</h4>
                    <p className="text-[10px] text-slate-500">Pantau status persetujuan</p>
                  </div>
                </div>
                <ArrowUpRight size={16} className="text-slate-400" />
              </Link>

              {(isManager || isAdmin) && (
                <Link
                  href={route('approvals.index')}
                  className="w-full p-3 rounded-2xl border border-blue-200 bg-blue-50/40 hover:bg-blue-50 flex items-center justify-between transition-all group"
                >
                  <div className="flex items-center space-x-3">
                    <div className="p-2 rounded-xl bg-blue-100 text-blue-700">
                      <CheckSquare size={16} />
                    </div>
                    <div className="text-left">
                      <h4 className="text-xs font-bold text-blue-950">Panel Approval</h4>
                      <p className="text-[10px] text-blue-700">Persetujuan cuti bawahan</p>
                    </div>
                  </div>
                  <ArrowUpRight size={16} className="text-blue-500" />
                </Link>
              )}

              {isAdmin && (
                <>
                  <Link
                    href={route('hrd.departments')}
                    className="w-full p-3 rounded-2xl border border-teal-200 bg-teal-50/40 hover:bg-teal-50 flex items-center justify-between transition-all group"
                  >
                    <div className="flex items-center space-x-3">
                      <div className="p-2 rounded-xl bg-teal-100 text-teal-700">
                        <Building size={16} />
                      </div>
                      <div className="text-left">
                        <h4 className="text-xs font-bold text-teal-950">Setup Departemen</h4>
                        <p className="text-[10px] text-teal-700">Atur alur approval per divisi</p>
                      </div>
                    </div>
                    <ArrowUpRight size={16} className="text-teal-500" />
                  </Link>

                  <Link
                    href={route('hrd.employees')}
                    className="w-full p-3 rounded-2xl border border-purple-200 bg-purple-50/40 hover:bg-purple-50 flex items-center justify-between transition-all group"
                  >
                    <div className="flex items-center space-x-3">
                      <div className="p-2 rounded-xl bg-purple-100 text-purple-700">
                        <Users size={16} />
                      </div>
                      <div className="text-left">
                        <h4 className="text-xs font-bold text-purple-950">Kelola Karyawan</h4>
                        <p className="text-[10px] text-purple-700">Database & kuota cuti</p>
                      </div>
                    </div>
                    <ArrowUpRight size={16} className="text-purple-500" />
                  </Link>

                  <Link
                    href={route('hrd.index')}
                    className="w-full p-3 rounded-2xl border border-purple-200 bg-purple-50/40 hover:bg-purple-50 flex items-center justify-between transition-all group"
                  >
                    <div className="flex items-center space-x-3">
                      <div className="p-2 rounded-xl bg-purple-100 text-purple-700">
                        <FileSpreadsheet size={16} />
                      </div>
                      <div className="text-left">
                        <h4 className="text-xs font-bold text-purple-950">Rekapitulasi Cuti</h4>
                        <p className="text-[10px] text-purple-700">Laporan & export Excel</p>
                      </div>
                    </div>
                    <ArrowUpRight size={16} className="text-purple-500" />
                  </Link>
                </>
              )}
            </CardContent>
          </Card>
        </motion.div>
      </motion.div>
    </AuthenticatedLayout>
  );
}
