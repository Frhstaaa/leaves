import React, { useState } from 'react';
import { router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
  FileText
} from 'lucide-react';

export default function HrdIndex({ requests, departments, categories, filters }) {
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [selectedDept, setSelectedDept] = useState(filters.department_id || '');
  const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

  const handleApplyFilter = (e) => {
    e?.preventDefault();
    router.get(route('hrd.index'), {
      search: searchQuery,
      department_id: selectedDept,
      status: selectedStatus,
    }, { preserveState: true });
  };

  const handleResetFilter = () => {
    setSearchQuery('');
    setSelectedDept('');
    setSelectedStatus('');
    router.get(route('hrd.index'));
  };

  return (
    <AuthenticatedLayout title="Rekapitulasi Cuti HRD / PGA">
      <div className="space-y-6">
        {/* Header & Export Button */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-extrabold text-white">Rekapitulasi Ketidakhadiran & Cuti Karyawan</h2>
            <p className="text-xs text-slate-400">Monitoring terpusat seluruh pengajuan permohonan tidak bekerja</p>
          </div>

          <a
            href={route('hrd.export')}
            className="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center space-x-2 transition-all duration-200 self-start md:self-auto"
          >
            <Download size={16} />
            <span>Export Rekapitulasi (CSV/Excel)</span>
          </a>
        </div>

        {/* Search & Filter Bar */}
        <div className="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl backdrop-blur-md">
          <form onSubmit={handleApplyFilter} className="grid grid-cols-1 md:grid-cols-4 gap-3">
            {/* Search Input */}
            <div>
              <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Cari Karyawan / No Request</label>
              <div className="relative">
                <Search size={16} className="absolute left-3 top-3 text-slate-500" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Nama, NIK, atau CUTI-..."
                  className="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-xs focus:border-indigo-500 outline-none"
                />
              </div>
            </div>

            {/* Department Filter */}
            <div>
              <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Filter Departemen</label>
              <select
                value={selectedDept}
                onChange={(e) => setSelectedDept(e.target.value)}
                className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-xs focus:border-indigo-500 outline-none"
              >
                <option value="">Semua Departemen</option>
                {departments.map((dept) => (
                  <option key={dept.id} value={dept.id}>{dept.name}</option>
                ))}
              </select>
            </div>

            {/* Status Filter */}
            <div>
              <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Filter Status Approval</label>
              <select
                value={selectedStatus}
                onChange={(e) => setSelectedStatus(e.target.value)}
                className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-xs focus:border-indigo-500 outline-none"
              >
                <option value="">Semua Status</option>
                <option value="pending">Pending Approval</option>
                <option value="approved">Disetujui (Approved)</option>
                <option value="rejected">Ditolak (Rejected)</option>
              </select>
            </div>

            {/* Action Buttons */}
            <div className="flex items-end space-x-2">
              <button
                type="submit"
                className="flex-1 py-2 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition-all"
              >
                Terapkan Filter
              </button>
              <button
                type="button"
                onClick={handleResetFilter}
                className="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition-all"
              >
                Reset
              </button>
            </div>
          </form>
        </div>

        {/* Master Table List */}
        <div className="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-2xl">
          {requests.data && requests.data.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead>
                  <tr className="border-b border-slate-800 text-xs font-bold text-slate-400 uppercase tracking-wider">
                    <th className="pb-3.5">No Request</th>
                    <th className="pb-3.5">Karyawan & NIK</th>
                    <th className="pb-3.5">Departemen</th>
                    <th className="pb-3.5">Kategori Cuti</th>
                    <th className="pb-3.5">Periode</th>
                    <th className="pb-3.5">Jumlah</th>
                    <th className="pb-3.5">Status</th>
                    <th className="pb-3.5">Approver</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/60 text-slate-300">
                  {requests.data.map((req) => (
                    <tr key={req.id} className="hover:bg-slate-800/40 transition-colors">
                      <td className="py-3.5 font-mono text-xs font-bold text-indigo-300">{req.request_number}</td>
                      <td className="py-3.5">
                        <span className="font-bold text-white text-sm block">{req.user?.name}</span>
                        <span className="text-[11px] text-slate-400 block">{req.user?.nik}</span>
                      </td>
                      <td className="py-3.5 text-xs text-slate-300 font-medium">
                        {req.user?.department?.name || 'General'}
                      </td>
                      <td className="py-3.5 text-xs font-semibold text-slate-200">
                        {req.category?.name}
                      </td>
                      <td className="py-3.5 text-xs text-slate-400">
                        {req.start_date} s/d {req.end_date}
                      </td>
                      <td className="py-3.5 font-bold text-slate-100">
                        {req.amount} {req.unit}
                      </td>
                      <td className="py-3.5">
                        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold ${
                          req.status === 'approved' ? 'badge-approved' :
                          req.status === 'rejected' ? 'badge-rejected' : 'badge-pending'
                        }`}>
                          {req.status === 'approved' ? 'Disetujui' :
                           req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                        </span>
                      </td>
                      <td className="py-3.5 text-xs text-slate-400">
                        {req.approver ? req.approver.name : '-'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="py-16 text-center text-slate-500">
              <FileSpreadsheet size={42} className="mx-auto mb-3 opacity-30" />
              <p className="text-sm font-semibold">Tidak ada rekapitulasi data yang cocok dengan kriteria pencarian.</p>
            </div>
          )}
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
