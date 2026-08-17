import React, { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
  Eye
} from 'lucide-react';

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

  const handleCancel = (id) => {
    if (confirm('Apakah Anda yakin ingin membatalkan pengajuan cuti ini?')) {
      router.delete(route('leave-requests.destroy', id));
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

            <select
              value={status}
              onChange={(e) => setStatus(e.target.value)}
              className="px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
            >
              <option value="">Semua Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Disetujui</option>
              <option value="rejected">Ditolak</option>
            </select>

            <button
              type="submit"
              className="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition-colors shrink-0"
            >
              Filter Data
            </button>
          </form>
        </div>

        {/* Request List */}
        <div className="space-y-3">
          {requests?.data && requests.data.length > 0 ? (
            <div className="space-y-3">
              {requests.data.map((req) => (
                <div
                  key={req.id}
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

                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase shrink-0 ${
                      req.status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                      req.status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200' :
                      'bg-amber-100 text-amber-800 border border-amber-200'
                    }`}>
                      {req.status === 'approved' ? 'Disetujui' : req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                    </span>
                  </div>

                  <p className="text-xs text-slate-600 bg-slate-50 p-3 rounded-2xl border border-slate-100 font-medium line-clamp-2">
                    {req.reason}
                  </p>

                  <div className="flex items-center justify-between pt-1">
                    <div className="flex items-center space-x-2 text-[11px] text-slate-400 font-medium">
                      <Clock size={13} />
                      <span>{new Date(req.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
                    </div>

                    <div className="flex items-center space-x-2">
                      <button
                        type="button"
                        onClick={() => setSelectedRequest(req)}
                        className="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center space-x-1 transition-colors"
                      >
                        <Eye size={14} />
                        <span>Lihat Detail</span>
                      </button>

                      {req.status === 'pending' && (
                        <button
                          type="button"
                          onClick={() => handleCancel(req.id)}
                          className="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold flex items-center space-x-1 border border-rose-200 transition-colors"
                        >
                          <Trash2 size={14} />
                          <span>Batal</span>
                        </button>
                      )}
                    </div>
                  </div>
                </div>
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
        {selectedRequest && (
          <div className="fixed inset-0 z-[100] flex items-end sm:items-center justify-center sm:p-4 animate-fade-in">
            {/* Full-screen Dark Backdrop Overlay (0px Gap) */}
            <div
              className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"
              onClick={() => setSelectedRequest(null)}
            />

            {/* Modal Box Container */}
            <div className="relative z-10 w-full max-w-lg p-5 sm:p-6 rounded-t-3xl sm:rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">

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
                    <span className="text-[10px] font-extrabold uppercase tracking-wider block opacity-70">Status Pengajuan</span>
                    <p className="text-sm font-black mt-0.5">
                      {selectedRequest.status === 'approved' ? '✅ Disetujui' :
                       selectedRequest.status === 'rejected' ? '❌ Ditolak' : '⏳ Pending (Menunggu Persetujuan Manager)'}
                    </p>
                  </div>
                  <span className="font-mono text-xs font-black px-3 py-1 rounded-full bg-white/80 border shadow-sm">
                    {selectedRequest.request_number}
                  </span>
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

            </div>
          </div>
        )}

      </div>
    </AuthenticatedLayout>
  );
}
