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
  Copy
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
    employee_status: user.employee_status || 'Tetap',
    education: user.education || '',
    position: user.position || user.role || '',
    contract_end_date: user.contract_end_date ? (typeof user.contract_end_date === 'string' ? user.contract_end_date.split('T')[0] : user.contract_end_date) : '',
    department_id: user.department_id || '',

    // 2. Data Pribadi & Kependudukan
    ktp_number: user.ktp_number || '',
    gender: user.gender || 'Laki-laki',
    birth_place: user.birth_place || '',
    birth_date: user.birth_date ? (typeof user.birth_date === 'string' ? user.birth_date.split('T')[0] : user.birth_date) : '',
    phone_number: user.phone_number || '',
    ktp_address: user.ktp_address || '',
    domicile_address: user.domicile_address || '',
    marital_status: user.marital_status || 'Belum Menikah',
    mother_maiden_name: user.mother_maiden_name || '',
    kk_number: user.kk_number || '',
    blood_type: user.blood_type || 'O',

    // 3. Keuangan, BPJS & Logistik
    npwp: user.npwp || '',
    bpjs_kesehatan_number: user.bpjs_kesehatan_number || '',
    bpjs_health_facility: user.bpjs_health_facility || '',
    bpjs_ketenagakerjaan_number: user.bpjs_ketenagakerjaan_number || '',
    bank_name: user.bank_name || 'BCA',
    bank_account_number: user.bank_account_number || '',
    vehicle_plate_number: user.vehicle_plate_number || '',
    sim_number: user.sim_number || '',
    sim_valid_until: user.sim_valid_until ? (typeof user.sim_valid_until === 'string' ? user.sim_valid_until.split('T')[0] : user.sim_valid_until) : '',
    shoe_size: user.shoe_size || '41',

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

  // Calculate live completeness locally
  const requiredKeys = [
    form.data.ktp_number, form.data.gender, form.data.birth_place, form.data.birth_date,
    form.data.phone_number, form.data.ktp_address, form.data.domicile_address,
    form.data.marital_status, form.data.mother_maiden_name, form.data.kk_number,
    form.data.blood_type, form.data.education, form.data.bank_account_number,
    form.data.emergency_contact_name, form.data.emergency_contact_phone
  ];
  const filledCount = requiredKeys.filter(val => val && String(val).trim() !== '').length;
  const liveCompletenessPercent = Math.min(100, Math.round(((filledCount + 4) / (requiredKeys.length + 4)) * 100));

  const copyKtpAddress = () => {
    form.setData('domicile_address', form.data.ktp_address);
    showToast('Alamat domisili disamakan dengan alamat KTP');
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    const targetUrl = isHrdView
      ? route('hrd.employees.biodata.update', user.id)
      : route('profile.biodata.update');

    form.put(targetUrl, {
      preserveScroll: true,
      onSuccess: () => {
        showToast('Data diri berhasil disimpan ke sistem PT SUGIYAMA INDONESIA!');
      },
      onError: (errs) => {
        showAlert({
          title: 'Periksa Isian Formulir',
          text: 'Terdapat beberapa data yang belum sesuai format. Silakan periksa pesan kesalahan pada formulir.',
          icon: 'error'
        });
      }
    });
  };

  const tabs = [
    { id: 'pekerjaan', label: '1. Data Pekerjaan', icon: Briefcase, color: 'text-blue-600', bg: 'bg-blue-50' },
    { id: 'pribadi', label: '2. Identitas Pribadi', icon: User, color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { id: 'keuangan', label: '3. BPJS & Operasional', icon: ShieldCheck, color: 'text-amber-600', bg: 'bg-amber-50' },
    { id: 'keluarga', label: '4. Keluarga & Darurat', icon: Users, color: 'text-purple-600', bg: 'bg-purple-50' },
  ];

  return (
    <AuthenticatedLayout title={isHrdView ? `Data Diri: ${user.name}` : 'Form Data Diri Saya'}>
      <Head title={`Form Data Diri - ${user.name || 'PT SUGIYAMA INDONESIA'}`} />

      <div className="max-w-6xl mx-auto space-y-6 pb-12">
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

        {/* Hero Header Card */}
        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-emerald-500/20">
          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div className="flex items-center space-x-4">
              <UserAvatar user={user} size="w-16 h-16 sm:w-20 sm:h-20" textSize="text-2xl" />
              <div>
                <div className="flex items-center space-x-2">
                  <span className="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    {user.employee_status || 'Karyawan SGIN'}
                  </span>
                  <span className="text-xs text-slate-400 font-mono">NIK: {user.nik || 'EMP-201'}</span>
                </div>
                <h1 className="text-xl sm:text-2xl font-black text-white tracking-tight mt-1">{user.name}</h1>
                <p className="text-xs text-slate-300 flex items-center space-x-2 mt-0.5">
                  <span>{user.department?.name || 'PT SUGIYAMA INDONESIA'}</span>
                  <span>•</span>
                  <span>{user.position || user.role}</span>
                </p>
              </div>
            </div>

            {/* Completeness Gauge & Print Action */}
            <div className="flex flex-wrap items-center gap-3">
              {/* Completeness Card */}
              <div className="p-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10 flex items-center space-x-3.5 shrink-0">
                <div className="relative flex items-center justify-center">
                  <div className={`w-12 h-12 rounded-full flex items-center justify-center font-black text-sm ${
                    liveCompletenessPercent >= 80 ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-slate-950'
                  }`}>
                    {liveCompletenessPercent}%
                  </div>
                </div>
                <div>
                  <div className="text-[10px] uppercase tracking-wider font-extrabold text-slate-300">
                    Kelengkapan Data
                  </div>
                  <div className="text-xs font-bold text-white flex items-center space-x-1 mt-0.5">
                    {liveCompletenessPercent >= 80 ? (
                      <>
                        <CheckCircle2 size={13} className="text-emerald-400" />
                        <span>Data Lengkap</span>
                      </>
                    ) : (
                      <>
                        <AlertCircle size={13} className="text-amber-400" />
                        <span>Perlu Dilengkapi</span>
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
                className="px-4 py-3 rounded-2xl bg-white/15 hover:bg-white/25 text-white font-bold text-xs flex items-center space-x-2 border border-white/20 transition-all active:scale-[0.98] shrink-0"
              >
                <Printer size={16} />
                <span>Cetak Form Data Diri</span>
              </a>
            </div>
          </div>
        </div>

        {/* Tab Navigation Pill Bar */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-2 bg-slate-100 p-1.5 rounded-2xl border border-slate-200/80">
          {tabs.map((tab) => {
            const Icon = tab.icon;
            const isActive = activeTab === tab.id;
            return (
              <button
                key={tab.id}
                type="button"
                onClick={() => setActiveTab(tab.id)}
                className={`py-3 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center space-x-2 ${
                  isActive
                    ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200 font-extrabold'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'
                }`}
              >
                <Icon size={16} className={isActive ? tab.color : 'text-slate-400'} />
                <span className="truncate">{tab.label}</span>
              </button>
            );
          })}
        </div>

        {/* Main Form Content */}
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* TAB 1: DATA PEKERJAAN */}
          {activeTab === 'pekerjaan' && (
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6"
            >
              <div className="flex items-center space-x-3 border-b border-slate-100 pb-4">
                <div className="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                  <Briefcase size={20} />
                </div>
                <div>
                  <h3 className="text-base font-extrabold text-slate-900">1. Data Pokok Pekerjaan</h3>
                  <p className="text-xs text-slate-500">Informasi penempatan, jabatan, dan status kepegawaian PT Sugiyama Indonesia</p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 text-xs">
                {/* Nama Lengkap */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Nama Lengkap Karyawan</label>
                  <input
                    type="text"
                    value={user.name}
                    disabled
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-semibold cursor-not-allowed"
                  />
                  <span className="text-[10px] text-slate-400">Sesuai nama terdaftar pada sistem HRD</span>
                </div>

                {/* NIK SGIN */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">NIK SGIN</label>
                  <input
                    type="text"
                    value={user.nik || 'EMP-201'}
                    disabled
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-emerald-800 font-mono font-bold cursor-not-allowed"
                  />
                  <span className="text-[10px] text-slate-400">Nomor Induk Karyawan PT Sugiyama Indonesia</span>
                </div>

                {/* Email Terdaftar */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Email Akun</label>
                  <input
                    type="email"
                    value={user.email}
                    disabled
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-semibold cursor-not-allowed"
                  />
                  <span className="text-[10px] text-slate-400">Email login & notifikasi sistem</span>
                </div>

                {/* Departemen / Bagian */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Departemen / Bagian</label>
                  {isHrdView ? (
                    <select
                      value={form.data.department_id}
                      onChange={(e) => form.setData('department_id', e.target.value)}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
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
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-semibold cursor-not-allowed"
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
                    placeholder="Contoh: Staff IT, Operator Machining, SPV"
                    className={`w-full px-3.5 py-2.5 rounded-xl ${!isHrdView && Boolean(user.position) ? 'bg-slate-100 cursor-not-allowed' : 'bg-slate-50 focus:bg-white'} border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none`}
                  />
                </div>

                {/* Status Karyawan */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Status Karyawan</label>
                  <select
                    value={form.data.employee_status}
                    onChange={(e) => form.setData('employee_status', e.target.value)}
                    disabled={!isHrdView && Boolean(user.employee_status)}
                    className={`w-full px-3.5 py-2.5 rounded-xl ${!isHrdView && Boolean(user.employee_status) ? 'bg-slate-100 cursor-not-allowed' : 'bg-slate-50 focus:bg-white'} border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none`}
                  >
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
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
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
                    className={`w-full px-3.5 py-2.5 rounded-xl ${!isHrdView && Boolean(user.join_date) ? 'bg-slate-100 cursor-not-allowed' : 'bg-slate-50 focus:bg-white'} border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none`}
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
                    className={`w-full px-3.5 py-2.5 rounded-xl ${!isHrdView ? 'bg-slate-100 cursor-not-allowed' : 'bg-slate-50 focus:bg-white'} border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none`}
                  />
                  <span className="text-[10px] text-slate-400">Khusus karyawan kontrak / PKWT</span>
                </div>
              </div>
            </motion.div>
          )}

          {/* TAB 2: IDENTITAS PRIBADI & KEPENDUDUKAN */}
          {activeTab === 'pribadi' && (
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6"
            >
              <div className="flex items-center space-x-3 border-b border-slate-100 pb-4">
                <div className="p-2.5 rounded-xl bg-emerald-50 text-emerald-600">
                  <User size={20} />
                </div>
                <div>
                  <h3 className="text-base font-extrabold text-slate-900">2. Identitas Pribadi & Kependudukan</h3>
                  <p className="text-xs text-slate-500">Data kependudukan resmi KTP, KK, dan kontak karyawan</p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 text-xs">
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
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    required
                  />
                  {form.errors.ktp_number && <p className="text-[11px] text-rose-600 font-bold">{form.errors.ktp_number}</p>}
                </div>

                {/* No KK */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">
                    Nomor Kartu Keluarga (No KK) <span className="text-rose-600">*</span>
                  </label>
                  <input
                    type="text"
                    maxLength={16}
                    value={form.data.kk_number}
                    onChange={(e) => form.setData('kk_number', e.target.value.replace(/\D/g, ''))}
                    placeholder="16 Digit Nomor KK"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* Jenis Kelamin */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Jenis Kelamin <span className="text-rose-600">*</span></label>
                  <select
                    value={form.data.gender}
                    onChange={(e) => form.setData('gender', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  >
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                  </select>
                </div>

                {/* Tempat Lahir */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Tempat Lahir <span className="text-rose-600">*</span></label>
                  <input
                    type="text"
                    value={form.data.birth_place}
                    onChange={(e) => form.setData('birth_place', e.target.value)}
                    placeholder="Kota kelahiran, contoh: Karawang"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* Tanggal Lahir */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Tanggal Lahir <span className="text-rose-600">*</span></label>
                  <input
                    type="date"
                    value={form.data.birth_date}
                    onChange={(e) => form.setData('birth_date', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* Golongan Darah */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Golongan Darah</label>
                  <select
                    value={form.data.blood_type}
                    onChange={(e) => form.setData('blood_type', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  >
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="AB">AB</option>
                    <option value="O">O</option>
                  </select>
                </div>

                {/* Nomor HP */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Nomor HP / WhatsApp <span className="text-rose-600">*</span></label>
                  <input
                    type="text"
                    value={form.data.phone_number}
                    onChange={(e) => form.setData('phone_number', e.target.value)}
                    placeholder="Contoh: 081234567890"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    required
                  />
                </div>

                {/* Status Perkawinan */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Status Perkawinan</label>
                  <select
                    value={form.data.marital_status}
                    onChange={(e) => form.setData('marital_status', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  >
                    <option value="Belum Menikah">Belum Menikah (TK/0)</option>
                    <option value="Menikah">Menikah (K/0)</option>
                    <option value="Menikah (Anak 1)">Menikah Anak 1 (K/1)</option>
                    <option value="Menikah (Anak 2)">Menikah Anak 2 (K/2)</option>
                    <option value="Menikah (Anak 3)">Menikah Anak 3 (K/3)</option>
                    <option value="Cerai Hidup">Cerai Hidup</option>
                    <option value="Cerai Mati">Cerai Mati</option>
                  </select>
                </div>

                {/* Nama Gadis Ibu Kandung */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Nama Gadis Ibu Kandung <span className="text-rose-600">*</span></label>
                  <input
                    type="text"
                    value={form.data.mother_maiden_name}
                    onChange={(e) => form.setData('mother_maiden_name', e.target.value)}
                    placeholder="Nama lengkap ibu kandung"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                  <span className="text-[10px] text-slate-400">Diperlukan untuk verifikasi BPJS & Perbankan</span>
                </div>
              </div>

              {/* Alamat KTP & Domisili (Full Width Textareas) */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2 text-xs">
                {/* Alamat KTP */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700 flex items-center space-x-1.5">
                    <MapPin size={14} className="text-emerald-600" />
                    <span>Alamat Lengkap KTP <span className="text-rose-600">*</span></span>
                  </label>
                  <textarea
                    rows={3}
                    value={form.data.ktp_address}
                    onChange={(e) => form.setData('ktp_address', e.target.value)}
                    placeholder="Sesuai alamat di KTP (RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten)"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-normal focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* Alamat Domisili */}
                <div className="space-y-1.5">
                  <div className="flex items-center justify-between">
                    <label className="font-bold text-slate-700 flex items-center space-x-1.5">
                      <MapPin size={14} className="text-blue-600" />
                      <span>Alamat Domisili Sekarang <span className="text-rose-600">*</span></span>
                    </label>
                    <button
                      type="button"
                      onClick={copyKtpAddress}
                      className="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 flex items-center space-x-1"
                    >
                      <Copy size={12} />
                      <span>Sama dengan KTP</span>
                    </button>
                  </div>
                  <textarea
                    rows={3}
                    value={form.data.domicile_address}
                    onChange={(e) => form.setData('domicile_address', e.target.value)}
                    placeholder="Alamat tempat tinggal saat ini"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-normal focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>
              </div>
            </motion.div>
          )}

          {/* TAB 3: BPJS, KEUANGAN & OPERASIONAL */}
          {activeTab === 'keuangan' && (
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6"
            >
              <div className="flex items-center space-x-3 border-b border-slate-100 pb-4">
                <div className="p-2.5 rounded-xl bg-amber-50 text-amber-600">
                  <ShieldCheck size={20} />
                </div>
                <div>
                  <h3 className="text-base font-extrabold text-slate-900">3. BPJS, Keuangan & Perlengkapan Operasional</h3>
                  <p className="text-xs text-slate-500">Nomor jaminan sosial, rekening penggajian, dan perlengkapan lapangan</p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 text-xs">
                {/* No BPJS Kesehatan */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">No. BPJS Kesehatan</label>
                  <input
                    type="text"
                    value={form.data.bpjs_kesehatan_number}
                    onChange={(e) => form.setData('bpjs_kesehatan_number', e.target.value)}
                    placeholder="Contoh: 0001234567890"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* Faskes BPJS Kes */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Faskes BPJS Kes (Faskes Tk 1)</label>
                  <input
                    type="text"
                    value={form.data.bpjs_health_facility}
                    onChange={(e) => form.setData('bpjs_health_facility', e.target.value)}
                    placeholder="Nama Klinik / Puskesmas Tingkat 1"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* No BPJS TKU */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">No. BPJS Ketenagakerjaan (TKU)</label>
                  <input
                    type="text"
                    value={form.data.bpjs_ketenagakerjaan_number}
                    onChange={(e) => form.setData('bpjs_ketenagakerjaan_number', e.target.value)}
                    placeholder="Nomor KPJ / BPJSTK"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* NPWP */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">NPWP</label>
                  <input
                    type="text"
                    value={form.data.npwp}
                    onChange={(e) => form.setData('npwp', e.target.value)}
                    placeholder="Contoh: 12.345.678.9-408.000"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-medium focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* Nama Bank */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Nama Bank Penggajian</label>
                  <input
                    type="text"
                    value={form.data.bank_name}
                    onChange={(e) => form.setData('bank_name', e.target.value)}
                    placeholder="Contoh: BCA / Mandiri / BRI / BNI"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* No Rekening */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Nomor Rekening Bank</label>
                  <input
                    type="text"
                    value={form.data.bank_account_number}
                    onChange={(e) => form.setData('bank_account_number', e.target.value)}
                    placeholder="Nomor rekening aktif"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* No Polisi Kendaraan */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">No Pol Kendaraan (Plat Nomor)</label>
                  <input
                    type="text"
                    value={form.data.vehicle_plate_number}
                    onChange={(e) => form.setData('vehicle_plate_number', e.target.value.toUpperCase())}
                    placeholder="Contoh: B 1234 SGI / T 5678 AA"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-bold uppercase focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* No SIM */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Nomor SIM</label>
                  <input
                    type="text"
                    value={form.data.sim_number}
                    onChange={(e) => form.setData('sim_number', e.target.value)}
                    placeholder="SIM A / C"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* Masa Berlaku SIM */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Masa Berlaku SIM</label>
                  <input
                    type="date"
                    value={form.data.sim_valid_until}
                    onChange={(e) => form.setData('sim_valid_until', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  />
                </div>

                {/* Ukuran Sepatu Safety */}
                <div className="space-y-1.5">
                  <label className="font-bold text-slate-700">Ukuran Sepatu Safety</label>
                  <select
                    value={form.data.shoe_size}
                    onChange={(e) => form.setData('shoe_size', e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                  >
                    {['37', '38', '39', '40', '41', '42', '43', '44', '45', '46'].map((sz) => (
                      <option key={sz} value={sz}>Ukuran {sz}</option>
                    ))}
                  </select>
                </div>
              </div>
            </motion.div>
          )}

          {/* TAB 4: KELUARGA & KONTAK DARURAT */}
          {activeTab === 'keluarga' && (
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-8"
            >
              {/* SUB-SECTION 1: KONTAK DARURAT */}
              <div className="space-y-4">
                <div className="flex items-center space-x-3 border-b border-slate-100 pb-3">
                  <div className="p-2 rounded-xl bg-rose-50 text-rose-600">
                    <Phone size={18} />
                  </div>
                  <div>
                    <h3 className="text-sm font-extrabold text-slate-900">Keluarga Yang Dapat Dihubungi (Kontak Darurat)</h3>
                    <p className="text-[11px] text-slate-500">Kontak penting saat keadaan darurat medis / kerja</p>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs">
                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Kontak Darurat <span className="text-rose-600">*</span></label>
                    <input
                      type="text"
                      value={form.data.emergency_contact_name}
                      onChange={(e) => form.setData('emergency_contact_name', e.target.value)}
                      placeholder="Nama lengkap keluarga"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Hubungan Keluarga <span className="text-rose-600">*</span></label>
                    <input
                      type="text"
                      value={form.data.emergency_contact_relationship}
                      onChange={(e) => form.setData('emergency_contact_relationship', e.target.value)}
                      placeholder="Contoh: Orang Tua / Suami / Istri / Kakak"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nomor Telepon / HP <span className="text-rose-600">*</span></label>
                    <input
                      type="text"
                      value={form.data.emergency_contact_phone}
                      onChange={(e) => form.setData('emergency_contact_phone', e.target.value)}
                      placeholder="Nomor telepon yang aktif"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>

                  <div className="col-span-1 md:col-span-3 space-y-1.5">
                    <label className="font-bold text-slate-700">Alamat Kontak Darurat</label>
                    <textarea
                      rows={2}
                      value={form.data.emergency_contact_address}
                      onChange={(e) => form.setData('emergency_contact_address', e.target.value)}
                      placeholder="Alamat tempat tinggal keluarga yang dapat dihubungi"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-normal focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>
                </div>
              </div>

              {/* SUB-SECTION 2: DATA PASANGAN */}
              <div className="space-y-4 pt-4 border-t border-slate-100">
                <div className="flex items-center space-x-3 border-b border-slate-100 pb-3">
                  <div className="p-2 rounded-xl bg-purple-50 text-purple-600">
                    <Heart size={18} />
                  </div>
                  <div>
                    <h3 className="text-sm font-extrabold text-slate-900">Data Pasangan (Suami / Istri)</h3>
                    <p className="text-[11px] text-slate-500">Khusus bagi karyawan dengan status menikah</p>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 text-xs">
                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Suami / Istri</label>
                    <input
                      type="text"
                      value={form.data.spouse_name}
                      onChange={(e) => form.setData('spouse_name', e.target.value)}
                      placeholder="Nama lengkap pasangan"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
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
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 font-mono text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Tempat Lahir Pasangan</label>
                    <input
                      type="text"
                      value={form.data.spouse_birth_place}
                      onChange={(e) => form.setData('spouse_birth_place', e.target.value)}
                      placeholder="Kota lahir pasangan"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Tanggal Lahir Pasangan</label>
                    <input
                      type="date"
                      value={form.data.spouse_birth_date}
                      onChange={(e) => form.setData('spouse_birth_date', e.target.value)}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>
                </div>
              </div>

              {/* SUB-SECTION 3: DATA ANAK */}
              <div className="space-y-4 pt-4 border-t border-slate-100">
                <div className="flex items-center space-x-3 border-b border-slate-100 pb-3">
                  <div className="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <Users size={18} />
                  </div>
                  <div>
                    <h3 className="text-sm font-extrabold text-slate-900">Data Tanggungan Anak</h3>
                    <p className="text-[11px] text-slate-500">Nama anak kandung / tanggungan keluarga (Anak ke-1 s/d 3)</p>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs">
                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Anak ke-1</label>
                    <input
                      type="text"
                      value={form.data.child_1_name}
                      onChange={(e) => form.setData('child_1_name', e.target.value)}
                      placeholder="Nama anak pertama"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Anak ke-2</label>
                    <input
                      type="text"
                      value={form.data.child_2_name}
                      onChange={(e) => form.setData('child_2_name', e.target.value)}
                      placeholder="Nama anak kedua"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <label className="font-bold text-slate-700">Nama Anak ke-3</label>
                    <input
                      type="text"
                      value={form.data.child_3_name}
                      onChange={(e) => form.setData('child_3_name', e.target.value)}
                      placeholder="Nama anak ketiga"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 focus:bg-white border border-slate-300 text-slate-900 font-semibold focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>
                </div>
              </div>
            </motion.div>
          )}

          {/* Sticky Bottom Save Action Bar */}
          <div className="sticky bottom-4 z-20 p-4 rounded-3xl bg-white/90 backdrop-blur-md border border-slate-200 shadow-xl flex items-center justify-between gap-4">
            <div className="flex items-center space-x-3 text-xs text-slate-600">
              <Info size={16} className="text-emerald-600 shrink-0" />
              <span className="hidden sm:inline">
                Pastikan data yang diisi telah sesuai dengan dokumen resmi kependudukan (KTP & KK).
              </span>
              <span className="sm:hidden font-bold">
                Kelengkapan: {liveCompletenessPercent}%
              </span>
            </div>

            <div className="flex items-center space-x-3">
              <button
                type="submit"
                disabled={form.processing}
                className="px-6 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-extrabold text-xs sm:text-sm shadow-md shadow-emerald-600/20 flex items-center space-x-2 transition-all disabled:opacity-50 cursor-pointer"
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
