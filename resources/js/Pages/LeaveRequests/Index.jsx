import React, { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { motion, AnimatePresence } from 'framer-motion';
import {
  FileText,
  Plus,
  Search,
  Filter,
  CheckCircle,
  XCircle,
  Clock,
  Paperclip,
  Trash2,
  Calendar,
  History,
  X,
  ChevronRight,
  Eye,
  MoreVertical,
  Copy
} from 'lucide-react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";

import { showConfirm, showToast } from '@/Utils/swal';

export default function LeaveRequestsIndex({ user: propUser, requests, filters, quota }) {
  const { auth } = usePage().props;
  const currentUser = propUser || auth?.user || {};

  const [selectedRequest, setSelectedRequest] = useState(null);
  const [search, setSearch] = useState(filters?.search || '');
  const [status, setStatus] = useState(filters?.status || '');

  const handleFilter = (e) => {
    e.preventDefault();
    router.get(route('leave-requests.index'), { search, status }, { preserveState: true });
  };

  const handleCancel = async (id) => {
    const confirmed = await showConfirm({
      title: 'Batalkan Pengajuan Cuti?',
      text: 'Pengajuan cuti yang masih berstatus pending akan dibatalkan dan dihapus.',
      icon: 'warning',
      confirmText: 'Ya, Batalkan',
      cancelText: 'Kembali',
    });

    if (confirmed) {
      router.delete(route('leave-requests.destroy', id), {
        onSuccess: () => showToast('Pengajuan cuti berhasil dibatalkan.'),
      });
    }
  };

  const totalQuota = quota?.total_quota || 12;
  const usedQuota = quota?.used_quota || 0;
  const remainingQuota = quota?.remaining_quota || (totalQuota - usedQuota);

  return (
    <AuthenticatedLayout title="Riwayat Pengajuan Cuti">
      <div className="space-y-6">

        {/* Page Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Riwayat Pengajuan Cuti</h2>
            <p className="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">Daftar permohonan & pemberitahuan ketidakhadiran Anda</p>
          </div>

          <Link
            href={route('leave-requests.create')}
            className="px-5 py-3 rounded-2xl bg-[#0FA172] hover:bg-[#1CB67C] text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 flex items-center justify-center space-x-2 transition-transform hover:scale-105 shrink-0"
          >
            <Plus size={18} />
            <span>Buat Pengajuan Baru</span>
          </Link>
        </div>

        {/* Kuotas Summary Bar */}
        <div className="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-emerald-800 to-teal-900 text-white shadow-lg flex items-center justify-between">
          <div className="space-y-1">
            <span className="text-[10px] font-extrabold uppercase text-emerald-200 tracking-wider">Hak Cuti Tahunan ({new Date().getFullYear()})</span>
            <p className="text-lg sm:text-xl font-black">{remainingQuota} Hari Tersisa <span className="text-xs font-semibold text-emerald-300">(dari {totalQuota} hari)</span></p>
          </div>
          <div className="text-right">
            <span className="text-[10px] font-extrabold uppercase text-emerald-200 tracking-wider">Terpakai</span>
            <p className="text-base sm:text-lg font-black text-amber-300">{usedQuota} Hari</p>
          </div>
        </div>

        {/* Filters Bar */}
        <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-3">
          <form onSubmit={handleFilter} className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                placeholder="Cari nomor request atau alasan..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full pl-9 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
              />
            </div>

            <div className="w-full sm:w-[170px] shrink-0">
              <Select value={status || 'all'} onValueChange={(val) => setStatus(val === 'all' ? '' : val)}>
                <SelectTrigger className="w-full bg-slate-50 border-slate-200 font-bold text-xs rounded-xl">
                  <SelectValue placeholder="Semua Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Status</SelectItem>
                  <SelectItem value="pending">⏳ Pending</SelectItem>
                  <SelectItem value="approved">✅ Disetujui</SelectItem>
                  <SelectItem value="rejected">❌ Ditolak</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Button
              type="submit"
              variant="default"
              className="px-5 py-2.5 rounded-xl font-black text-xs shrink-0 space-x-1.5"
            >
              <Filter size={14} />
              <span>Filter Data</span>
            </Button>
          </form>
        </div>

        {/* Request List */}
        <div className="space-y-3">
          {requests?.data && requests.data.length > 0 ? (
            <div className="space-y-3">
              {requests.data.map((req, index) => (
                <motion.div
                  key={req.id}
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.25, delay: Math.min(index * 0.04, 0.3), ease: 'easeOut' }}
                  whileHover={{ y: -2, transition: { duration: 0.15 } }}
                  className="p-4 sm:p-5 rounded-3xl bg-white border border-slate-200/90 shadow-sm hover:shadow-md transition-all space-y-3"
                >
                  <div className="flex items-start justify-between">
                    <div className="space-y-1 min-w-0 pr-2">
                      <div className="flex items-center space-x-2">
                        <span className="font-mono text-xs font-black text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200">
                          {req.request_number}
                        </span>
                        <span className="text-xs font-extrabold text-slate-900 truncate">
                          {req.category?.name}
                        </span>
                      </div>
                      <p className="text-[11px] text-slate-500 font-semibold truncate">
                        Sifat: <strong className="text-slate-800">{req.submission_type}</strong> &bull; {req.start_date} s/d {req.end_date} ({req.amount} {req.unit})
                      </p>
                    </div>

                    <div className="flex items-center space-x-1.5 shrink-0">
                      {req.status === 'pending' && (
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black border ${
                          req.current_stage === 'approval_1' ? 'bg-blue-100 text-blue-800 border-blue-200' :
                          req.current_stage === 'approval_2' ? 'bg-purple-100 text-purple-800 border-purple-200' :
                          'bg-amber-100 text-amber-800 border-amber-200'
                        }`}>
                          {req.current_stage === 'approval_1' ? 'Menunggu Atasan 1' :
                           req.current_stage === 'approval_2' ? 'Menunggu Atasan 2' :
                           'Menunggu HRD'}
                        </span>
                      )}
                      <span className={`inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase ${
                        req.status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                        req.status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200' :
                        'bg-amber-100 text-amber-800 border border-amber-200'
                      }`}>
                        {req.status === 'approved' ? 'Disetujui' : req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                      </span>
                    </div>
                  </div>

                  <p className="text-xs text-slate-600 bg-slate-50 p-3 rounded-2xl border border-slate-100 font-medium line-clamp-2">
                    {req.reason}
                  </p>

                  <div className="flex items-center justify-between pt-1">
                    <div className="flex items-center space-x-2 text-[11px] text-slate-400 font-medium">
                      <Clock size={13} />
                      <span>{new Date(req.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
                    </div>

                    <div className="flex items-center space-x-1.5">
                      <Button
                        variant="secondary"
                        size="sm"
                        onClick={() => setSelectedRequest(req)}
                        className="rounded-xl space-x-1 font-bold text-xs"
                      >
                        <Eye size={14} />
                        <span>Lihat Detail</span>
                      </Button>

                      {/* Dropdown Menu for Extra Actions */}
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" className="h-8 w-8 rounded-xl text-slate-500 hover:text-slate-900">
                            <MoreVertical size={15} />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                          <DropdownMenuLabel>Aksi Pengajuan</DropdownMenuLabel>
                          <DropdownMenuItem onClick={() => setSelectedRequest(req)}>
                            <Eye className="mr-2 h-4 w-4 text-emerald-600" />
                            <span>Buka Detail</span>
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => {
                            navigator.clipboard.writeText(req.request_number);
                            showToast(`Nomor ${req.request_number} berhasil disalin!`);
                          }}>
                            <Copy className="mr-2 h-4 w-4 text-blue-600" />
                            <span>Salin No. Request</span>
                          </DropdownMenuItem>
                          {req.status === 'pending' && (
                            <>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem
                                onClick={() => handleCancel(req.id)}
                                className="text-rose-600 focus:bg-rose-50 focus:text-rose-700"
                              >
                                <Trash2 className="mr-2 h-4 w-4" />
                                <span>Batalkan Pengajuan</span>
                              </DropdownMenuItem>
                            </>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          ) : (
            <div className="p-12 rounded-3xl bg-white border border-slate-200 text-center text-slate-400 space-y-2">
              <History size={40} className="mx-auto opacity-40 text-slate-400" />
              <p className="text-xs font-bold">Tidak ada pengajuan cuti yang ditemukan.</p>
            </div>
          )}
        </div>

        {/* Modal Detail Request (100% Mobile Responsive, Seamless Full Backdrop) */}
        <AnimatePresence>
          {selectedRequest && (
            <div className="fixed inset-0 z-[100] flex items-end sm:items-start sm:items-center justify-center sm:p-4">
              {/* Full-screen Dark Backdrop Overlay */}
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.15 }}
                className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm"
                onClick={() => setSelectedRequest(null)}
              />

              {/* Modal Box Container */}
              <motion.div
                initial={{ opacity: 0, y: 30, scale: 0.96 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: 30, scale: 0.96 }}
                transition={{ type: 'spring', stiffness: 380, damping: 30 }}
                className="relative z-10 w-full max-w-lg p-5 sm:p-6 rounded-t-3xl sm:rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4 max-h-[calc(100dvh-3rem)] sm:max-h-[85vh] flex flex-col my-auto sm:my-auto"
              >

                {/* Header */}
                <div className="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
                  <div className="flex items-center space-x-2.5">
                    <div className="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                      <FileText size={20} />
                    </div>
                    <div>
                      <h3 className="text-base font-black text-slate-900 leading-tight">Detail Pengajuan Cuti</h3>
                      <p className="text-[11px] text-slate-400 font-medium">Informasi lengkap permohonan ketidakhadiran</p>
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => setSelectedRequest(null)}
                    className="p-2 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800 transition-colors"
                  >
                    <X size={18} />
                  </button>
                </div>

                {/* Body Content - Scrollable */}
                <div className="space-y-3 overflow-y-auto flex-1 pr-1 text-xs">

                  {/* Status Header Banner */}
                  <div className={`p-4 rounded-2xl border flex items-center justify-between ${
                    selectedRequest.status === 'approved' ? 'bg-emerald-50/80 border-emerald-200 text-emerald-950' :
                    selectedRequest.status === 'rejected' ? 'bg-rose-50/80 border-rose-200 text-rose-950' :
                    'bg-amber-50/80 border-amber-200 text-amber-950'
                  }`}>
                    <div>
                      <span className="text-[10px] font-extrabold uppercase tracking-wider block opacity-70">Status & Tahapan Saat Ini</span>
                      <p className="text-sm font-black mt-0.5">
                        {selectedRequest.status === 'approved' ? '✅ Disetujui (Approved)' :
                         selectedRequest.status === 'rejected' ? '❌ Ditolak (Rejected)' :
                         `⏳ Pending (${
                            selectedRequest.current_stage === 'approval_1' ? 'Menunggu Approval 1 - Supervisor' :
                            selectedRequest.current_stage === 'approval_2' ? 'Menunggu Approval 2 - Manager' :
                            'Menunggu Approval Akhir HRD'
                         })`}
                      </p>
                    </div>
                    <span className="font-mono text-xs font-black px-3 py-1 rounded-full bg-white/80 border shadow-sm">
                      {selectedRequest.request_number}
                    </span>
                  </div>

                  {/* Multi-Tier Approval Progress Stepper */}
                  <div className="p-4 rounded-2xl border border-slate-200 bg-slate-50 space-y-2.5">
                    <span className="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider block">
                      Progress Persetujuan Bertingkat (Approval Timeline)
                    </span>

                    <div className="space-y-2">
                      {/* Tier 1 */}
                      <div className="flex items-start space-x-2.5">
                        <div className={`w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5 ${
                          selectedRequest.approved_by_1 ? 'bg-emerald-600 text-white' :
                          selectedRequest.current_stage === 'approval_1' && selectedRequest.status === 'pending' ? 'bg-amber-500 text-white' :
                          'bg-slate-200 text-slate-500'
                        }`}>
                          {selectedRequest.approved_by_1 ? '✓' : '1'}
                        </div>
                        <div className="text-[11px] min-w-0 flex-1">
                          <p className="font-bold text-slate-900">
                            Tingkat 1: Atasan 1 (Supervisor / Lead)
                            {selectedRequest.approver1 && <span className="text-emerald-700 font-extrabold ml-1">&bull; {selectedRequest.approver1.name} (Disetujui)</span>}
                          </p>
                          {selectedRequest.approval_1_note && (
                            <p className="text-slate-600 italic mt-0.5">&ldquo;{selectedRequest.approval_1_note}&rdquo;</p>
                          )}
                        </div>
                      </div>

                      {/* Tier 2 */}
                      <div className="flex items-start space-x-2.5">
                        <div className={`w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5 ${
                          selectedRequest.approved_by_2 ? 'bg-emerald-600 text-white' :
                          selectedRequest.current_stage === 'approval_2' && selectedRequest.status === 'pending' ? 'bg-amber-500 text-white' :
                          'bg-slate-200 text-slate-500'
                        }`}>
                          {selectedRequest.approved_by_2 ? '✓' : '2'}
                        </div>
                        <div className="text-[11px] min-w-0 flex-1">
                          <p className="font-bold text-slate-900">
                            Tingkat 2: Atasan 2 (Manager / Dept Head)
                            {selectedRequest.approver2 && <span className="text-emerald-700 font-extrabold ml-1">&bull; {selectedRequest.approver2.name} (Disetujui)</span>}
                          </p>
                          {selectedRequest.approval_2_note && (
                            <p className="text-slate-600 italic mt-0.5">&ldquo;{selectedRequest.approval_2_note}&rdquo;</p>
                          )}
                        </div>
                      </div>

                      {/* Tier 3: HRD */}
                      <div className="flex items-start space-x-2.5">
                        <div className={`w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5 ${
                          selectedRequest.approved_by_hrd || (selectedRequest.status === 'approved' && !selectedRequest.approved_by_1 && !selectedRequest.approved_by_2) ? 'bg-emerald-600 text-white' :
                          selectedRequest.current_stage === 'hrd' && selectedRequest.status === 'pending' ? 'bg-amber-500 text-white' :
                          'bg-slate-200 text-slate-500'
                        }`}>
                          {selectedRequest.status === 'approved' ? '✓' : '3'}
                        </div>
                        <div className="text-[11px] min-w-0 flex-1">
                          <p className="font-bold text-slate-900">
                            Tingkat 3: HRD / PGA Admin (Final)
                            {selectedRequest.status === 'approved' && <span className="text-emerald-700 font-extrabold ml-1">&bull; Disetujui Final</span>}
                          </p>
                          {selectedRequest.approval_note && (
                            <p className="text-slate-600 italic mt-0.5">&ldquo;{selectedRequest.approval_note}&rdquo;</p>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Data Cards Grid */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50">
                      <span className="text-[10px] font-extrabold uppercase text-slate-400 block mb-0.5">Pemohon / NIK</span>
                      <p className="font-extrabold text-slate-900">{selectedRequest.user?.name || currentUser.name || 'Budi Santoso'}</p>
                      <p className="text-[11px] text-slate-500 font-medium">{selectedRequest.user?.nik || currentUser.nik || 'EMP-201'}</p>
                    </div>

                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50">
                      <span className="text-[10px] font-extrabold uppercase text-slate-400 block mb-0.5">Sifat & Kategori</span>
                      <p className="font-extrabold text-slate-900">{selectedRequest.category?.name}</p>
                      <p className="text-[11px] text-emerald-700 font-bold">{selectedRequest.submission_type}</p>
                    </div>

                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50">
                      <span className="text-[10px] font-extrabold uppercase text-slate-400 block mb-0.5">Periode Cuti</span>
                      <p className="font-extrabold text-slate-900">{selectedRequest.start_date} s/d {selectedRequest.end_date}</p>
                    </div>

                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50">
                      <span className="text-[10px] font-extrabold uppercase text-slate-400 block mb-0.5">Total Durasi</span>
                      <p className="font-extrabold text-slate-900">{selectedRequest.amount} {selectedRequest.unit}</p>
                    </div>
                  </div>

                  {/* Alasan Permohonan */}
                  <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50 space-y-1">
                    <span className="text-[10px] font-extrabold uppercase text-slate-400 block">Detail Alasan Permohonan:</span>
                    <p className="font-medium text-slate-800 bg-white p-3 rounded-xl border border-slate-200 leading-relaxed text-xs">
                      {selectedRequest.reason}
                    </p>
                  </div>

                  {/* Catatan Peninjau / Manager */}
                  {selectedRequest.approval_note && (
                    <div className="p-3.5 rounded-2xl border border-amber-200 bg-amber-50/50 space-y-1">
                      <span className="text-[10px] font-extrabold uppercase text-amber-800 block">Catatan Persetujuan Manager / HRD:</span>
                      <p className="font-semibold text-slate-900 bg-white p-3 rounded-xl border border-amber-200 leading-relaxed text-xs">
                        {selectedRequest.approval_note}
                      </p>
                    </div>
                  )}

                  {/* File Lampiran */}
                  {selectedRequest.attachment_path && (
                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between">
                      <div>
                        <span className="text-[10px] font-extrabold uppercase text-slate-400 block">File Lampiran Dokumen</span>
                        <p className="text-xs font-bold text-slate-700">Surat Keterangan / Bukti Tambahan</p>
                      </div>
                      <a
                        href={`/storage/${selectedRequest.attachment_path}`}
                        target="_blank"
                        rel="noreferrer"
                        className="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-sm transition-colors"
                      >
                        <Paperclip size={14} />
                        <span>Buka File</span>
                      </a>
                    </div>
                  )}
                </div>

                {/* Footer Buttons */}
                <div className="pt-3 border-t border-slate-100 shrink-0">
                  <button
                    type="button"
                    onClick={() => setSelectedRequest(null)}
                    className="w-full py-3.5 rounded-2xl bg-[#0FA172] hover:bg-[#1CB67C] text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 transition-all"
                  >
                    Tutup Detail
                  </button>
                </div>

              </motion.div>
            </div>
          )}
        </AnimatePresence>

      </div>
    </AuthenticatedLayout>
  );
}
