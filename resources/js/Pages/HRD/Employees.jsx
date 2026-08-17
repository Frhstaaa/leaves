import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Users, Edit3, Shield, User, Building, Calendar, Check, X } from 'lucide-react';

export default function HrdEmployees({ employees, departments, managers }) {
  const [selectedUser, setSelectedUser] = useState(null);
  const [newQuota, setNewQuota] = useState(12);

  const handleOpenQuotaModal = (emp) => {
    setSelectedUser(emp);
    setNewQuota(emp.current_quota?.total_quota || 12);
  };

  const handleSaveQuota = (e) => {
    e.preventDefault();
    if (!selectedUser) return;

    router.post(route('hrd.update-quota', selectedUser.id), {
      total_quota: newQuota
    }, {
      onSuccess: () => {
        setSelectedUser(null);
      }
    });
  };

  return (
    <AuthenticatedLayout title="Kelola Data Karyawan & Kuota Cuti">
      <div className="space-y-6">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-extrabold text-white">Master Data Karyawan & Pengaturan Kuota Cuti</h2>
            <p className="text-xs text-slate-400">Atur departemen, role, dan kuota cuti tahunan karyawan</p>
          </div>
        </div>

        <div className="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-2xl">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-slate-800 text-xs font-bold text-slate-400 uppercase tracking-wider">
                  <th className="pb-3.5">NIK & Karyawan</th>
                  <th className="pb-3.5">Email</th>
                  <th className="pb-3.5">Departemen</th>
                  <th className="pb-3.5">Role System</th>
                  <th className="pb-3.5">Atasan Direct</th>
                  <th className="pb-3.5">Kuota Cuti ({new Date().getFullYear()})</th>
                  <th className="pb-3.5 text-right">Aksi HRD</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60 text-slate-300">
                {employees.map((emp) => {
                  const quota = emp.current_quota;
                  return (
                    <tr key={emp.id} className="hover:bg-slate-800/40 transition-colors">
                      <td className="py-4">
                        <span className="font-mono text-xs text-indigo-300 font-bold block">{emp.nik || 'EMP-???'}</span>
                        <span className="font-bold text-white text-sm block mt-0.5">{emp.name}</span>
                      </td>
                      <td className="py-4 text-xs text-slate-300 font-medium">
                        {emp.email}
                      </td>
                      <td className="py-4 text-xs font-semibold text-slate-200">
                        {emp.department?.name || 'General'}
                      </td>
                      <td className="py-4">
                        <span className={`inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider ${
                          emp.role === 'admin' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' :
                          emp.role === 'manager' ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' :
                          'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                        }`}>
                          {emp.role}
                        </span>
                      </td>
                      <td className="py-4 text-xs text-slate-400">
                        {emp.manager?.name || '-'}
                      </td>
                      <td className="py-4">
                        <div className="text-xs font-bold text-white">
                          Sisa: <span className="text-emerald-400">{quota?.remaining_quota ?? 12}</span> / {quota?.total_quota ?? 12} Hari
                        </div>
                        <span className="text-[10px] text-slate-500 block">Terkai: {quota?.used_quota ?? 0} Hari</span>
                      </td>
                      <td className="py-4 text-right">
                        <button
                          onClick={() => handleOpenQuotaModal(emp)}
                          className="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 hover:text-white text-xs font-bold transition-all border border-indigo-500/30 flex items-center space-x-1 ml-auto"
                        >
                          <Edit3 size={14} />
                          <span>Edit Kuota</span>
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>

        {/* Modal Update Kuota Cuti */}
        {selectedUser && (
          <div className="fixed inset-y-0 inset-x-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in">
            <div className="w-full max-w-md p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl space-y-5">
              <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 className="text-base font-bold text-white">Update Kuota Cuti Tahunan</h3>
                <button onClick={() => setSelectedUser(null)} className="p-1 rounded-lg text-slate-400 hover:text-white">
                  <X size={18} />
                </button>
              </div>

              <div className="text-xs text-slate-300 space-y-2">
                <p className="font-medium">Karyawan: <strong className="text-white">{selectedUser.name}</strong> ({selectedUser.nik})</p>
                <p className="text-slate-400">Departemen: {selectedUser.department?.name || 'General'}</p>
              </div>

              <form onSubmit={handleSaveQuota} className="space-y-4">
                <div>
                  <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Total Jatah Kuota Cuti (Hari / Tahun)
                  </label>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    value={newQuota}
                    onChange={(e) => setNewQuota(e.target.value)}
                    className="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 font-bold text-sm focus:border-indigo-500 outline-none"
                    required
                  />
                  <p className="text-[11px] text-slate-500 mt-1">Sisa kuota akan dihitung ulang secara otomatis berdasarkan cuti terpakai.</p>
                </div>

                <div className="flex items-center justify-end space-x-2 pt-2">
                  <button
                    type="button"
                    onClick={() => setSelectedUser(null)}
                    className="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    className="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30"
                  >
                    Simpan Perubahan
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
