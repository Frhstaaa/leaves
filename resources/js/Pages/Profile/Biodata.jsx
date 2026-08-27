import React, { useState } from 'react';
import { Head, useForm, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout, { UserAvatar } from '@/Layouts/AuthenticatedLayout';
import {
  User,
  Briefcase,
  ShieldCheck,
  Users,
  CreditCard,
  Building2,
  FileText,
  Printer,
  CheckCircle2,
  AlertCircle,
  Save,
  Phone,
  MapPin,
  Calendar,
  Heart,
  Truck,
  Sparkles,
  Info,
  ChevronRight,
  ArrowLeft,
  Copy,
  GraduationCap,
  Activity,
  HeartHandshake
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { showToast, showAlert } from '@/Utils/swal';

export default function Biodata({ user = {}, departments = [], isHrdView = false }) {
  const { auth } = usePage().props;
  const isSuperadmin = auth?.user?.is_superadmin || auth?.user?.role === 'superadmin';
  const isAdmin = auth?.user?.is_admin || auth?.user?.role === 'admin' || isSuperadmin;

  const [activeTab, setActiveTab] = useState('pekerjaan'); // 'pekerjaan' | 'pribadi' | 'keuangan' | 'keluarga'

  // Initialize Form Data
  const form = useForm({
    // 1. Data Pekerjaan
    join_date: user.join_date ? (typeof user.join_date === 'string' ? user.join_date.split('T')[0] : user.join_date) : '',
    employee_status: user.employee_status || '',
    education: user.education || '',
    position: user.position || (user.role && user.role !== 'employee' ? user.role : ''),
    contract_end_date: user.contract_end_date ? (typeof user.contract_end_date === 'string' ? user.contract_end_date.split('T')[0] : user.contract_end_date) : '',
    department_id: user.department_id || '',

    // 2. Data Pribadi & Kependudukan
    ktp_number: user.ktp_number || '',
    gender: user.gender || '',
    birth_place: user.birth_place || '',
    birth_date: user.birth_date ? (typeof user.birth_date === 'string' ? user.birth_date.split('T')[0] : user.birth_date) : '',
    phone_number: user.phone_number || '',
    ktp_address: user.ktp_address || '',
    domicile_address: user.domicile_address || '',
    marital_status: user.marital_status || '',
    mother_maiden_name: user.mother_maiden_name || '',
    kk_number: user.kk_number || '',
    blood_type: user.blood_type || '',

    // 3. Keuangan, BPJS & Logistik
    npwp: user.npwp || '',
    bpjs_kesehatan_number: user.bpjs_kesehatan_number || '',
    bpjs_health_facility: user.bpjs_health_facility || '',
    bpjs_ketenagakerjaan_number: user.bpjs_ketenagakerjaan_number || '',
    bank_name: user.bank_name || '',
    bank_account_number: user.bank_account_number || '',
    vehicle_plate_number: user.vehicle_plate_number || '',
    sim_number: user.sim_number || '',
    sim_valid_until: user.sim_valid_until ? (typeof user.sim_valid_until === 'string' ? user.sim_valid_until.split('T')[0] : user.sim_valid_until) : '',
    shoe_size: user.shoe_size || '',

    // 4. Keluarga & Kontak Darurat
    emergency_contact_name: user.emergency_contact_name || '',
    emergency_contact_relationship: user.emergency_contact_relationship || '',
    emergency_contact_phone: user.emergency_contact_phone || '',
    emergency_contact_address: user.emergency_contact_address || '',
    spouse_name: user.spouse_name || '',
    spouse_ktp_number: user.spouse_ktp_number || '',
    spouse_birth_place: user.spouse_birth_place || '',
    spouse_birth_date: user.spouse_birth_date ? (typeof user.spouse_birth_date === 'string' ? user.spouse_birth_date.split('T')[0] : user.spouse_birth_date) : '',
    child_1_name: user.child_1_name || '',
    child_2_name: user.child_2_name || '',
    child_3_name: user.child_3_name || '',
  });

  // Calculate live completeness locally using the EXACT same 23 fields as User model
  const completenessFieldValues = [
    user.name,
    user.nik,
    user.email,
    user.department_id || form.data.department_id,
    form.data.join_date,
    form.data.employee_status,
    form.data.education,
    form.data.position,
    form.data.ktp_number,
    form.data.gender,
    form.data.birth_place,
    form.data.birth_date,
    form.data.phone_number,
    form.data.ktp_address,
    form.data.domicile_address,
    form.data.marital_status,
    form.data.mother_maiden_name,
    form.data.kk_number,
    form.data.blood_type,
    form.data.emergency_contact_name,
    form.data.emergency_contact_relationship,
    form.data.emergency_contact_phone,
    form.data.bank_account_number,
  ];

  const filledCount = completenessFieldValues.filter(val => val !== null && val !== undefined && String(val).trim() !== '').length;
  const liveCompletenessPercent = Math.min(100, Math.round((filledCount / completenessFieldValues.length) * 100));

  const copyKtpAddress = () => {
    form.setData('domicile_address', form.data.ktp_address);
    showToast('Alamat domisili disamakan dengan alamat KTP');
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    const targetUrl = isHrdView
      ? route('hrd.employees.biodata.update', user.id)
      : route('profile.biodata.update');

    form.post(targetUrl, {
      preserveScroll: true,
      onSuccess: () => {
        showToast('Data diri berhasil disimpan ke sistem PT SUGIYAMA INDONESIA!');
      },
      onError: (errors) => {
        console.error('Biodata submit errors:', errors);
        showAlert({
          title: 'Periksa Isian Formulir',
          text: 'Terdapat beberapa data yang belum sesuai format. Silakan periksa pesan kesalahan pada formulir.',
          icon: 'error'
        });
      }
    });
  };

  const tabs = [
    { id: 'pekerjaan', label: '1. Data Pekerjaan', shortLabel: 'Pekerjaan', icon: Briefcase, color: 'text-blue-600', activeBg: 'bg-blue-50 text-blue-700 border-blue-200' },
    { id: 'pribadi', label: '2. Identitas Pribadi', shortLabel: 'Identitas', icon: User, color: 'text-emerald-600', activeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    { id: 'keuangan', label: '3. BPJS & Operasional', shortLabel: 'BPJS & Keuangan', icon: ShieldCheck, color: 'text-teal-600', activeBg: 'bg-teal-50 text-teal-700 border-teal-200' },
    { id: 'keluarga', label: '4. Keluarga & Darurat', shortLabel: 'Keluarga', icon: Users, color: 'text-purple-600', activeBg: 'bg-purple-50 text-purple-700 border-purple-200' },
  ];

  return (
    <AuthenticatedLayout title={isHrdView ? `Data Diri: ${user.name}` : 'Form Data Diri Saya'}>
      <Head title={`Form Data Diri - ${user.name || 'PT SUGIYAMA INDONESIA'}`} />

      <div className="max-w-6xl mx-auto space-y-4 sm:space-y-6 pb-28 sm:pb-12">
        {/* Breadcrumb Navigation for HRD View */}
        {isHrdView && (
          <div className="flex items-center space-x-2 text-xs font-semibold text-slate-500 bg-white p-3 rounded-2xl border border-slate-200 shadow-xs">
            <Link href={route('hrd.employees')} className="hover:text-emerald-600 flex items-center space-x-1">
              <ArrowLeft size={14} />
              <span>Kembali ke Manajemen Karyawan</span>
            </Link>
            <ChevronRight size={12} />
            <span className="text-slate-900 font-bold">{user.name} ({user.nik})</span>
          </div>
        )}

        {/* ========================================================================= */}
        {/* HERO PROFILE HEADER CARD (CLEAN SGIN WHITE & EMERALD THEME)               */}
        {/* ========================================================================= */}
        <div className="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-white border border-slate-200/90 shadow-xs p-4 sm:p-6 md:p-7">
          {/* Top Decorative Green Accent Bar */}
          <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-600" />

          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-6 pt-1">
            {/* User Avatar & Info */}
            <div className="flex items-center space-x-3.5 sm:space-x-5 min-w-0">
              <UserAvatar user={user} size="w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20" textSize="text-xl sm:text-2xl" />
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-1.5 sm:gap-2">
                  <span className="px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200">
                    {user.employee_status || 'Karyawan Tetap'}
                  </span>
                  <span className="text-[11px] sm:text-xs font-mono font-bold text-emerald-800 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200">
                    NIK: {user.nik || 'EMP-201'}
                  </span>
                </div>
                <h1 className="text-lg sm:text-xl md:text-2xl font-black text-slate-900 tracking-tight mt-1 truncate">
                  {user.name}
                </h1>
                <p className="text-xs sm:text-sm text-slate-500 font-medium truncate mt-0.5">
                  <span className="text-slate-800 font-semibold">{user.department?.name || 'PT SUGIYAMA INDONESIA'}</span>
                  <span className="mx-1.5 text-slate-300">•</span>
                  <span>{user.position || user.role}</span>
                </p>
              </div>
            </div>

            {/* Completeness Status & Print Action */}
            <div className="flex flex-wrap sm:flex-nowrap items-center gap-2.5 sm:gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
              {/* Completeness Card */}
              <div className="flex-1 sm:flex-initial p-3 sm:p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 flex items-center space-x-3 min-w-[180px]">
                <div className={`w-10 h-10 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center font-black text-xs sm:text-sm shrink-0 shadow-xs ${
                  liveCompletenessPercent >= 80 ? 'bg-emerald-600 text-white shadow-emerald-600/20' : 'bg-amber-500 text-white shadow-amber-500/20'
                }`}>
                  {liveCompletenessPercent}%
                </div>
                <div className="min-w-0">
                  <div className="text-[10px] uppercase tracking-wider font-extrabold text-slate-400 truncate">
                    Kelengkapan Data
                  </div>
                  <div className="text-xs font-bold text-slate-800 flex items-center space-x-1 mt-0.5 truncate">
                    {liveCompletenessPercent >= 80 ? (
                      <>
                        <CheckCircle2 size={13} className="text-emerald-600 shrink-0" />
                        <span className="text-emerald-700">Data Lengkap</span>
                      </>
                    ) : (
                      <>
                        <AlertCircle size={13} className="text-amber-600 shrink-0" />
                        <span className="text-amber-700">Perlu Dilengkapi</span>
                      </>
                    )}
                  </div>
                </div>
              </div>

              {/* Print Document Button */}
              <a
                href={isHrdView ? route('hrd.employees.biodata.print', user.id) : route('profile.biodata.print')}
                target="_blank"
                rel="noopener noreferrer"
                className="w-full sm:w-auto px-4 py-3 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 font-bold text-xs flex items-center justify-center space-x-2 border border-slate-200 shadow-xs transition-all active:scale-[0.98] shrink-0"
              >
                <Printer size={15} className="text-emerald-600" />
                <span>Cetak Form</span>
              </a>
            </div>
          </div>
        </div>

        {/* ========================================================================= */}
        {/* RESPONSIVE TAB NAVIGATION BAR (HORIZONTAL PILLS ON MOBILE)                */}
        {/* ========================================================================= */}
        <div className="bg-slate-100/90 p-1.5 rounded-2xl border border-slate-200/90">
          <div className="flex sm:grid sm:grid-cols-4 gap-1.5 overflow-x-auto no-scrollbar scroll-smooth">
            {tabs.map((tab) => {
              const Icon = tab.icon;
              const isActive = activeTab === tab.id;
              return (
                <button
                  key={tab.id}
                  type="button"
                  onClick={() => setActiveTab(tab.id)}
                  className={`py-2.5 sm:py-3 px-3.5 sm:px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center space-x-2 shrink-0 sm:shrink ${
                    isActive
                      ? 'bg-white text-slate-900 shadow-xs ring-1 ring-slate-200/90 font-extrabold'
                      : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
                  }`}
                >
                  <Icon size={16} className={isActive ? tab.color : 'text-slate-400'} />
                  <span className="truncate hidden sm:inline">{tab.label}</span>
                  <span className="truncate sm:hidden">{tab.shortLabel}</span>
                </button>
              );
            })}
          </div>
        </div>

        {/* ========================================================================= */}
        {/* MAIN FORM CONTENT WITH ENHANCED MOBILE PROPORTIONS                        */}
        {/* ========================================================================= */}
        <form onSubmit={handleSubmit} className="space-y-4 sm:space-y-6">
          {/* TAB 1: DATA PEKERJAAN */}
          {activeTab === 'pekerjaan' && (
            <motion.div
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              className="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-slate-200/90 shadow-xs space-y-5 sm:space-y-6"
            >
              <div className="flex items-center space-x-3 border-b border-slate-100 pb-3.5 sm:pb-4">
                <div className="p-2 sm:p-2.5 rounded-xl bg-blue-50 text-blue-600 shrink-0">
                  <Briefcase size={20} />
                </div>
                <div>
                  <h3 className="text-sm sm:text-base font-extrabold text-slate-900">1. Data Pokok Pekerjaan</h3>
                  <p className="text-[11px] sm:text-xs text-slate-500">Informasi penempatan, jabatan, dan status kepegawaian PT Sugiyama Indonesia</p>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-5 text-xs">
                {/* Nama Lengkap */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Nama Lengkap Karyawan</label>
                  <input
                    type="text"
                    value={user.name}
                    disabled
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-100/90 border border-slate-200 text-slate-700 font-semibold cursor-not-allowed text-xs sm:text-sm"
                  />
                  <span className="text-[10px] text-slate-400 block">Sesuai nama terdaftar pada sistem HRD</span>
                </div>

                {/* NIK SGIN */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">NIK SGIN</label>
                  <input
                    type="text"
                    value={user.nik || 'EMP-201'}
                    disabled
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-100/90 border border-slate-200 text-emerald-800 font-mono font-bold cursor-not-allowed text-xs sm:text-sm"
                  />
                  <span className="text-[10px] text-slate-400 block">Nomor Induk Karyawan PT Sugiyama Indonesia</span>
                </div>

                {/* Email Terdaftar */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Email Akun</label>
                  <input
                    type="email"
                    value={user.email}
                    disabled
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-100/90 border border-slate-200 text-slate-700 font-semibold cursor-not-allowed text-xs sm:text-sm"
                  />
                  <span className="text-[10px] text-slate-400 block">Email login & notifikasi sistem</span>
                </div>

                {/* Departemen / Bagian */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Departemen / Bagian</label>
                  {isHrdView ? (
                    <select
                      value={form.data.department_id}
                      onChange={(e) => form.setData('department_id', e.target.value)}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm"
                    >
                      <option value="">-- Pilih Departemen --</option>
                      {departments.map((d) => (
                        <option key={d.id} value={d.id}>{d.name} ({d.code})</option>
                      ))}
                    </select>
                  ) : (
                    <input
                      type="text"
                      value={user.department?.name || 'General'}
                      disabled
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-100/90 border border-slate-200 text-slate-700 font-semibold cursor-not-allowed text-xs sm:text-sm"
                    />
                  )}
                </div>

                {/* Jabatan */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Jabatan</label>
                  <input
                    type="text"
                    value={form.data.position}
                    onChange={(e) => form.setData('position', e.target.value)}
                    disabled={!isHrdView && Boolean(user.position)}
                    placeholder="Contoh: Staff IT, Operator, SPV"
                    className={`w-full px-3.5 py-2.5 rounded-xl ${!isHrdView && Boolean(user.position) ? 'bg-slate-100/90 cursor-not-allowed' : 'bg-slate-50 focus:bg-white'} border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all`}
                  />
                </div>

                {/* Status Karyawan */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Status Karyawan</label>
                  <select
                    value={form.data.employee_status}
                    onChange={(e) => form.setData('employee_status', e.target.value)}
                    disabled={!isHrdView && Boolean(user.employee_status)}
                    className={`w-full px-3.5 py-2.5 rounded-xl ${!isHrdView && Boolean(user.employee_status) ? 'bg-slate-100/90 cursor-not-allowed' : 'bg-slate-50 focus:bg-white'} border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all`}
                  >
                    <option value="">-- Pilih Status Karyawan --</option>
                    <option value="Tetap">Karyawan Tetap (PKWTT)</option>
                    <option value="Kontrak">Karyawan Kontrak (PKWT)</option>
                    <option value="Magang">Magang / Internship</option>
                    <option value="Percobaan">Masa Percobaan (Probation)</option>
                  </select>
                </div>

                {/* Pendidikan Terakhir */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Pendidikan Terakhir</label>
                  <select
                    value={form.data.education}
                    onChange={(e) => form.setData('education', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                  >
                    <option value="">-- Pilih Jenjang Pendidikan --</option>
                    <option value="SMA / SMK">SMA / SMK Sederajat</option>
                    <option value="Diploma 1 (D1)">Diploma 1 (D1)</option>
                    <option value="Diploma 2 (D2)">Diploma 2 (D2)</option>
                    <option value="Diploma 3 (D3)">Diploma 3 (D3)</option>
                    <option value="Sarjana (S1)">Sarjana (S1)</option>
                    <option value="Magister (S2)">Magister (S2)</option>
                    <option value="SMP">SMP Sederajat</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>

                {/* Tanggal Bergabung */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Tanggal Bergabung (Join Date)</label>
                  <input
                    type="date"
                    value={form.data.join_date}
                    onChange={(e) => form.setData('join_date', e.target.value)}
                    disabled={!isHrdView && Boolean(user.join_date)}
                    className={`w-full px-3.5 py-2.5 rounded-xl ${!isHrdView && Boolean(user.join_date) ? 'bg-slate-100/90 cursor-not-allowed' : 'bg-slate-50 focus:bg-white'} border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all`}
                  />
                </div>

                {/* Aktif Bekerja Sampai (Masa Kontrak) */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Aktif Bekerja Sampai</label>
                  <input
                    type="date"
                    value={form.data.contract_end_date}
                    onChange={(e) => form.setData('contract_end_date', e.target.value)}
                    disabled={!isHrdView}
                    className={`w-full px-3.5 py-2.5 rounded-xl ${!isHrdView ? 'bg-slate-100/90 cursor-not-allowed' : 'bg-slate-50 focus:bg-white'} border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all`}
                  />
                  <span className="text-[10px] text-slate-400 block">Khusus karyawan kontrak / PKWT</span>
                </div>
              </div>
            </motion.div>
          )}

          {/* TAB 2: IDENTITAS PRIBADI & KEPENDUDUKAN */}
          {activeTab === 'pribadi' && (
            <motion.div
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              className="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-slate-200/90 shadow-xs space-y-5 sm:space-y-6"
            >
              <div className="flex items-center space-x-3 border-b border-slate-100 pb-3.5 sm:pb-4">
                <div className="p-2 sm:p-2.5 rounded-xl bg-emerald-50 text-emerald-600 shrink-0">
                  <User size={20} />
                </div>
                <div>
                  <h3 className="text-sm sm:text-base font-extrabold text-slate-900">2. Identitas Pribadi & Kependudukan</h3>
                  <p className="text-[11px] sm:text-xs text-slate-500">Data kependudukan resmi KTP, KK, dan kontak karyawan</p>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-5 text-xs">
                {/* NIK KTP */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">
                    NIK KTP (16 Digit) <span className="text-rose-600">*</span>
                  </label>
                  <input
                    type="text"
                    maxLength={16}
                    value={form.data.ktp_number}
                    onChange={(e) => form.setData('ktp_number', e.target.value.replace(/\D/g, ''))}
                    placeholder="Contoh: 3201012345670001"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 font-mono text-slate-900 font-bold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    required
                  />
                  {form.errors.ktp_number && <p className="text-[11px] text-rose-600 font-bold">{form.errors.ktp_number}</p>}
                </div>

                {/* No KK */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">
                    Nomor Kartu Keluarga (No KK)
                  </label>
                  <input
                    type="text"
                    maxLength={16}
                    value={form.data.kk_number}
                    onChange={(e) => form.setData('kk_number', e.target.value.replace(/\D/g, ''))}
                    placeholder="16 Digit Nomor KK"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 font-mono text-slate-900 font-bold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                  />
                </div>

                {/* Jenis Kelamin */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Jenis Kelamin <span className="text-rose-600">*</span></label>
                  <select
                    value={form.data.gender}
                    onChange={(e) => form.setData('gender', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                  >
                    <option value="">-- Pilih Jenis Kelamin --</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                  </select>
                </div>

                {/* Tempat Lahir */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Tempat Lahir</label>
                  <input
                    type="text"
                    value={form.data.birth_place}
                    onChange={(e) => form.setData('birth_place', e.target.value)}
                    placeholder="Kota / Kabupaten Lahir"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                  />
                </div>

                {/* Tanggal Lahir */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Tanggal Lahir</label>
                  <input
                    type="date"
                    value={form.data.birth_date}
                    onChange={(e) => form.setData('birth_date', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                  />
                </div>

                {/* Golongan Darah */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Golongan Darah</label>
                  <select
                    value={form.data.blood_type}
                    onChange={(e) => form.setData('blood_type', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-bold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                  >
                    <option value="">-- Pilih Golongan Darah --</option>
                    <option value="A">Golongan Darah A</option>
                    <option value="B">Golongan Darah B</option>
                    <option value="AB">Golongan Darah AB</option>
                    <option value="O">Golongan Darah O</option>
                    <option value="-">-</option>
                  </select>
                </div>

                {/* Nama Gadis Ibu Kandung */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Nama Gadis Ibu Kandung</label>
                  <input
                    type="text"
                    value={form.data.mother_maiden_name}
                    onChange={(e) => form.setData('mother_maiden_name', e.target.value)}
                    placeholder="Nama lengkap ibu kandung"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                  />
                </div>

                {/* Nomor HP / WhatsApp */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Nomor HP / WhatsApp Aktif</label>
                  <input
                    type="tel"
                    value={form.data.phone_number}
                    onChange={(e) => form.setData('phone_number', e.target.value)}
                    placeholder="Contoh: 081234567890"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                  />
                </div>

                {/* Status Perkawinan */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Status Perkawinan</label>
                  <select
                    value={form.data.marital_status}
                    onChange={(e) => form.setData('marital_status', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                  >
                    <option value="">-- Pilih Status Perkawinan --</option>
                    <option value="Belum Menikah">Belum Menikah (Lajang)</option>
                    <option value="Menikah">Menikah (Kawin)</option>
                    <option value="Cerai Hidup">Cerai Hidup</option>
                    <option value="Cerai Mati">Cerai Mati</option>
                  </select>
                </div>
              </div>

              {/* Alamat KTP & Domisili */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 pt-3 border-t border-slate-100 text-xs">
                {/* Alamat KTP */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700 flex items-center justify-between">
                    <span>Alamat Lengkap Sesuai KTP</span>
                    <span className="text-[10px] text-slate-400 font-normal">RT/RW, Desa/Kel, Kec, Kab/Kota</span>
                  </label>
                  <textarea
                    rows={3}
                    value={form.data.ktp_address}
                    onChange={(e) => form.setData('ktp_address', e.target.value)}
                    placeholder="Tuliskan alamat lengkap beserta RT, RW, Kelurahan, Kecamatan, Kabupaten/Kota..."
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-medium focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all resize-none"
                  />
                </div>

                {/* Alamat Domisili */}
                <div className="space-y-1.5">
                  <div className="flex items-center justify-between">
                    <label className="font-bold text-slate-700">Alamat Domisili Saat Ini</label>
                    <button
                      type="button"
                      onClick={copyKtpAddress}
                      className="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[10px] flex items-center space-x-1 border border-emerald-200 transition-colors"
                    >
                      <Copy size={11} />
                      <span>Sama dengan KTP</span>
                    </button>
                  </div>
                  <textarea
                    rows={3}
                    value={form.data.domicile_address}
                    onChange={(e) => form.setData('domicile_address', e.target.value)}
                    placeholder="Tuliskan tempat tinggal sekarang jika berbeda dengan KTP..."
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-medium focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all resize-none"
                  />
                </div>
              </div>
            </motion.div>
          )}

          {/* TAB 3: BPJS, KEUANGAN & OPERASIONAL */}
          {activeTab === 'keuangan' && (
            <motion.div
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              className="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-slate-200/90 shadow-xs space-y-5 sm:space-y-6"
            >
              <div className="flex items-center space-x-3 border-b border-slate-100 pb-3.5 sm:pb-4">
                <div className="p-2 sm:p-2.5 rounded-xl bg-teal-50 text-teal-600 shrink-0">
                  <ShieldCheck size={20} />
                </div>
                <div>
                  <h3 className="text-sm sm:text-base font-extrabold text-slate-900">3. BPJS, Keuangan & Perlengkapan Kerja</h3>
                  <p className="text-[11px] sm:text-xs text-slate-500">Nomor jaminan sosial, rekening penggajian, dan fasilitas operasional</p>
                </div>
              </div>

              {/* SECTION: JAMINAN SOSIAL & PAJAK */}
              <div className="space-y-3">
                <span className="text-xs font-black uppercase tracking-wider text-slate-400 block">Jaminan Sosial & Pajak</span>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5 text-xs">
                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">No BPJS Kesehatan</label>
                    <input
                      type="text"
                      value={form.data.bpjs_kesehatan_number}
                      onChange={(e) => form.setData('bpjs_kesehatan_number', e.target.value)}
                      placeholder="Nomor kartu BPJS Kesehatan"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 font-mono text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Faskes BPJS Kes (Tingkat 1)</label>
                    <input
                      type="text"
                      value={form.data.bpjs_health_facility}
                      onChange={(e) => form.setData('bpjs_health_facility', e.target.value)}
                      placeholder="Contoh: Klinik SGIN, Puskesmas Klari"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">No BPJS TKU / Ketenagakerjaan</label>
                    <input
                      type="text"
                      value={form.data.bpjs_ketenagakerjaan_number}
                      onChange={(e) => form.setData('bpjs_ketenagakerjaan_number', e.target.value)}
                      placeholder="Nomor kartu BPJS TKU (KPJ)"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 font-mono text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nomor NPWP</label>
                    <input
                      type="text"
                      value={form.data.npwp}
                      onChange={(e) => form.setData('npwp', e.target.value)}
                      placeholder="15 / 16 Digit NPWP"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 font-mono text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>
                </div>
              </div>

              {/* SECTION: REKENING PENGGAJIAN */}
              <div className="space-y-3 pt-3 border-t border-slate-100">
                <span className="text-xs font-black uppercase tracking-wider text-slate-400 block">Rekening Bank Penggajian</span>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-5 text-xs">
                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Bank</label>
                    <select
                      value={form.data.bank_name}
                      onChange={(e) => form.setData('bank_name', e.target.value)}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-bold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    >
                      <option value="BCA">Bank Central Asia (BCA)</option>
                      <option value="Mandiri">Bank Mandiri</option>
                      <option value="BNI">Bank Negara Indonesia (BNI)</option>
                      <option value="BRI">Bank Rakyat Indonesia (BRI)</option>
                      <option value="BSI">Bank Syariah Indonesia (BSI)</option>
                      <option value="CIMB Niaga">Bank CIMB Niaga</option>
                      <option value="Permata">Bank Permata</option>
                      <option value="Lainnya">Bank Lainnya</option>
                    </select>
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nomor Rekening Bank</label>
                    <input
                      type="text"
                      value={form.data.bank_account_number}
                      onChange={(e) => form.setData('bank_account_number', e.target.value.replace(/\D/g, ''))}
                      placeholder="Nomor rekening atas nama karyawan"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 font-mono text-slate-900 font-bold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>
                </div>
              </div>

              {/* SECTION: LOGISTIK & OPERASIONAL */}
              <div className="space-y-3 pt-3 border-t border-slate-100">
                <span className="text-xs font-black uppercase tracking-wider text-slate-400 block">Logistik & Perlengkapan Keselamatan</span>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5 text-xs">
                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">No Polisi Kendaraan</label>
                    <input
                      type="text"
                      value={form.data.vehicle_plate_number}
                      onChange={(e) => form.setData('vehicle_plate_number', e.target.value.toUpperCase())}
                      placeholder="Contoh: B 1234 SGIN"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 font-mono text-slate-900 font-bold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nomor SIM (A / C)</label>
                    <input
                      type="text"
                      value={form.data.sim_number}
                      onChange={(e) => form.setData('sim_number', e.target.value)}
                      placeholder="Nomor Surat Izin Mengemudi"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 font-mono text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Masa Berlaku SIM</label>
                    <input
                      type="date"
                      value={form.data.sim_valid_until}
                      onChange={(e) => form.setData('sim_valid_until', e.target.value)}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Ukuran Sepatu Safety</label>
                    <select
                      value={form.data.shoe_size}
                      onChange={(e) => form.setData('shoe_size', e.target.value)}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-bold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    >
                      {['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'].map((sz) => (
                        <option key={sz} value={sz}>Ukuran {sz}</option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>
            </motion.div>
          )}

          {/* TAB 4: KELUARGA & KONTAK DARURAT */}
          {activeTab === 'keluarga' && (
            <motion.div
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              className="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-slate-200/90 shadow-xs space-y-5 sm:space-y-6"
            >
              {/* SUB-SECTION 1: KONTAK DARURAT */}
              <div className="space-y-3.5 sm:space-y-4">
                <div className="flex items-center space-x-3 border-b border-slate-100 pb-3">
                  <div className="p-2 rounded-xl bg-rose-50 text-rose-600 shrink-0">
                    <Phone size={18} />
                  </div>
                  <div>
                    <h3 className="text-sm font-extrabold text-slate-900">Keluarga Yang Dapat Dihubungi (Kontak Darurat)</h3>
                    <p className="text-[11px] text-slate-500">Pihak keluarga yang dapat segera dihubungi saat keadaan darurat medis / kerja</p>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-5 text-xs">
                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Kontak Darurat</label>
                    <input
                      type="text"
                      value={form.data.emergency_contact_name}
                      onChange={(e) => form.setData('emergency_contact_name', e.target.value)}
                      placeholder="Nama orang tua, saudara, atau wali"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Hubungan Keluarga</label>
                    <select
                      value={form.data.emergency_contact_relationship}
                      onChange={(e) => form.setData('emergency_contact_relationship', e.target.value)}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    >
                      <option value="">-- Pilih Hubungan --</option>
                      <option value="Orang Tua (Ayah/Ibu)">Orang Tua (Ayah/Ibu)</option>
                      <option value="Suami / Istri">Suami / Istri</option>
                      <option value="Kakak / Adik Kandung">Kakak / Adik Kandung</option>
                      <option value="Paman / Bibi">Paman / Bibi</option>
                      <option value="Mertua">Mertua</option>
                      <option value="Lainnya">Lainnya</option>
                    </select>
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nomor Telepon Kontak Darurat</label>
                    <input
                      type="tel"
                      value={form.data.emergency_contact_phone}
                      onChange={(e) => form.setData('emergency_contact_phone', e.target.value)}
                      placeholder="Contoh: 081234567890"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5 sm:col-span-2 lg:col-span-3">
                    <label className="font-bold text-slate-700">Alamat Lengkap Kontak Darurat</label>
                    <input
                      type="text"
                      value={form.data.emergency_contact_address}
                      onChange={(e) => form.setData('emergency_contact_address', e.target.value)}
                      placeholder="Alamat domisili keluarga yang dapat dihubungi"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-medium focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>
                </div>
              </div>

              {/* SUB-SECTION 2: DATA PASANGAN */}
              <div className="space-y-3.5 sm:space-y-4 pt-4 border-t border-slate-100">
                <div className="flex items-center space-x-3 border-b border-slate-100 pb-3">
                  <div className="p-2 rounded-xl bg-purple-50 text-purple-600 shrink-0">
                    <Heart size={18} />
                  </div>
                  <div>
                    <h3 className="text-sm font-extrabold text-slate-900">Data Pasangan (Suami / Istri)</h3>
                    <p className="text-[11px] text-slate-500">Khusus bagi karyawan dengan status menikah</p>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5 text-xs">
                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Suami / Istri</label>
                    <input
                      type="text"
                      value={form.data.spouse_name}
                      onChange={(e) => form.setData('spouse_name', e.target.value)}
                      placeholder="Nama lengkap pasangan"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">NIK Suami / Istri (KTP)</label>
                    <input
                      type="text"
                      maxLength={16}
                      value={form.data.spouse_ktp_number}
                      onChange={(e) => form.setData('spouse_ktp_number', e.target.value.replace(/\D/g, ''))}
                      placeholder="16 Digit NIK KTP Pasangan"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 font-mono text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Tempat Lahir Pasangan</label>
                    <input
                      type="text"
                      value={form.data.spouse_birth_place}
                      onChange={(e) => form.setData('spouse_birth_place', e.target.value)}
                      placeholder="Kota lahir pasangan"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Tanggal Lahir Pasangan</label>
                    <input
                      type="date"
                      value={form.data.spouse_birth_date}
                      onChange={(e) => form.setData('spouse_birth_date', e.target.value)}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>
                </div>
              </div>

              {/* SUB-SECTION 3: DATA ANAK */}
              <div className="space-y-3.5 sm:space-y-4 pt-4 border-t border-slate-100">
                <div className="flex items-center space-x-3 border-b border-slate-100 pb-3">
                  <div className="p-2 rounded-xl bg-emerald-50 text-emerald-600 shrink-0">
                    <Users size={18} />
                  </div>
                  <div>
                    <h3 className="text-sm font-extrabold text-slate-900">Data Tanggungan Anak</h3>
                    <p className="text-[11px] text-slate-500">Nama anak kandung / tanggungan keluarga (Anak ke-1 s/d 3)</p>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-5 text-xs">
                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Anak ke-1</label>
                    <input
                      type="text"
                      value={form.data.child_1_name}
                      onChange={(e) => form.setData('child_1_name', e.target.value)}
                      placeholder="Nama anak pertama"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Anak ke-2</label>
                    <input
                      type="text"
                      value={form.data.child_2_name}
                      onChange={(e) => form.setData('child_2_name', e.target.value)}
                      placeholder="Nama anak kedua"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Anak ke-3</label>
                    <input
                      type="text"
                      value={form.data.child_3_name}
                      onChange={(e) => form.setData('child_3_name', e.target.value)}
                      placeholder="Nama anak ketiga"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-200/90 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none text-xs sm:text-sm transition-all"
                    />
                  </div>
                </div>
              </div>
            </motion.div>
          )}

          {/* ========================================================================= */}
          {/* STICKY BOTTOM SAVE ACTION BAR (SAFE MARGIN ON MOBILE)                     */}
          {/* ========================================================================= */}
          <div className="sticky bottom-3 sm:bottom-4 z-20 p-3 sm:p-4 rounded-2xl sm:rounded-3xl bg-white/95 backdrop-blur-md border border-slate-200/90 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">
            <div className="flex items-center space-x-2.5 text-xs text-slate-600 w-full sm:w-auto">
              <Info size={16} className="text-emerald-600 shrink-0" />
              <span className="hidden sm:inline">
                Pastikan data yang diisi telah sesuai dengan dokumen resmi kependudukan (KTP & KK).
              </span>
              <span className="sm:hidden font-bold text-slate-800">
                Kelengkapan Data: <strong className="text-emerald-700 font-mono">{liveCompletenessPercent}%</strong>
              </span>
            </div>

            <div className="flex items-center space-x-2.5 w-full sm:w-auto">
              <button
                type="submit"
                disabled={form.processing}
                className="w-full sm:w-auto px-6 py-3 rounded-xl sm:rounded-2xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-extrabold text-xs sm:text-sm shadow-md shadow-emerald-600/20 flex items-center justify-center space-x-2 transition-all disabled:opacity-50 cursor-pointer"
              >
                {form.processing ? (
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                ) : (
                  <>
                    <Save size={16} />
                    <span>Simpan Data Diri</span>
                  </>
                )}
              </button>
            </div>
          </div>
        </form>
      </div>
    </AuthenticatedLayout>
  );
}
