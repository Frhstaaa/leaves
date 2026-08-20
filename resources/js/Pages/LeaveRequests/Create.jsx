import React, { useState, useEffect } from 'react';
import { useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
  FileText,
  User,
  Mail,
  Building,
  Calendar,
  Clock,
  Paperclip,
  CheckCircle2,
  AlertCircle,
  ArrowLeft,
  Upload,
  Info,
  ShieldCheck,
  ChevronRight,
  ChevronLeft,
  ChevronDown,
  RotateCcw,
  Send,
  HelpCircle,
  Check,
  Search,
  X
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

export default function CreateLeaveRequest({ user, categories, quota }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    submission_type: 'PERMOHONAN',
    approval_agreed: 'Ya',
    leave_category_id: categories[0]?.id || '',
    unit: 'hari',
    amount: 1,
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    reason: '',
    attachment: null,
  });

  const [currentStep, setCurrentStep] = useState(1);
  const [selectedCategory, setSelectedCategory] = useState(categories[0] || null);
  const [categoryModalOpen, setCategoryModalOpen] = useState(false);
  const [categorySearch, setCategorySearch] = useState('');

  const [filePreviewName, setFilePreviewName] = useState('');
  const [fileSizeText, setFileSizeText] = useState('');
  const [agreedError, setAgreedError] = useState('');

  const handleSelectCategory = (cat) => {
    setData('leave_category_id', cat.id);
    setSelectedCategory(cat);
    setData('unit', cat.unit_type || 'hari');
    setCategoryModalOpen(false);
  };

  useEffect(() => {
    if (data.start_date && data.end_date && data.unit === 'hari') {
      const start = new Date(data.start_date);
      const end = new Date(data.end_date);
      if (end >= start) {
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        setData('amount', diffDays);
      }
    }
  }, [data.start_date, data.end_date, data.unit]);

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setData('attachment', file);
      setFilePreviewName(file.name);
      setFileSizeText((file.size / (1024 * 1024)).toFixed(2) + ' MB');
    }
  };

  const handleNextStep = () => {
    if (currentStep === 1) {
      if (data.approval_agreed !== 'Ya') {
        setAgreedError('Anda wajib memilih "Ya" untuk menyetujui proses persetujuan kepala departemen.');
        return;
      }
      setAgreedError('');
    }
    if (currentStep === 2) {
      if (!data.start_date) {
        alert('Tanggal Mulai wajib diisi.');
        return;
      }
      if (!data.end_date) {
        alert('Tanggal Selesai wajib diisi.');
        return;
      }
      if (!data.reason || data.reason.trim().length < 3) {
        alert('Detail alasan permohonan wajib diisi (minimal 3 karakter).');
        return;
      }
      if (selectedCategory?.requires_attachment && !data.attachment) {
        alert('Kategori ' + selectedCategory.name + ' wajib melampirkan file dokumen pendukung.');
        return;
      }
    }
    setCurrentStep((prev) => Math.min(prev + 1, 3));
  };

  const handlePrevStep = () => {
    setCurrentStep((prev) => Math.max(prev - 1, 1));
  };

  const handleResetForm = () => {
    if (confirm('Apakah Anda yakin ingin mengosongkan formulir ini?')) {
      reset();
      setFilePreviewName('');
      setFileSizeText('');
      setCurrentStep(1);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (data.approval_agreed !== 'Ya') {
      setAgreedError('Anda wajib menyetujui persetujuan kepala departemen.');
      setCurrentStep(1);
      return;
    }
    if (!data.reason || data.reason.trim().length < 3) {
      alert('Detail alasan permohonan wajib diisi (minimal 3 karakter).');
      setCurrentStep(2);
      return;
    }
    post(route('leave-requests.store'), {
      onError: (errs) => {
        if (errs.reason || errs.start_date || errs.end_date || errs.amount || errs.attachment) {
          setCurrentStep(2);
        } else if (errs.submission_type || errs.approval_agreed || errs.leave_category_id) {
          setCurrentStep(1);
        }
      }
    });
  };

  const totalQuota = quota?.total_quota || 12;
  const usedQuota = quota?.used_quota || 0;
  const remainingQuota = quota?.remaining_quota || (totalQuota - usedQuota);

  const filteredCategories = categories.filter((cat) => {
    if (!categorySearch) return true;
    return cat.name.toLowerCase().includes(categorySearch.toLowerCase());
  });

  return (
    <AuthenticatedLayout title="Buat Pengajuan Baru">
      <div className="w-full space-y-6">

        {/* Page Header */}
        <div className="flex items-center justify-between gap-3">
          <div className="flex items-center space-x-2.5 sm:space-x-3 min-w-0 flex-1">
            <Link
              href={route('dashboard')}
              className="w-9 h-9 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-all shadow-sm shrink-0"
              title="Kembali ke Dashboard"
            >
              <ArrowLeft size={18} />
            </Link>
            <div className="min-w-0 flex-1">
              <h2 className="text-base sm:text-2xl font-black text-slate-900 tracking-tight truncate">Buat Pengajuan Baru</h2>
              <p className="text-[11px] sm:text-sm text-slate-500 font-medium truncate">Formulir permohonan izin, dinas, sakit & cuti karyawan</p>
            </div>
          </div>

          <button
            type="button"
            onClick={handleResetForm}
            title="Reset Form"
            className="w-9 h-9 sm:w-auto sm:h-auto p-0 sm:px-4 sm:py-2.5 rounded-xl sm:rounded-2xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-bold text-xs flex items-center justify-center sm:space-x-2 transition-all shadow-sm shrink-0"
          >
            <RotateCcw size={15} />
            <span className="hidden sm:inline">Reset Formulir</span>
          </button>
        </div>

        {/* Form Container matching Mockup Design */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, ease: 'easeOut' }}
          className="p-4 sm:p-8 rounded-2xl sm:rounded-3xl bg-white border border-slate-200/80 shadow-lg space-y-5 sm:space-y-6 w-full"
        >

          {/* Stepper Progress Bar (1 Jenis -> 2 Detail -> 3 Review) */}
          <div className="relative flex items-center justify-between px-4 sm:px-16 md:px-28 py-2 sm:py-3 max-w-2xl mx-auto">
            {/* Track Container (Center of Step 1 to Center of Step 3) */}
            <div className="absolute top-[20px] sm:top-[23px] left-8 right-8 sm:left-20 sm:right-20 md:left-32 md:right-32 h-1 bg-slate-100 -translate-y-1/2 z-0 rounded-full overflow-hidden">
              <div
                className="h-full bg-[#0FA172] transition-all duration-300 rounded-full"
                style={{ width: currentStep === 1 ? '0%' : currentStep === 2 ? '50%' : '100%' }}
              />
            </div>

            {/* Step 1 */}
            <div className="relative z-10 flex flex-col items-center space-y-1 sm:space-y-1.5 cursor-pointer" onClick={() => setCurrentStep(1)}>
              <div className={`w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center font-black text-xs transition-all ${
                currentStep >= 1 ? 'bg-[#0FA172] text-white shadow-md shadow-emerald-600/30 ring-4 ring-white' : 'bg-slate-200 text-slate-500 ring-4 ring-white'
              }`}>
                1
              </div>
              <span className={`text-[11px] sm:text-xs font-bold ${currentStep >= 1 ? 'text-emerald-700' : 'text-slate-400'}`}>
                Jenis
              </span>
            </div>

            {/* Step 2 */}
            <div className="relative z-10 flex flex-col items-center space-y-1 sm:space-y-1.5 cursor-pointer" onClick={() => { if (data.approval_agreed === 'Ya') setCurrentStep(2); }}>
              <div className={`w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center font-black text-xs transition-all ${
                currentStep >= 2 ? 'bg-[#0FA172] text-white shadow-md shadow-emerald-600/30 ring-4 ring-white' : 'bg-slate-200 text-slate-500 ring-4 ring-white'
              }`}>
                2
              </div>
              <span className={`text-[11px] sm:text-xs font-bold ${currentStep >= 2 ? 'text-emerald-700' : 'text-slate-400'}`}>
                Detail
              </span>
            </div>

            {/* Step 3 */}
            <div className="relative z-10 flex flex-col items-center space-y-1 sm:space-y-1.5">
              <div className={`w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center font-black text-xs transition-all ${
                currentStep >= 3 ? 'bg-[#0FA172] text-white shadow-md shadow-emerald-600/30 ring-4 ring-white' : 'bg-slate-200 text-slate-500 ring-4 ring-white'
              }`}>
                3
              </div>
              <span className={`text-[11px] sm:text-xs font-bold ${currentStep >= 3 ? 'text-emerald-700' : 'text-slate-400'}`}>
                Review
              </span>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">

            {/* STEP 1: JENIS PENGAJUAN & DIVISION APPROVAL */}
            {currentStep === 1 && (
              <div className="space-y-5 animate-fade-in">
                {/* User Identity Info Card */}
                <div className="p-4 sm:p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                  <span className="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider">Identitas Pemohon (Auto-filled)</span>
                  <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                    <div>
                      <p className="font-extrabold text-slate-900 text-base">{user.name}</p>
                      <p className="text-slate-500 mt-0.5">NIK: <strong className="text-emerald-700">{user.nik || 'EMP-201'}</strong> &bull; Dept: <strong className="text-slate-800">{user.department_name || user.department?.name || 'Information Technology'}</strong></p>
                    </div>
                    <span className="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200 self-start sm:self-auto">
                      Aktif
                    </span>
                  </div>
                </div>

                {/* Question: Jenis Pengajuan (PEMBERITAHUAN / PERMOHONAN) */}
                <div className="space-y-2">
                  <label className="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                    Sifat Pengajuan <span className="text-rose-600">*</span>
                  </label>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <button
                      type="button"
                      onClick={() => setData('submission_type', 'PERMOHONAN')}
                      className={`p-4 rounded-2xl border text-left flex items-center justify-between transition-all ${
                        data.submission_type === 'PERMOHONAN'
                          ? 'bg-emerald-50/80 border-emerald-500 ring-2 ring-emerald-500/20 text-emerald-950 font-bold'
                          : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                      }`}
                    >
                      <div>
                        <p className="text-xs sm:text-sm font-bold">PERMOHONAN</p>
                        <p className="text-[11px] text-slate-500 font-normal mt-0.5">Membutuhkan persetujuan atasan terlebih dahulu</p>
                      </div>
                      {data.submission_type === 'PERMOHONAN' && (
                        <div className="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 ml-2">
                          <Check size={14} />
                        </div>
                      )}
                    </button>

                    <button
                      type="button"
                      onClick={() => setData('submission_type', 'PEMBERITAHUAN')}
                      className={`p-4 rounded-2xl border text-left flex items-center justify-between transition-all ${
                        data.submission_type === 'PEMBERITAHUAN'
                          ? 'bg-emerald-50/80 border-emerald-500 ring-2 ring-emerald-500/20 text-emerald-950 font-bold'
                          : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                      }`}
                    >
                      <div>
                        <p className="text-xs sm:text-sm font-bold">PEMBERITAHUAN</p>
                        <p className="text-[11px] text-slate-500 font-normal mt-0.5">Pemberitahuan resmi kondisi khusus/darurat</p>
                      </div>
                      {data.submission_type === 'PEMBERITAHUAN' && (
                        <div className="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 ml-2">
                          <Check size={14} />
                        </div>
                      )}
                    </button>
                  </div>
                </div>

                {/* Custom Sleek Select Trigger Component for Category */}
                <div className="space-y-2">
                  <label className="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                    Kategori Tidak Bekerja <span className="text-rose-600">*</span>
                  </label>

                  <button
                    type="button"
                    onClick={() => setCategoryModalOpen(true)}
                    className="w-full px-4 py-3.5 rounded-2xl bg-white border border-slate-300 hover:border-emerald-500 text-slate-900 font-extrabold text-xs sm:text-sm flex items-center justify-between shadow-sm transition-all text-left"
                  >
                    <span className={selectedCategory ? 'text-slate-900' : 'text-slate-400'}>
                      {selectedCategory ? selectedCategory.name : '-- Pilih Kategori Tidak Bekerja --'}
                    </span>
                    <ChevronDown size={18} className="text-slate-400 shrink-0 ml-2" />
                  </button>

                  {/* Selected Category Detail Card */}
                  {selectedCategory && (
                    <div className="p-4 rounded-2xl bg-emerald-50/80 border border-emerald-200 space-y-1.5 animate-fade-in mt-2">
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-extrabold text-emerald-950 flex items-center space-x-1.5">
                          <FileText size={16} className="text-emerald-600 shrink-0" />
                          <span>{selectedCategory.name}</span>
                        </span>
                        <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase ${
                          selectedCategory.requires_attachment ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                        }`}>
                          {selectedCategory.requires_attachment ? 'Wajib Surat / Bukti Lampiran' : 'Tanpa Lampiran'}
                        </span>
                      </div>
                      <p className="text-[11px] text-emerald-900 leading-relaxed font-medium">
                        {selectedCategory.description || `Satuan permohonan: ${selectedCategory.unit_type}.`}
                      </p>
                    </div>
                  )}
                </div>

                {/* Question: Agreement Check Approval Division */}
                <div className="p-4 sm:p-5 rounded-2xl bg-amber-50/80 border border-amber-200 space-y-3">
                  <div className="flex items-start space-x-3">
                    <ShieldCheck size={20} className="text-amber-700 shrink-0 mt-0.5" />
                    <div>
                      <h4 className="text-xs font-bold text-amber-950">Persetujuan Atasan: {user?.manager_name || 'Atasan Direct'}</h4>
                      <p className="text-[11px] text-amber-800 leading-relaxed mt-0.5">
                        Apakah Anda menyetujui bahwa pengajuan ini akan diteruskan ke <strong>{user?.manager_name || 'Atasan Direct'}</strong> untuk proses peninjauan dan persetujuan?
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center space-x-4 pt-1">
                    <label className="flex items-center space-x-2 cursor-pointer text-xs font-bold text-slate-800">
                      <input
                        type="radio"
                        name="approval_agreed"
                        value="Ya"
                        checked={data.approval_agreed === 'Ya'}
                        onChange={(e) => {
                          setData('approval_agreed', e.target.value);
                          setAgreedError('');
                        }}
                        className="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-slate-300"
                      />
                      <span>Ya (Saya Setuju)</span>
                    </label>

                    <label className="flex items-center space-x-2 cursor-pointer text-xs font-bold text-slate-600">
                      <input
                        type="radio"
                        name="approval_agreed"
                        value="Tidak"
                        checked={data.approval_agreed === 'Tidak'}
                        onChange={(e) => setData('approval_agreed', e.target.value)}
                        className="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-slate-300"
                      />
                      <span>Tidak</span>
                    </label>
                  </div>
                  {agreedError && <p className="text-xs text-rose-600 font-bold">{agreedError}</p>}
                </div>
              </div>
            )}

            {/* STEP 2: DETAIL FORMULIR & TANGGAL */}
            {currentStep === 2 && (
              <div className="space-y-5 animate-fade-in">
                {/* Quota Summary Box */}
                {selectedCategory?.name?.toLowerCase().includes('cuti tahunan') && (
                  <div className="p-4 sm:p-5 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-between">
                    <div>
                      <span className="text-[10px] font-extrabold uppercase text-teal-700">Sisa Kuota Cuti Tahunan Anda</span>
                      <p className="text-base sm:text-lg font-black text-slate-900">{remainingQuota} Hari <span className="text-xs font-bold text-slate-500">(dari {totalQuota} hari)</span></p>
                    </div>
                    <div className="w-10 h-10 rounded-full bg-teal-600 text-white font-black flex items-center justify-center text-sm shadow-md shadow-teal-600/20">
                      {remainingQuota}d
                    </div>
                  </div>
                )}

                {/* Tanggal Cuti & Total Hari/Jam */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase mb-1">
                      Tanggal Mulai <span className="text-rose-600">*</span>
                    </label>
                    <input
                      type="date"
                      value={data.start_date}
                      onChange={(e) => setData('start_date', e.target.value)}
                      className="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-semibold text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                      required
                    />
                    {errors.start_date && <p className="mt-1 text-xs text-rose-600 font-bold">{errors.start_date}</p>}
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase mb-1">
                      Tanggal Selesai <span className="text-rose-600">*</span>
                    </label>
                    <input
                      type="date"
                      value={data.end_date}
                      onChange={(e) => setData('end_date', e.target.value)}
                      className="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-semibold text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                      required
                    />
                    {errors.end_date && <p className="mt-1 text-xs text-rose-600 font-bold">{errors.end_date}</p>}
                  </div>
                </div>

                {/* Durasi Cuti Input */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase mb-1">Jumlah Durasi</label>
                    <input
                      type="number"
                      step="0.5"
                      min="0.5"
                      value={data.amount}
                      onChange={(e) => setData('amount', parseFloat(e.target.value) || 0.5)}
                      className="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase mb-1">Satuan</label>
                    <select
                      value={data.unit}
                      onChange={(e) => setData('unit', e.target.value)}
                      className="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                    >
                      <option value="hari">Hari</option>
                      <option value="jam">Jam</option>
                    </select>
                  </div>
                </div>

                {/* Alasan Permohonan */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase mb-1">
                    Detail Alasan Permohonan / Ketidakhadiran <span className="text-rose-600">*</span>
                  </label>
                  <textarea
                    rows={4}
                    value={data.reason}
                    onChange={(e) => setData('reason', e.target.value)}
                    placeholder="Jelaskan alasan detail pengajuan permohonan atau ketidakhadiran Anda secara jelas..."
                    className="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-medium text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                    required
                  />
                  {errors.reason && <p className="mt-1 text-xs text-rose-600 font-bold">{errors.reason}</p>}
                </div>

                {/* Attachment File Upload (Auto Convert to WebP for images) */}
                <div className="space-y-1.5">
                  <label className="block text-xs font-bold text-slate-700 uppercase">
                    File Lampiran Pendukung {selectedCategory?.requires_attachment ? <span className="text-rose-600">(Wajib *)</span> : '(Opsional)'}
                  </label>
                  <div className="p-5 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-slate-50 text-center cursor-pointer transition-colors relative">
                    <input
                      type="file"
                      accept=".pdf,.png,.jpg,.jpeg,.webp"
                      onChange={handleFileChange}
                      className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    />
                    <Upload size={26} className="mx-auto text-emerald-600 mb-1.5" />
                    {filePreviewName ? (
                      <div>
                        <p className="text-xs font-bold text-emerald-800">{filePreviewName}</p>
                        <p className="text-[10px] text-slate-500 mt-0.5">{fileSizeText} &bull; Siap diunggah (Otomatis WebP)</p>
                      </div>
                    ) : (
                      <div>
                        <p className="text-xs font-bold text-slate-700">Pilih atau Seret File Surat Keterangan / Bukti Lampiran</p>
                        <p className="text-[10px] text-slate-400 mt-0.5">PDF, PNG, JPG, WEBP (Maksimal 10 MB)</p>
                      </div>
                    )}
                  </div>
                  {errors.attachment && <p className="text-xs text-rose-600 font-bold">{errors.attachment}</p>}
                </div>
              </div>
            )}

            {/* STEP 3: REVIEW & PRATINJAU PENGAJUAN */}
            {currentStep === 3 && (
              <div className="space-y-5 animate-fade-in">
                {Object.keys(errors).length > 0 && (
                  <div className="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 space-y-1">
                    <div className="flex items-center space-x-2 font-bold text-xs">
                      <AlertCircle size={16} className="text-rose-600 shrink-0" />
                      <span>Terdapat kesalahan pada data pengajuan:</span>
                    </div>
                    <ul className="list-disc list-inside text-xs space-y-0.5 pl-2 font-medium">
                      {Object.values(errors).map((err, idx) => (
                        <li key={idx}>{err}</li>
                      ))}
                    </ul>
                  </div>
                )}

                <div className="p-4 sm:p-5 rounded-2xl bg-emerald-50 border border-emerald-200">
                  <h4 className="text-xs sm:text-sm font-extrabold text-emerald-950 uppercase tracking-wider mb-1">Pratinjau Data Pengajuan Cuti</h4>
                  <p className="text-xs text-emerald-800">Mohon periksa kembali detail permohonan Anda sebelum mengirimkan ke atasan.</p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div className="p-3.5 sm:p-4 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                    <span className="text-slate-500 font-medium">Sifat Pengajuan</span>
                    <span className="font-extrabold text-emerald-700">{data.submission_type}</span>
                  </div>

                  <div className="p-3.5 sm:p-4 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                    <span className="text-slate-500 font-medium">Tujuan Approval</span>
                    <span className="font-bold text-emerald-700 truncate max-w-[180px]">{user?.manager_name || 'Atasan Direct'}</span>
                  </div>

                  <div className="p-3.5 sm:p-4 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                    <span className="text-slate-500 font-medium">Jenis Kategori</span>
                    <span className="font-bold text-slate-900">{selectedCategory?.name}</span>
                  </div>

                  <div className="p-3.5 sm:p-4 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                    <span className="text-slate-500 font-medium">Periode Tanggal</span>
                    <span className="font-bold text-slate-900">{data.start_date} s/d {data.end_date}</span>
                  </div>

                  <div className="p-3.5 sm:p-4 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                    <span className="text-slate-500 font-medium">Total Durasi</span>
                    <span className="font-extrabold text-slate-900">{data.amount} {data.unit}</span>
                  </div>

                  <div className="p-3.5 sm:p-4 rounded-xl border border-slate-200 bg-slate-50 flex justify-between items-center">
                    <span className="text-slate-500 font-medium">File Lampiran</span>
                    <span className="font-semibold text-slate-700 truncate max-w-[150px]">{filePreviewName || 'Tidak Ada File'}</span>
                  </div>

                  <div className="p-3.5 sm:p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-1.5 sm:col-span-2">
                    <span className="text-slate-500 font-medium block">Detail Alasan:</span>
                    <p className="font-semibold text-slate-800 bg-white p-3 rounded-lg border border-slate-200 leading-relaxed">{data.reason}</p>
                  </div>
                </div>
              </div>
            )}

            {/* Stepper Buttons (Lanjutkan / Kirim Pengajuan) */}
            <div className="flex items-center justify-between pt-4 border-t border-slate-100">
              {currentStep > 1 ? (
                <button
                  type="button"
                  onClick={handlePrevStep}
                  className="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center space-x-1 transition-colors"
                >
                  <ChevronLeft size={16} />
                  <span>Kembali</span>
                </button>
              ) : (
                <div></div>
              )}

              {currentStep < 3 ? (
                <button
                  type="button"
                  onClick={handleNextStep}
                  className="px-6 py-3.5 rounded-2xl bg-[#0FA172] hover:bg-[#1CB67C] text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 flex items-center space-x-2 transition-all ml-auto"
                >
                  <span>Lanjutkan</span>
                  <ChevronRight size={16} />
                </button>
              ) : (
                <button
                  type="submit"
                  disabled={processing}
                  className="px-7 py-3.5 rounded-2xl bg-[#0FA172] hover:bg-[#1CB67C] text-white font-black text-xs shadow-lg shadow-emerald-600/20 flex items-center space-x-2 transition-all ml-auto disabled:opacity-50"
                >
                  <Send size={16} />
                  <span>Kirim Pengajuan Cuti</span>
                </button>
              )}
            </div>

          </form>
        </motion.div>

      </div>

      {/* ALL CATEGORIES SEARCHABLE MODAL WITH FRAMER MOTION */}
      <AnimatePresence>
        {categoryModalOpen && (
          <div className="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.15 }}
              className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm"
              onClick={() => setCategoryModalOpen(false)}
            />

            <motion.div
              initial={{ y: '100%', opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              exit={{ y: '100%', opacity: 0 }}
              transition={{ type: 'spring', stiffness: 380, damping: 30 }}
              className="relative z-10 w-full max-w-lg p-5 rounded-t-3xl sm:rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4 max-h-[85vh] flex flex-col"
            >
              <div className="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
                <div className="flex items-center space-x-2">
                  <FileText size={20} className="text-emerald-600" />
                  <h3 className="text-base font-extrabold text-slate-900">Pilih Kategori Tidak Bekerja</h3>
                </div>
                <button
                  type="button"
                  onClick={() => setCategoryModalOpen(false)}
                  className="p-1.5 rounded-lg bg-slate-100 text-slate-400 hover:text-slate-800"
                >
                  <X size={18} />
                </button>
              </div>

              {/* Search Input */}
              <div className="relative shrink-0">
                <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type="text"
                  value={categorySearch}
                  onChange={(e) => setCategorySearch(e.target.value)}
                  placeholder="Cari jenis permohonan / izin / sakit..."
                  className="w-full pl-9 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 placeholder-slate-400 text-xs font-semibold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                />
              </div>

              {/* Category List */}
              <div className="space-y-2 overflow-y-auto flex-1 pr-1">
                {filteredCategories.length > 0 ? (
                  filteredCategories.map((cat) => {
                    const isSelected = data.leave_category_id == cat.id;
                    return (
                      <button
                        key={cat.id}
                        type="button"
                        onClick={() => handleSelectCategory(cat)}
                        className={`w-full p-3.5 rounded-2xl border text-left flex items-center justify-between transition-all ${
                          isSelected
                            ? 'bg-emerald-50 border-emerald-500 shadow-sm'
                            : 'bg-slate-50 border-slate-200 hover:bg-slate-100'
                        }`}
                      >
                        <div className="min-w-0 flex-1 pr-2">
                          <div className="flex items-center space-x-2">
                            <span className="font-bold text-xs text-slate-900">{cat.name}</span>
                            <span className="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-200 text-slate-700">
                              {cat.type}
                            </span>
                          </div>
                          {cat.description && (
                            <p className="text-[11px] text-slate-500 font-normal mt-0.5 truncate">{cat.description}</p>
                          )}
                        </div>

                        {isSelected && (
                          <div className="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                            <Check size={14} />
                          </div>
                        )}
                      </button>
                    );
                  })
                ) : (
                  <div className="p-8 text-center text-slate-400">
                    <p className="text-xs font-semibold">Tidak ada kategori yang cocok dengan pencarian.</p>
                  </div>
                )}
              </div>

              <div className="pt-2 shrink-0 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setCategoryModalOpen(false)}
                  className="w-full py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors"
                >
                  Batal
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </AuthenticatedLayout>
  );
}
