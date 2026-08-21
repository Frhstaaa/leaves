import React, { useState } from 'react';
import { useForm, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { motion, AnimatePresence } from 'framer-motion';
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

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";

export default function ApprovalsIndex({ requests, departments = [], filters = {}, isHrdAdmin = false }) {
  const [activeModal, setActiveModal] = useState(null); // 'approve', 'reject', or null
  const [selectedReq, setSelectedReq] = useState(null);
  const [approvalNote, setApprovalNote] = useState('');
  const [errorMsg, setErrorMsg] = useState('');
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [selectedDept, setSelectedDept] = useState(filters.department_id || '');

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
    router.get(route('approvals.index'), {
      status,
      department_id: selectedDept,
      search: searchQuery,
    }, { preserveState: true });
  };

  const handleSearchSubmit = (e) => {
    e?.preventDefault();
    router.get(route('approvals.index'), {
      status: filters.status || 'pending',
      department_id: selectedDept,
      search: searchQuery,
    }, { preserveState: true });
  };

  return (
    <AuthenticatedLayout title="Persetujuan Cuti Tim (Approval Center)">
      <div className="space-y-6">
        {/* Header Title */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-black text-slate-900">Panel Persetujuan Cuti (Approval Center)</h2>
            <p className="text-xs text-slate-500">
              {isHrdAdmin ? 'Pengawasan dan persetujuan pengajuan cuti seluruh departemen' : 'Kelola dan berikan persetujuan permohonan tidak bekerja tim Anda'}
            </p>
          </div>

          {/* HRD / Admin Filter & Search */}
          {isHrdAdmin && (
            <form onSubmit={handleSearchSubmit} className="flex flex-wrap items-center gap-2">
              <div className="w-[180px]">
                <Select
                  value={selectedDept ? String(selectedDept) : 'all'}
                  onValueChange={(val) => {
                    const newDept = val === 'all' ? '' : val;
                    setSelectedDept(newDept);
                    router.get(route('approvals.index'), {
                      status: filters.status || 'pending',
                      department_id: newDept,
                      search: searchQuery,
                    }, { preserveState: true });
                  }}
                >
                  <SelectTrigger className="bg-white border-slate-200 text-xs font-semibold h-9 rounded-xl">
                    <SelectValue placeholder="Semua Departemen" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Semua Departemen</SelectItem>
                    {departments.map((d) => (
                      <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Cari NIK / Nama..."
                className="px-3 py-2 rounded-xl bg-white border border-slate-200 text-xs font-medium text-slate-800 shadow-sm outline-none focus:border-emerald-500"
              />
              <Button
                type="submit"
                variant="default"
                size="sm"
                className="rounded-xl font-bold text-xs"
              >
                Cari
              </Button>
            </form>
          )}
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
              filters.status === 'all'
                ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                : 'text-slate-700 hover:text-slate-900 bg-white border border-slate-200'
            }`}
          >
            <span>📋 Semua Data</span>
          </button>
        </div>

        {/* HRD Stage Sub-filter (When viewing Pending) */}
        {isHrdAdmin && (filters.status === 'pending' || !filters.status) && (
          <div className="flex items-center space-x-2 overflow-x-auto pb-1 text-xs">
            <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider shrink-0">Filter Tahap:</span>
            {[
              { label: '🎯 Menunggu HRD (Final)', stage: 'hrd' },
              { label: '1️⃣ Menunggu Atasan 1', stage: 'approval_1' },
              { label: '2️⃣ Menunggu Atasan 2', stage: 'approval_2' },
              { label: '🌐 Semua Tahap Pending', stage: 'all' },
            ].map((st) => {
              const isActive = (filters.stage === st.stage) || (!filters.stage && st.stage === 'hrd');
              return (
                <button
                  key={st.stage}
                  type="button"
                  onClick={() => {
                    router.get(route('approvals.index'), {
                      status: 'pending',
                      stage: st.stage,
                      department_id: selectedDept,
                      search: searchQuery,
                    }, { preserveState: true });
                  }}
                  className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 ${
                    isActive
                      ? 'bg-slate-900 text-white shadow-xs'
                      : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
                  }`}
                >
                  {st.label}
                </button>
              );
            })}
          </div>
        )}

        {/* Requests Table & Mobile Cards */}
        <div className="p-4 sm:p-6 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-sm">
          {requests.data && requests.data.length > 0 ? (
            <div>
              {/* Mobile Card View (< md) */}
              <div className="block md:hidden space-y-3">
                {requests.data.map((req, index) => {
                  const stageBadge = req.current_stage === 'approval_1'
                    ? { label: 'Tingkat 1 (Supervisor)', cls: 'bg-blue-100 text-blue-800 border-blue-200' }
                    : req.current_stage === 'approval_2'
                    ? { label: 'Tingkat 2 (Manager)', cls: 'bg-purple-100 text-purple-800 border-purple-200' }
                    : { label: 'HRD / PGA Admin', cls: 'bg-amber-100 text-amber-800 border-amber-200' };

                  return (
                    <motion.div
                      key={req.id}
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ duration: 0.25, delay: Math.min(index * 0.04, 0.3), ease: 'easeOut' }}
                      whileHover={{ y: -2, transition: { duration: 0.15 } }}
                      className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3 shadow-xs"
                    >
                      <div className="flex items-center justify-between">
                        <span className="font-mono text-xs font-bold text-teal-700">{req.request_number}</span>
                        <div className="flex items-center space-x-1.5">
                          {req.status === 'pending' && (
                            <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black border ${stageBadge.cls}`}>
                              {stageBadge.label}
                            </span>
                          )}
                          <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ${
                            req.status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                            req.status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200' :
                            'bg-amber-100 text-amber-800 border border-amber-200'
                          }`}>
                            {req.status === 'approved' ? 'Disetujui' : req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                          </span>
                        </div>
                      </div>

                      <div className="space-y-1">
                        <p className="font-extrabold text-sm text-slate-900">{req.user?.name}</p>
                        <p className="text-[11px] text-slate-500">{req.user?.nik} &bull; {req.user?.department?.name || 'General'}</p>
                        <p className="text-xs font-bold text-teal-700 pt-1">{req.category?.name} {req.submission_type ? `(${req.submission_type})` : ''}</p>
                        <p className="text-xs text-slate-700">{req.start_date} s/d {req.end_date} &bull; <strong className="text-slate-900">{req.amount} {req.unit}</strong></p>
                      </div>

                      {/* Previous Multi-Tier Approval Logs */}
                      {(req.approved_by_1 || req.approved_by_2) && (
                        <div className="p-2.5 rounded-xl bg-white border border-slate-200 text-[11px] space-y-1 text-slate-600">
                          {req.approver1 && (
                            <p className="text-blue-800 font-semibold">
                              &bull; <strong>Tingkat 1 Disetujui:</strong> {req.approver1.name} {req.approval_1_note ? `("${req.approval_1_note}")` : ''}
                            </p>
                          )}
                          {req.approver2 && (
                            <p className="text-purple-800 font-semibold">
                              &bull; <strong>Tingkat 2 Disetujui:</strong> {req.approver2.name} {req.approval_2_note ? `("${req.approval_2_note}")` : ''}
                            </p>
                          )}
                        </div>
                      )}

                      <div className="pt-2 border-t border-slate-200 flex items-center justify-end space-x-2">
                        {req.status === 'pending' ? (
                          <>
                            <button
                              onClick={() => handleOpenApproveModal(req)}
                              className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center space-x-1 shadow-sm transition-transform active:scale-95"
                            >
                              <CheckCircle size={14} />
                              <span>Setujui</span>
                            </button>
                            <button
                              onClick={() => handleOpenRejectModal(req)}
                              className="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold flex items-center space-x-1 hover:bg-rose-100 transition-transform active:scale-95"
                            >
                              <XCircle size={14} />
                              <span>Tolak</span>
                            </button>
                          </>
                        ) : (
                          <span className="text-xs text-slate-400 italic">Selesai Ditinjau</span>
                        )}
                      </div>
                    </motion.div>
                  );
                })}
              </div>

              {/* Desktop Table View (>= md) */}
              <div className="hidden md:block overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead>
                    <tr className="border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                      <th className="pb-3.5">No Request & Pemohon</th>
                      <th className="pb-3.5">Kategori & Alasan</th>
                      <th className="pb-3.5">Periode Cuti</th>
                      <th className="pb-3.5">Tahapan & Status</th>
                      <th className="pb-3.5">Riwayat Persetujuan</th>
                      <th className="pb-3.5 text-right">Keputusan Approval</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 text-slate-700">
                    {requests.data.map((req) => {
                      const stageBadge = req.current_stage === 'approval_1'
                        ? { label: 'Tingkat 1 (Supervisor)', cls: 'bg-blue-100 text-blue-800 border-blue-200' }
                        : req.current_stage === 'approval_2'
                        ? { label: 'Tingkat 2 (Manager)', cls: 'bg-purple-100 text-purple-800 border-purple-200' }
                        : { label: 'HRD / PGA Admin', cls: 'bg-amber-100 text-amber-800 border-amber-200' };

                      return (
                        <tr key={req.id} className="hover:bg-slate-50 transition-colors">
                          <td className="py-4">
                            <span className="font-mono text-xs font-bold text-teal-700 block">{req.request_number}</span>
                            <span className="font-bold text-slate-900 text-sm block mt-0.5">{req.user?.name}</span>
                            <span className="text-[11px] text-slate-500 block">{req.user?.nik} &bull; {req.user?.department?.name || 'General'}</span>
                          </td>
                          <td className="py-4 max-w-xs">
                            <span className="font-bold text-slate-900 block">{req.category?.name}</span>
                            <p className="text-xs text-slate-600 truncate mt-0.5">{req.reason}</p>
                            {req.attachment_path && (
                              <a
                                href={`/storage/${req.attachment_path}`}
                                target="_blank"
                                rel="noreferrer"
                                className="text-teal-700 font-bold hover:underline text-xs block mt-1"
                              >
                                &bull; Lihat Lampiran
                              </a>
                            )}
                          </td>
                          <td className="py-4 text-xs font-medium text-slate-600">
                            <div>{req.start_date} s/d {req.end_date}</div>
                            <div className="font-bold text-slate-900 mt-0.5">{req.amount} {req.unit}</div>
                          </td>
                          <td className="py-4 space-y-1">
                            {req.status === 'pending' && (
                              <div>
                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black border ${stageBadge.cls}`}>
                                  {stageBadge.label}
                                </span>
                              </div>
                            )}
                            <div>
                              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${
                                req.status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                                req.status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200' :
                                'bg-amber-100 text-amber-800 border border-amber-200'
                              }`}>
                                {req.status === 'approved' ? 'Disetujui' :
                                 req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                              </span>
                            </div>
                          </td>
                          <td className="py-4 text-xs">
                            <div className="space-y-1 max-w-[200px]">
                              {req.approver1 && (
                                <p className="text-blue-800 font-semibold truncate">
                                  ✓ T1: {req.approver1.name}
                                </p>
                              )}
                              {req.approver2 && (
                                <p className="text-purple-800 font-semibold truncate">
                                  ✓ T2: {req.approver2.name}
                                </p>
                              )}
                              {req.approverHrd && (
                                <p className="text-emerald-800 font-bold truncate">
                                  ✓ HRD: {req.approverHrd.name}
                                </p>
                              )}
                              {!req.approver1 && !req.approver2 && !req.approverHrd && (
                                <span className="text-slate-400">-</span>
                              )}
                            </div>
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
                      );
                    })}
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
        <AnimatePresence>
          {activeModal === 'approve' && selectedReq && (
            <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.15 }}
                className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm"
                onClick={() => setActiveModal(null)}
              />
              <motion.div
                initial={{ opacity: 0, scale: 0.95, y: 15 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 15 }}
                transition={{ type: 'spring', stiffness: 400, damping: 30 }}
                className="relative z-10 w-full max-w-md p-6 rounded-3xl bg-white border border-slate-200 shadow-2xl space-y-4"
              >
                <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                  <h3 className="text-base font-extrabold text-slate-900 flex items-center space-x-2">
                    <CheckCircle size={20} className="text-emerald-600" />
                    <span>
                      {selectedReq.current_stage === 'approval_1' ? 'Persetujuan Tingkat 1 (Supervisor)' :
                       selectedReq.current_stage === 'approval_2' ? 'Persetujuan Tingkat 2 (Manager)' :
                       'Persetujuan Akhir HRD / PGA Admin'}
                    </span>
                  </h3>
                  <button onClick={() => setActiveModal(null)} className="p-1 rounded-lg hover:bg-slate-100 text-slate-400">
                    <X size={18} />
                  </button>
                </div>

                <div className="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-950 space-y-1">
                  <p className="font-extrabold text-sm">{selectedReq.user?.name} ({selectedReq.user?.nik})</p>
                  <p className="text-emerald-800">
                    Kategori: <strong>{selectedReq.category?.name}</strong> &bull; Jumlah: <strong>{selectedReq.amount} {selectedReq.unit}</strong>
                  </p>
                  <p className="text-[11px] text-emerald-700">Periode: {selectedReq.start_date} s/d {selectedReq.end_date}</p>
                </div>

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
                    className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 active:scale-95 transition-all"
                  >
                    Konfirmasi Setuju
                  </button>
                </div>
              </motion.div>
            </div>
          )}
        </AnimatePresence>

        {/* Reject Modal */}
        <AnimatePresence>
          {activeModal === 'reject' && selectedReq && (
            <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.15 }}
                className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm"
                onClick={() => setActiveModal(null)}
              />
              <motion.div
                initial={{ opacity: 0, scale: 0.95, y: 15 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 15 }}
                transition={{ type: 'spring', stiffness: 400, damping: 30 }}
                className="relative z-10 w-full max-w-md p-6 rounded-3xl bg-white border border-slate-200 shadow-2xl space-y-4"
              >
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
                    className="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs shadow-md shadow-rose-600/20 active:scale-95 transition-all"
                  >
                    Konfirmasi Tolak
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
