import React, { useState } from 'react';
import { router, Link } from '@inertiajs/react';
import AuthenticatedLayout, { UserAvatar } from '@/Layouts/AuthenticatedLayout';
import {
  FileSpreadsheet,
  Download,
  Filter,
  Search,
  Users,
  Building,
  Calendar,
  CheckCircle,
  XCircle,
  Clock,
  FileText,
  Eye,
  X,
  Paperclip,
  Tag,
  AlertCircle,
  Zap,
  Sliders,
  Check,
  RotateCcw,
  Sparkles,
  ShieldCheck,
  ChevronRight
} from 'lucide-react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { showConfirm, showToast } from '@/Utils/swal';

export default function HrdIndex({ requests = { data: [] }, departments = [], categories = [], stats = {}, filters = {} }) {
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [selectedDept, setSelectedDept] = useState(filters.department_id || '');
  const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
  const [selectedCategory, setSelectedCategory] = useState(filters.category_id || '');

  // Detail Modal State
  const [selectedRequest, setSelectedRequest] = useState(null);

  // Manual Status Override Modal State
  const [overrideRequest, setOverrideRequest] = useState(null);
  const [overrideStatusVal, setOverrideStatusVal] = useState('approved');
  const [overrideStageVal, setOverrideStageVal] = useState('hrd');
  const [overrideNote, setOverrideNote] = useState('');
  const [overrideProcessing, setOverrideProcessing] = useState(false);

  const handleApplyFilter = (e) => {
    e?.preventDefault();
    router.get(route('hrd.index'), {
      search: searchQuery,
      department_id: selectedDept,
      status: selectedStatus,
      category_id: selectedCategory,
    }, { preserveState: true });
  };

  const handleResetFilter = () => {
    setSearchQuery('');
    setSelectedDept('');
    setSelectedStatus('');
    setSelectedCategory('');
    router.get(route('hrd.index'));
  };

  // Quick 1-Click Force Approve
  const handleQuickApprove = async (req) => {
    const confirmed = await showConfirm({
      title: 'Setujui Pengajuan Langsung?',
      text: `Pengajuan ${req.request_number} milik ${req.user?.name} akan langsung disetujui (Approved) dan kuota cuti otomatis diperbarui.`,
      icon: 'question',
      confirmText: 'Ya, Setujui Langsung!',
      cancelText: 'Batal',
    });
    if (confirmed) {
      router.post(route('hrd.requests.override', req.id), {
        status: 'approved',
        stage: 'completed',
        note: 'Disetujui langsung oleh HRD / PGA Admin via 1-Click Action',
      }, {
        preserveScroll: true,
        onSuccess: () => {
          showToast('Pengajuan berhasil disetujui langsung!', 'success');
          if (selectedRequest?.id === req.id) setSelectedRequest(null);
          if (overrideRequest?.id === req.id) setOverrideRequest(null);
        },
        onError: () => {
          showToast('Gagal memproses persetujuan.', 'error');
        }
      });
    }
  };

  // Quick 1-Click Force Reject
  const handleQuickReject = async (req) => {
    const confirmed = await showConfirm({
      title: 'Tolak Pengajuan Ini?',
      text: `Pengajuan ${req.request_number} milik ${req.user?.name} akan diubah statusnya menjadi Ditolak (Rejected).`,
      icon: 'warning',
      confirmText: 'Ya, Tolak!',
      cancelText: 'Batal',
    });
    if (confirmed) {
      router.post(route('hrd.requests.override', req.id), {
        status: 'rejected',
        note: 'Ditolak oleh HRD / PGA Admin via 1-Click Action',
      }, {
        preserveScroll: true,
        onSuccess: () => {
          showToast('Pengajuan berhasil ditolak.', 'info');
          if (selectedRequest?.id === req.id) setSelectedRequest(null);
          if (overrideRequest?.id === req.id) setOverrideRequest(null);
        },
        onError: () => {
          showToast('Gagal memproses penolakan.', 'error');
        }
      });
    }
  };

  // Open Full Override Modal
  const handleOpenOverrideModal = (req) => {
    setOverrideRequest(req);
    setOverrideStatusVal(req.status || 'approved');
    setOverrideStageVal(req.current_stage || 'hrd');
    setOverrideNote('');
  };

  // Submit Manual Status Override Form
  const handleSaveOverride = (e) => {
    e.preventDefault();
    if (!overrideRequest) return;
    setOverrideProcessing(true);
    router.post(route('hrd.requests.override', overrideRequest.id), {
      status: overrideStatusVal,
      stage: overrideStageVal,
      note: overrideNote,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        showToast('Status pengajuan berhasil diperbarui secara manual!', 'success');
        setOverrideRequest(null);
        if (selectedRequest?.id === overrideRequest.id) setSelectedRequest(null);
        setOverrideProcessing(false);
      },
      onError: () => {
        showToast('Gagal memperbarui status pengajuan.', 'error');
        setOverrideProcessing(false);
      }
    });
  };

  const getStageBadge = (req) => {
    if (req.status === 'approved') {
      return (
        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
          ✅ Disetujui (Selesai)
        </span>
      );
    }
    if (req.status === 'rejected') {
      return (
        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300">
          ❌ Ditolak
        </span>
      );
    }

    // Pending
    switch (req.current_stage) {
      case 'approval_1':
        return (
          <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
            ⏳ Menunggu Atasan 1
          </span>
        );
      case 'approval_2':
        return (
          <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-900 border border-indigo-300">
            ⏳ Menunggu Atasan 2
          </span>
        );
      case 'hrd':
      default:
        return (
          <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-900 border border-purple-300">
            ⏳ Menunggu HRD
          </span>
        );
    }
  };

  return (
    <AuthenticatedLayout title="Rekapitulasi Cuti HRD / PGA">
      <div className="space-y-6">

        {/* Page Header & Export Button */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-black text-slate-900 tracking-tight">Rekapitulasi Ketidakhadiran & Cuti Karyawan</h2>
            <p className="text-xs text-slate-500">Monitoring terpusat, alur multi-tier approval, dan panel override status pengajuan langsung</p>
          </div>

          <a
            href={route('hrd.export')}
            className="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs shadow-lg shadow-emerald-600/20 flex items-center space-x-2 transition-all duration-200 self-start md:self-auto"
          >
            <Download size={16} />
            <span>Export Rekapitulasi (CSV/Excel)</span>
          </a>
        </div>

        {/* Summary Statistics Cards */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
          <div className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">
              <FileText size={20} />
            </div>
            <div>
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Pengajuan</span>
              <span className="text-lg font-black text-slate-900">{stats.total || requests.data?.length || 0}</span>
            </div>
          </div>

          <div className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold shrink-0">
              <Clock size={20} />
            </div>
            <div>
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Pending Approval</span>
              <span className="text-lg font-black text-amber-700">{stats.pending || 0}</span>
            </div>
          </div>

          <div className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">
              <CheckCircle size={20} />
            </div>
            <div>
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Disetujui (Approved)</span>
              <span className="text-lg font-black text-emerald-700">{stats.approved || 0}</span>
            </div>
          </div>

          <div className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold shrink-0">
              <XCircle size={20} />
            </div>
            <div>
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Ditolak (Rejected)</span>
              <span className="text-lg font-black text-rose-700">{stats.rejected || 0}</span>
            </div>
          </div>
        </div>

        {/* Search & Filter Bar */}
        <div className="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-sm">
          <form onSubmit={handleApplyFilter} className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            {/* Search Input */}
            <div>
              <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cari Karyawan / Request</label>
              <div className="relative">
                <Search size={16} className="absolute left-3 top-2.5 text-slate-400" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Nama, NIK, atau CUTI-..."
                  className="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 text-xs font-medium focus:bg-white focus:border-emerald-500 outline-none transition-all"
                />
              </div>
            </div>

            {/* Department Filter */}
            <div>
              <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Departemen</label>
              <Select
                value={selectedDept ? String(selectedDept) : 'all'}
                onValueChange={(val) => setSelectedDept(val === 'all' ? '' : val)}
              >
                <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-slate-800 text-xs font-semibold rounded-xl">
                  <SelectValue placeholder="Semua Departemen" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Departemen</SelectItem>
                  {departments.map((dept) => (
                    <SelectItem key={dept.id} value={String(dept.id)}>{dept.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Status Filter */}
            <div>
              <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Status</label>
              <Select
                value={selectedStatus || 'all'}
                onValueChange={(val) => setSelectedStatus(val === 'all' ? '' : val)}
              >
                <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-slate-800 text-xs font-semibold rounded-xl">
                  <SelectValue placeholder="Semua Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Status</SelectItem>
                  <SelectItem value="pending">⏳ Pending Approval</SelectItem>
                  <SelectItem value="approved">✅ Disetujui (Approved)</SelectItem>
                  <SelectItem value="rejected">❌ Ditolak (Rejected)</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Action Buttons */}
            <div className="flex items-end space-x-2">
              <button
                type="submit"
                className="flex-1 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 transition-all"
              >
                Terapkan Filter
              </button>
              <button
                type="button"
                onClick={handleResetFilter}
                className="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition-all"
              >
                Reset
              </button>
            </div>
          </form>
        </div>

        {/* Master Table List */}
        <div className="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
          {requests.data && requests.data.length > 0 ? (
            <div>
              {/* MOBILE RECAP CARD VIEW (< md) */}
              <div className="block md:hidden divide-y divide-slate-100">
                {requests.data.map((req) => (
                  <div
                    key={req.id}
                    onClick={() => setSelectedRequest(req)}
                    className="p-4 space-y-3 hover:bg-slate-50/80 transition-colors cursor-pointer"
                  >
                    <div className="flex items-center justify-between">
                      <span className="font-mono text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        {req.request_number}
                      </span>
                      {getStageBadge(req)}
                    </div>

                    <div className="flex items-center space-x-3">
                      <UserAvatar user={req.user} size="w-10 h-10" textSize="text-xs" />
                      <div className="min-w-0 flex-1">
                        <h4 className="font-extrabold text-slate-900 text-sm truncate">{req.user?.name}</h4>
                        <p className="text-[11px] text-slate-500 font-medium truncate">{req.user?.nik} &bull; {req.user?.department?.name || 'General'}</p>
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-2 text-xs pt-1">
                      <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 min-w-0">
                        <span className="text-[10px] text-slate-400 font-bold uppercase block">Kategori Cuti</span>
                        <span className="font-bold text-slate-900 truncate block">{req.category?.name || 'Cuti'}</span>
                      </div>

                      <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 min-w-0">
                        <span className="text-[10px] text-slate-400 font-bold uppercase block">Durasi</span>
                        <span className="font-extrabold text-emerald-600 truncate block">{req.amount} {req.unit || 'hari'}</span>
                      </div>
                    </div>

                    <div className="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                      <span className="text-slate-500 font-medium text-[11px]">{req.start_date} s/d {req.end_date}</span>

                      {/* Action Group */}
                      <div className="flex items-center space-x-1.5">
                        {req.status === 'pending' && (
                          <>
                            <button
                              type="button"
                              onClick={(e) => {
                                e.stopPropagation();
                                handleQuickApprove(req);
                              }}
                              className="p-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs"
                              title="⚡ Setujui Langsung (1-Klik)"
                            >
                              <Check size={14} />
                            </button>
                            <button
                              type="button"
                              onClick={(e) => {
                                e.stopPropagation();
                                handleQuickReject(req);
                              }}
                              className="p-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs"
                              title="❌ Tolak Pengajuan (1-Klik)"
                            >
                              <X size={14} />
                            </button>
                          </>
                        )}
                        <button
                          type="button"
                          onClick={(e) => {
                            e.stopPropagation();
                            handleOpenOverrideModal(req);
                          }}
                          className="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center space-x-1 border border-slate-200"
                          title="Edit Status Manual"
                        >
                          <Sliders size={13} />
                          <span>Status</span>
                        </button>
                        <button
                          type="button"
                          onClick={(e) => {
                            e.stopPropagation();
                            setSelectedRequest(req);
                          }}
                          className="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center space-x-1 border border-emerald-200"
                        >
                          <Eye size={13} />
                          <span>Detail</span>
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {/* DESKTOP RECAP TABLE VIEW (>= md) */}
              <div className="hidden md:block overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead>
                    <tr className="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                      <th className="py-3.5 px-4">No Request</th>
                      <th className="py-3.5 px-4">Karyawan & NIK</th>
                      <th className="py-3.5 px-4">Departemen</th>
                      <th className="py-3.5 px-4">Kategori Cuti</th>
                      <th className="py-3.5 px-4">Periode & Durasi</th>
                      <th className="py-3.5 px-4">Status & Tahapan</th>
                      <th className="py-3.5 px-4 text-center">Aksi Override & Detail</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 text-slate-700">
                    {requests.data.map((req) => (
                      <tr
                        key={req.id}
                        onClick={() => setSelectedRequest(req)}
                        className="hover:bg-slate-50/80 cursor-pointer transition-colors"
                      >
                        <td className="py-3.5 px-4 font-mono font-bold text-emerald-700">
                          {req.request_number}
                        </td>

                        <td className="py-3.5 px-4">
                          <div className="flex items-center space-x-3">
                            <UserAvatar user={req.user} size="w-8 h-8" textSize="text-xs" />
                            <div>
                              <span className="font-extrabold text-slate-900 text-xs block">{req.user?.name}</span>
                              <span className="text-[10px] text-slate-500 font-mono block">{req.user?.nik}</span>
                            </div>
                          </div>
                        </td>

                        <td className="py-3.5 px-4 text-slate-700 font-medium">
                          {req.user?.department?.name || 'General'}
                        </td>

                        <td className="py-3.5 px-4">
                          <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            {req.category?.name || 'Cuti'}
                          </span>
                        </td>

                        <td className="py-3.5 px-4">
                          <div className="text-slate-900 font-medium">
                            {req.start_date} s/d {req.end_date}
                          </div>
                          <span className="text-[10px] font-bold text-emerald-700 block mt-0.5">
                            Total: {req.amount} {req.unit || 'hari'}
                          </span>
                        </td>

                        <td className="py-3.5 px-4">
                          {getStageBadge(req)}
                        </td>

                        <td className="py-3.5 px-4 text-center">
                          <div className="flex items-center justify-center space-x-1.5" onClick={(e) => e.stopPropagation()}>
                            {req.status === 'pending' && (
                              <>
                                <button
                                  type="button"
                                  onClick={() => handleQuickApprove(req)}
                                  className="p-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-all active:scale-95"
                                  title="⚡ Setujui Langsung (1-Klik)"
                                >
                                  <Check size={14} />
                                </button>
                                <button
                                  type="button"
                                  onClick={() => handleQuickReject(req)}
                                  className="p-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-all active:scale-95"
                                  title="❌ Tolak Pengajuan (1-Klik)"
                                >
                                  <X size={14} />
                                </button>
                              </>
                            )}

                            <button
                              type="button"
                              onClick={() => handleOpenOverrideModal(req)}
                              className="p-1.5 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors border border-slate-200 inline-flex items-center space-x-1 font-bold"
                              title="Edit Status & Stage Manual"
                            >
                              <Sliders size={13} />
                              <span className="text-[11px]">Edit Status</span>
                            </button>

                            <button
                              type="button"
                              onClick={() => setSelectedRequest(req)}
                              className="p-1.5 px-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition-colors border border-emerald-200 inline-flex items-center space-x-1 font-bold"
                            >
                              <Eye size={13} />
                              <span className="text-[11px]">Detail</span>
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          ) : (
            <div className="py-16 text-center text-slate-400">
              <FileSpreadsheet size={42} className="mx-auto mb-3 opacity-30 text-slate-500" />
              <p className="text-xs font-bold text-slate-600">Tidak ada rekapitulasi data yang sesuai kriteria pencarian.</p>
            </div>
          )}
        </div>

        {/* ========================================================================= */}
        {/* MODAL 1: DETAIL PENGURUSAN UNTUK HRD                                      */}
        {/* ========================================================================= */}
        {selectedRequest && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 animate-fade-in">
            <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" onClick={() => setSelectedRequest(null)} />
            <div className="relative z-10 w-full max-w-lg p-6 rounded-3xl bg-white border border-slate-200 shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto">

              {/* Modal Header */}
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                  <span className="text-[10px] font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                    {selectedRequest.request_number}
                  </span>
                  <h3 className="text-base font-extrabold text-slate-900 mt-1">Detail Pengajuan Cuti</h3>
                </div>
                <button onClick={() => setSelectedRequest(null)} className="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                  <X size={18} />
                </button>
              </div>

              {/* Status Banner */}
              <div className={`p-4 rounded-2xl border flex items-center space-x-3 ${
                selectedRequest.status === 'approved' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' :
                selectedRequest.status === 'rejected' ? 'bg-rose-50 border-rose-200 text-rose-900' :
                'bg-amber-50 border-amber-200 text-amber-900'
              }`}>
                {selectedRequest.status === 'approved' && <CheckCircle size={24} className="text-emerald-600 shrink-0" />}
                {selectedRequest.status === 'rejected' && <XCircle size={24} className="text-rose-600 shrink-0" />}
                {selectedRequest.status === 'pending' && <Clock size={24} className="text-amber-600 shrink-0" />}
                <div>
                  <h4 className="text-xs font-black uppercase tracking-wider">
                    {selectedRequest.status === 'approved' ? 'Pengajuan Disetujui (Completed)' :
                     selectedRequest.status === 'rejected' ? 'Pengajuan Ditolak' :
                     `Pending Approval (${(selectedRequest.current_stage || 'hrd').toUpperCase()})`}
                  </h4>
                  <p className="text-[11px] opacity-90 mt-0.5">
                    {selectedRequest.status === 'approved' ? `Disetujui oleh ${selectedRequest.approver?.name || 'HRD / Admin'}` :
                     selectedRequest.status === 'rejected' ? `Ditolak oleh ${selectedRequest.approver?.name || 'Approver'}` :
                     `Menunggu persetujuan pada tahapan ${selectedRequest.current_stage || 'hrd'}`}
                  </p>
                </div>
              </div>

              {/* Employee & Category Information */}
              <div className="grid grid-cols-2 gap-3 text-xs">
                <div className="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pemohon</span>
                  <div className="flex items-center space-x-2">
                    <UserAvatar user={selectedRequest.user} size="w-7 h-7" textSize="text-xs" />
                    <div>
                      <h5 className="font-bold text-slate-900">{selectedRequest.user?.name}</h5>
                      <p className="text-[10px] text-slate-500">{selectedRequest.user?.nik}</p>
                    </div>
                  </div>
                </div>

                <div className="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kategori Cuti</span>
                  <h5 className="font-bold text-slate-900">{selectedRequest.category?.name || 'Cuti'}</h5>
                  <p className="text-[10px] text-slate-500">Departemen: {selectedRequest.user?.department?.name || '-'}</p>
                </div>
              </div>

              {/* Date & Duration Info */}
              <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                <div className="flex justify-between items-center border-b border-slate-200/60 pb-2">
                  <span className="text-slate-500 font-medium">Tanggal Mulai</span>
                  <span className="font-bold text-slate-900">{selectedRequest.start_date}</span>
                </div>
                <div className="flex justify-between items-center border-b border-slate-200/60 pb-2">
                  <span className="text-slate-500 font-medium">Tanggal Selesai</span>
                  <span className="font-bold text-slate-900">{selectedRequest.end_date}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-slate-500 font-medium">Jumlah Durasi</span>
                  <span className="font-extrabold text-emerald-700">{selectedRequest.amount} {selectedRequest.unit || 'hari'}</span>
                </div>
              </div>

              {/* Reason / Alasan */}
              <div className="space-y-1 text-xs">
                <label className="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Alasan Permohonan</label>
                <div className="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-800 leading-relaxed">
                  {selectedRequest.reason}
                </div>
              </div>

              {/* Approver Note */}
              {selectedRequest.approval_note && (
                <div className="space-y-1 text-xs">
                  <label className="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Catatan Approver</label>
                  <div className="p-3 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 leading-relaxed font-medium">
                    "{selectedRequest.approval_note}"
                  </div>
                </div>
              )}

              {/* Attachment File Link */}
              {selectedRequest.attachment_path && (
                <div className="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                  <div className="flex items-center space-x-2 min-w-0">
                    <Paperclip size={16} className="text-emerald-600 shrink-0" />
                    <span className="font-bold text-slate-800 truncate">Lampiran Dokumen</span>
                  </div>
                  <a
                    href={`/storage/${selectedRequest.attachment_path}`}
                    target="_blank"
                    rel="noreferrer"
                    className="px-3 py-1.5 rounded-xl bg-emerald-600 text-white font-bold text-[11px] shadow-sm hover:bg-emerald-700 transition-colors"
                  >
                    Unduh Dokumen
                  </a>
                </div>
              )}

              {/* Quick Actions inside Detail Modal */}
              <div className="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center gap-2">
                <button
                  type="button"
                  onClick={() => handleOpenOverrideModal(selectedRequest)}
                  className="w-full sm:w-1/2 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs flex items-center justify-center space-x-1.5 transition-colors"
                >
                  <Sliders size={14} />
                  <span>Edit Status Manual</span>
                </button>

                {selectedRequest.status === 'pending' ? (
                  <button
                    type="button"
                    onClick={() => handleQuickApprove(selectedRequest)}
                    className="w-full sm:w-1/2 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs flex items-center justify-center space-x-1.5 shadow-md shadow-emerald-600/20 transition-all"
                  >
                    <Check size={14} />
                    <span>⚡ Setujui Langsung</span>
                  </button>
                ) : (
                  <button
                    type="button"
                    onClick={() => setSelectedRequest(null)}
                    className="w-full sm:w-1/2 py-2.5 rounded-xl bg-slate-950 text-white font-extrabold text-xs"
                  >
                    Tutup
                  </button>
                )}
              </div>
            </div>
          </div>
        )}

        {/* ========================================================================= */}
        {/* MODAL 2: MANUAL STATUS OVERRIDE PANEL (PILIHAN 2 OPSI & KUSTOMISASI)      */}
        {/* ========================================================================= */}
        {overrideRequest && (
          <div className="fixed inset-0 z-[110] flex items-center justify-center p-4 animate-fade-in">
            <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" onClick={() => setOverrideRequest(null)} />
            <div className="relative z-10 w-full max-w-lg p-6 rounded-3xl bg-white border border-slate-200 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">

              {/* Header */}
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div className="flex items-center space-x-2.5">
                  <div className="p-2 rounded-xl bg-emerald-100 text-emerald-700 font-bold">
                    <Sliders size={18} />
                  </div>
                  <div>
                    <h3 className="text-base font-black text-slate-900 leading-tight">
                      Panel Edit Status Pengajuan
                    </h3>
                    <p className="text-xs text-slate-500">
                      Ubah status secara instan atau sesuaikan tahapan approval manual
                    </p>
                  </div>
                </div>
                <button onClick={() => setOverrideRequest(null)} className="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                  <X size={18} />
                </button>
              </div>

              {/* Request Summary Box */}
              <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-1.5">
                <div className="flex items-center justify-between">
                  <span className="font-mono font-bold text-emerald-700">{overrideRequest.request_number}</span>
                  {getStageBadge(overrideRequest)}
                </div>
                <p className="font-bold text-slate-800">
                  {overrideRequest.user?.name} ({overrideRequest.user?.nik || 'NIK: -'}) &bull; {overrideRequest.user?.department?.name || 'General'}
                </p>
                <p className="text-slate-600 text-[11px]">
                  {overrideRequest.category?.name} &bull; <strong>{overrideRequest.amount} {overrideRequest.unit || 'hari'}</strong> ({overrideRequest.start_date} s/d {overrideRequest.end_date})
                </p>
              </div>

              {/* 2 OPSI CEPAT SESUAI PERMINTAAN USER */}
              <div className="space-y-2">
                <label className="block text-[11px] font-extrabold text-slate-700 uppercase tracking-wider">
                  ⚡ Opsi Tindakan Cepat:
                </label>
                <div className="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => handleQuickApprove(overrideRequest)}
                    className="p-3 rounded-2xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-900 flex flex-col items-center justify-center space-y-1 transition-all active:scale-[0.98]"
                  >
                    <CheckCircle size={20} className="text-emerald-600" />
                    <span className="text-xs font-black">1. Setujui Langsung</span>
                    <span className="text-[10px] text-emerald-700">Bypass multi-tier & potong kuota</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => handleQuickReject(overrideRequest)}
                    className="p-3 rounded-2xl bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-900 flex flex-col items-center justify-center space-y-1 transition-all active:scale-[0.98]"
                  >
                    <XCircle size={20} className="text-rose-600" />
                    <span className="text-xs font-black">2. Tolak Pengajuan</span>
                    <span className="text-[10px] text-rose-700">Hentikan dan batalkan pengajuan</span>
                  </button>
                </div>
              </div>

              {/* OPSI KUSTOMISASI MANUAL STATUS & TAHAPAN */}
              <form onSubmit={handleSaveOverride} className="space-y-3 pt-2 border-t border-slate-100">
                <label className="block text-[11px] font-extrabold text-slate-700 uppercase tracking-wider">
                  🔧 Atau Atur Status & Tahapan Tertentu:
                </label>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Status Baru *</label>
                    <select
                      value={overrideStatusVal}
                      onChange={(e) => setOverrideStatusVal(e.target.value)}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
                    >
                      <option value="approved">✅ Disetujui (Approved)</option>
                      <option value="rejected">❌ Ditolak (Rejected)</option>
                      <option value="pending">⏳ Pending Approval</option>
                    </select>
                  </div>

                  {overrideStatusVal === 'pending' && (
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Tahapan / Stage *</label>
                      <select
                        value={overrideStageVal}
                        onChange={(e) => setOverrideStageVal(e.target.value)}
                        className="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
                      >
                        <option value="approval_1">Stage 1: Menunggu Atasan 1 (Supervisor)</option>
                        <option value="approval_2">Stage 2: Menunggu Atasan 2 (Manager)</option>
                        <option value="hrd">Stage 3: Menunggu HRD / PGA Admin</option>
                      </select>
                    </div>
                  )}
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">Catatan / Alasan Perubahan</label>
                  <textarea
                    rows={2}
                    value={overrideNote}
                    onChange={(e) => setOverrideNote(e.target.value)}
                    placeholder="Contoh: Disetujui khusus via memo direksi, revisi status, dll..."
                    className="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-none resize-none"
                  />
                </div>

                <div className="flex items-center justify-end space-x-2 pt-2">
                  <button
                    type="button"
                    onClick={() => setOverrideRequest(null)}
                    className="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={overrideProcessing}
                    className="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 transition-all active:scale-95"
                  >
                    {overrideProcessing ? 'Menyimpan...' : 'Simpan Perubahan Manual'}
                  </button>
                </div>
              </form>

            </div>
          </div>
        )}

      </div>
    </AuthenticatedLayout>
  );
}
