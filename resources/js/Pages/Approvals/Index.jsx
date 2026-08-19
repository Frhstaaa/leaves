import React, { useState } from 'react';
import { useForm, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
  CheckSquare,
  CheckCircle,
  XCircle,
  Clock,
  User,
  Building,
  Calendar,
  FileText,
  Download,
  MessageSquare,
  X
} from 'lucide-react';

export default function ApprovalsIndex({ requests, filters }) {
  const [activeModal, setActiveModal] = useState(null); // 'approve', 'reject', or null
  const [selectedReq, setSelectedReq] = useState(null);
  const [approvalNote, setApprovalNote] = useState('');
  const [errorMsg, setErrorMsg] = useState('');

  const handleOpenApproveModal = (req) => {
    setSelectedReq(req);
    setApprovalNote('');
    setErrorMsg('');
    setActiveModal('approve');
  };

  const handleOpenRejectModal = (req) => {
    setSelectedReq(req);
    setApprovalNote('');
    setErrorMsg('');
    setActiveModal('reject');
  };

  const handleSubmitApprove = (e) => {
    e.preventDefault();
    if (!selectedReq) return;

    router.post(route('approvals.approve', selectedReq.id), { note: approvalNote }, {
      onSuccess: () => {
        setActiveModal(null);
        setSelectedReq(null);
      }
    });
  };

  const handleSubmitReject = (e) => {
    e.preventDefault();
    if (!selectedReq) return;

    if (!approvalNote || approvalNote.trim().length < 3) {
      setErrorMsg('Alasan penolakan pengajuan wajib diisi.');
      return;
    }

    router.post(route('approvals.reject', selectedReq.id), { note: approvalNote }, {
      onSuccess: () => {
        setActiveModal(null);
        setSelectedReq(null);
      }
    });
  };

  const handleFilterStatus = (status) => {
    router.get(route('approvals.index'), { status }, { preserveState: true });
  };

  return (
    <AuthenticatedLayout title="Persetujuan Cuti Tim (Approval Center)">
      <div className="space-y-6">
        {/* Header Title */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-black text-slate-900">Panel Persetujuan Cuti (Approval Center)</h2>
            <p className="text-xs text-slate-500">Kelola dan berikan persetujuan permohonan tidak bekerja tim Anda</p>
          </div>
        </div>

        {/* Filter Status Tabs (2x2 Grid on Mobile for 100% Full Visibility & Horizontal Scroll on Desktop) */}
        <div className="w-full border-b border-slate-200 pb-3 grid grid-cols-2 gap-2 sm:flex sm:items-center sm:space-x-2 sm:overflow-x-auto sm:no-scrollbar">
          <button
            onClick={() => handleFilterStatus('pending')}
            className={`px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center flex items-center justify-center space-x-1.5 ${
              filters.status === 'pending'
                ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20'
                : 'text-slate-700 hover:text-slate-900 bg-white border border-slate-200'
            }`}
          >
            <span>⏳ Menunggu</span>
          </button>

          <button
            onClick={() => handleFilterStatus('approved')}
            className={`px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center flex items-center justify-center space-x-1.5 ${
              filters.status === 'approved'
                ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                : 'text-slate-700 hover:text-slate-900 bg-white border border-slate-200'
            }`}
          >
            <span>✅ Disetujui</span>
          </button>

          <button
            onClick={() => handleFilterStatus('rejected')}
            className={`px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center flex items-center justify-center space-x-1.5 ${
              filters.status === 'rejected'
                ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20'
                : 'text-slate-700 hover:text-slate-900 bg-white border border-slate-200'
            }`}
          >
            <span>❌ Ditolak</span>
          </button>

          <button
            onClick={() => handleFilterStatus('all')}
            className={`px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center flex items-center justify-center space-x-1.5 ${
              filters.status === 'all' || !filters.status
                ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                : 'text-slate-700 hover:text-slate-900 bg-white border border-slate-200'
            }`}
          >
            <span>📋 Semua Data</span>
          </button>
        </div>

        {/* Requests Table & Mobile Cards */}
        <div className="p-4 sm:p-6 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-sm">
          {requests.data && requests.data.length > 0 ? (
            <div>
              {/* Mobile Card View (< md) */}
              <div className="block md:hidden space-y-3">
                {requests.data.map((req) => (
                  <div key={req.id} className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="font-mono text-xs font-bold text-teal-700">{req.request_number}</span>
                      <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ${
                        req.status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                        req.status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200' :
                        'bg-amber-100 text-amber-800 border border-amber-200'
                      }`}>
                        {req.status === 'approved' ? 'Disetujui' : req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                      </span>
                    </div>

                    <div className="space-y-1">
                      <p className="font-extrabold text-sm text-slate-900">{req.user?.name}</p>
                      <p className="text-[11px] text-slate-500">{req.user?.nik} &bull; {req.user?.department?.name || 'General'}</p>
                      <p className="text-xs font-bold text-teal-700 pt-1">{req.category?.name} {req.submission_type ? `(${req.submission_type})` : ''}</p>
                      <p className="text-xs text-slate-700">{req.start_date} s/d {req.end_date} &bull; <strong className="text-slate-900">{req.amount} {req.unit}</strong></p>
                    </div>

                    <div className="pt-2 border-t border-slate-200 flex items-center justify-end space-x-2">
                      {req.status === 'pending' ? (
                        <>
                          <button
                            onClick={() => handleOpenApproveModal(req)}
                            className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center space-x-1 shadow-sm"
                          >
                            <CheckCircle size={14} />
                            <span>Setujui</span>
                          </button>
                          <button
                            onClick={() => handleOpenRejectModal(req)}
                            className="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold flex items-center space-x-1 hover:bg-rose-100"
                          >
                            <XCircle size={14} />
                            <span>Tolak</span>
                          </button>
                        </>
                      ) : (
                        <span className="text-xs text-slate-400 italic">Selesai Ditinjau</span>
                      )}
                    </div>
                  </div>
                ))}
              </div>

              {/* Desktop Table View (>= md) */}
              <div className="hidden md:block overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead>
                    <tr className="border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                      <th className="pb-3.5">No Request & Pemohon</th>
                      <th className="pb-3.5">Kategori & Alasan</th>
                      <th className="pb-3.5">Periode Cuti</th>
                      <th className="pb-3.5">Jumlah</th>
                      <th className="pb-3.5">Lampiran</th>
                      <th className="pb-3.5">Status</th>
                      <th className="pb-3.5 text-right">Keputusan Approval</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 text-slate-700">
                    {requests.data.map((req) => (
                      <tr key={req.id} className="hover:bg-slate-50 transition-colors">
                        <td className="py-4">
                          <span className="font-mono text-xs font-bold text-teal-700 block">{req.request_number}</span>
                          <span className="font-bold text-slate-900 text-sm block mt-0.5">{req.user?.name}</span>
                          <span className="text-[11px] text-slate-500 block">{req.user?.nik} &bull; {req.user?.department?.name || 'General'}</span>
                        </td>
                        <td className="py-4 max-w-xs">
                          <span className="font-bold text-slate-900 block">{req.category?.name}</span>
                          <p className="text-xs text-slate-600 truncate mt-0.5">{req.reason}</p>
                        </td>
                        <td className="py-4 text-xs font-medium text-slate-600">
                          {req.start_date} s/d {req.end_date}
                        </td>
                        <td className="py-4 font-bold text-slate-900">
                          {req.amount} {req.unit}
                        </td>
                        <td className="py-4 text-xs">
                          {req.attachment_path ? (
                            <a
                              href={`/storage/${req.attachment_path}`}
                              target="_blank"
                              rel="noreferrer"
                              className="text-teal-700 font-bold hover:underline"
                            >
                              File Lampiran
                            </a>
                          ) : (
                            <span className="text-slate-400">-</span>
                          )}
                        </td>
                        <td className="py-4">
                          <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold ${
                            req.status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                            req.status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200' :
                            'bg-amber-100 text-amber-800 border border-amber-200'
                          }`}>
                            {req.status === 'approved' ? 'Disetujui' :
                             req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                          </span>
                        </td>
                        <td className="py-4 text-right">
                          {req.status === 'pending' ? (
                            <div className="flex items-center justify-end space-x-2">
                              <button
                                onClick={() => handleOpenApproveModal(req)}
                                className="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm flex items-center space-x-1"
                              >
                                <CheckCircle size={14} />
                                <span>Setujui</span>
                              </button>
                              <button
                                onClick={() => handleOpenRejectModal(req)}
                                className="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs flex items-center space-x-1"
                              >
                                <XCircle size={14} />
                                <span>Tolak</span>
                              </button>
                            </div>
                          ) : (
                            <span className="text-xs text-slate-400 italic">Selesai Ditinjau</span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          ) : (
            <div className="py-16 text-center text-slate-400">
              <CheckSquare size={42} className="mx-auto mb-3 opacity-40" />
              <p className="text-sm font-semibold">Tidak ada pengajuan cuti yang memerlukan persetujuan Anda saat ini.</p>
            </div>
          )}
        </div>

        {/* Approve Modal */}
        {activeModal === 'approve' && selectedReq && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
            <div className="w-full max-w-md p-6 rounded-3xl bg-white border border-slate-200 shadow-2xl space-y-4">
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 className="text-base font-extrabold text-slate-900 flex items-center space-x-2">
                  <CheckCircle size={20} className="text-emerald-600" />
                  <span>Setujui Pengajuan Cuti</span>
                </h3>
                <button onClick={() => setActiveModal(null)} className="p-1 rounded-lg hover:bg-slate-100 text-slate-400">
                  <X size={18} />
                </button>
              </div>

              <p className="text-xs text-slate-600 leading-relaxed">
                Anda akan menyetujui pengajuan cuti dari <strong>{selectedReq.user?.name}</strong> sejumlah <strong>{selectedReq.amount} {selectedReq.unit}</strong>.
              </p>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Catatan Persetujuan (Opsional)</label>
                <textarea
                  rows={3}
                  value={approvalNote}
                  onChange={(e) => setApprovalNote(e.target.value)}
                  placeholder="Tambahkan catatan persetujuan jika ada..."
                  className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                />
              </div>

              <div className="flex items-center justify-end space-x-2 pt-2">
                <button
                  type="button"
                  onClick={() => setActiveModal(null)}
                  className="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200"
                >
                  Batal
                </button>
                <button
                  type="button"
                  onClick={handleSubmitApprove}
                  className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20"
                >
                  Konfirmasi Setuju
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Reject Modal */}
        {activeModal === 'reject' && selectedReq && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
            <div className="w-full max-w-md p-6 rounded-3xl bg-white border border-slate-200 shadow-2xl space-y-4">
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 className="text-base font-extrabold text-slate-900 flex items-center space-x-2">
                  <XCircle size={20} className="text-rose-600" />
                  <span>Tolak Pengajuan Cuti</span>
                </h3>
                <button onClick={() => setActiveModal(null)} className="p-1 rounded-lg hover:bg-slate-100 text-slate-400">
                  <X size={18} />
                </button>
              </div>

              <p className="text-xs text-slate-600 leading-relaxed">
                Anda akan menolak pengajuan cuti dari <strong>{selectedReq.user?.name}</strong>. Silakan isi alasan penolakan di bawah ini.
              </p>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Alasan Penolakan (Wajib) <span className="text-rose-600">*</span></label>
                <textarea
                  rows={3}
                  value={approvalNote}
                  onChange={(e) => {
                    setApprovalNote(e.target.value);
                    setErrorMsg('');
                  }}
                  placeholder="Isikan alasan spesifik penolakan pengajuan..."
                  className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-600 outline-none"
                  required
                />
                {errorMsg && <p className="mt-1 text-xs text-rose-600 font-bold">{errorMsg}</p>}
              </div>

              <div className="flex items-center justify-end space-x-2 pt-2">
                <button
                  type="button"
                  onClick={() => setActiveModal(null)}
                  className="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200"
                >
                  Batal
                </button>
                <button
                  type="button"
                  onClick={handleSubmitReject}
                  className="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs shadow-md shadow-rose-600/20"
                >
                  Konfirmasi Tolak
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
