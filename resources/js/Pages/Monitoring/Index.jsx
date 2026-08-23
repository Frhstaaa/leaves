import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer,
  PieChart, Pie, Cell, LineChart, Line
} from 'recharts';
import {
  Activity, Users, Clock, CheckCircle, Search, Calendar as CalendarIcon, 
  BarChart3, PieChart as PieChartIcon, TrendingUp, Download, Building2, Briefcase
} from 'lucide-react';

const COLORS = ['#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'];

export default function MonitoringIndex({ metrics, statusDistribution, monthlyTrend, categoryBreakdown, departmentBreakdown, currentYear }) {
  const [selectedYear, setSelectedYear] = useState(currentYear);

  const handleYearChange = (e) => {
    setSelectedYear(e.target.value);
    router.get(route('monitoring.index'), { year: e.target.value }, { preserveState: true });
  };

  // Format Status for PieChart
  const statusData = statusDistribution.map(item => ({
    name: item.status === 'approved' ? 'Disetujui' : item.status === 'rejected' ? 'Ditolak' : 'Menunggu',
    value: item.total
  }));

  return (
    <AuthenticatedLayout title="Monitoring & Analytics">
      <Head title="Enterprise Monitoring" />

      <div className="space-y-6">
        {/* Header & Year Filter */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
              <Activity className="text-emerald-600" size={24} />
              Enterprise Monitoring Dashboard
            </h2>
            <p className="text-xs text-slate-500 mt-1">Pusat kendali dan analitik data pengajuan karyawan SGIN.</p>
          </div>
          <div className="flex items-center space-x-3">
            <div className="relative">
              <CalendarIcon className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
              <select
                value={selectedYear}
                onChange={handleYearChange}
                className="pl-9 pr-8 py-2 rounded-xl bg-white border border-slate-200 text-sm font-bold text-slate-800 shadow-sm focus:ring-2 focus:ring-emerald-500 outline-none"
              >
                <option value={currentYear}>{currentYear}</option>
                <option value={currentYear - 1}>{currentYear - 1}</option>
                <option value={currentYear - 2}>{currentYear - 2}</option>
              </select>
            </div>
            <Link
              href={route('monitoring.annual-report', { year: selectedYear })}
              className="px-4 py-2 bg-emerald-600 text-white text-xs font-black rounded-xl shadow-md shadow-emerald-600/20 hover:bg-emerald-700 transition-colors flex items-center gap-2"
            >
              <CalendarIcon size={14} />
              <span>Laporan Matrix Cuti 1 Tahun</span>
            </Link>
            <a href={route('hrd.export')} className="px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl shadow-sm hover:bg-slate-800 transition-colors flex items-center gap-2">
              <Download size={14} />
              Export Rekap
            </a>
          </div>
        </div>

        {/* Executive Summary Cards */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
            <div className="flex items-center space-x-3 mb-3">
              <div className="p-2.5 rounded-xl bg-blue-50 text-blue-600"><Clock size={20} /></div>
              <span className="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Avg. SLA Waktu</span>
            </div>
            <div className="flex items-end gap-2">
              <h3 className="text-3xl font-black text-slate-900">{metrics.averageSlaHours}</h3>
              <span className="text-sm font-bold text-slate-400 mb-1">Jam</span>
            </div>
            <p className="text-[10px] text-slate-400 mt-1">Rata-rata waktu persetujuan</p>
          </div>

          <div className="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
            <div className="flex items-center space-x-3 mb-3">
              <div className="p-2.5 rounded-xl bg-emerald-50 text-emerald-600"><Briefcase size={20} /></div>
              <span className="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Total Pengajuan</span>
            </div>
            <h3 className="text-3xl font-black text-slate-900">{metrics.totalRequests}</h3>
            <p className="text-[10px] text-slate-400 mt-1">Sepanjang tahun {selectedYear}</p>
          </div>

          <div className="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
            <div className="flex items-center space-x-3 mb-3">
              <div className="p-2.5 rounded-xl bg-purple-50 text-purple-600"><Users size={20} /></div>
              <span className="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Karyawan Aktif</span>
            </div>
            <h3 className="text-3xl font-black text-slate-900">{metrics.totalEmployees}</h3>
            <p className="text-[10px] text-slate-400 mt-1">Terdaftar di sistem</p>
          </div>

          <div className="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
            <div className="flex items-center space-x-3 mb-3">
              <div className="p-2.5 rounded-xl bg-amber-50 text-amber-600"><CalendarIcon size={20} /></div>
              <span className="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Cuti Hari Ini</span>
            </div>
            <h3 className="text-3xl font-black text-slate-900">{metrics.onLeaveToday}</h3>
            <p className="text-[10px] text-slate-400 mt-1">Karyawan sedang cuti</p>
          </div>
        </div>

        {/* Charts Row 1 */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Monthly Trend (Line Chart) */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h3 className="text-base font-bold text-slate-900">Tren Pengajuan Bulanan</h3>
                <p className="text-[11px] text-slate-500">Volume pengajuan per bulan di {selectedYear}</p>
              </div>
              <TrendingUp className="text-slate-300" size={24} />
            </div>
            <div className="h-64 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={monthlyTrend} margin={{ top: 5, right: 20, bottom: 5, left: -20 }}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                  <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} />
                  <RechartsTooltip contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                  <Line type="monotone" dataKey="total" stroke="#0ea5e9" strokeWidth={3} dot={{ r: 4, fill: '#0ea5e9', strokeWidth: 0 }} activeDot={{ r: 6 }} />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Category Breakdown (Pie Chart) */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h3 className="text-base font-bold text-slate-900">Distribusi Kategori Cuti</h3>
                <p className="text-[11px] text-slate-500">Berdasarkan jenis pengajuan</p>
              </div>
              <PieChartIcon className="text-slate-300" size={24} />
            </div>
            <div className="h-64 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={categoryBreakdown}
                    cx="50%"
                    cy="50%"
                    innerRadius={60}
                    outerRadius={90}
                    paddingAngle={5}
                    dataKey="value"
                  >
                    {categoryBreakdown.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <RechartsTooltip contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                  <Legend verticalAlign="bottom" height={36} iconType="circle" wrapperStyle={{ fontSize: '11px', fontWeight: 'bold', color: '#64748b' }} />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        {/* Charts Row 2 */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Department Breakdown (Bar Chart) */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h3 className="text-base font-bold text-slate-900">Pengajuan per Departemen</h3>
                <p className="text-[11px] text-slate-500">Volume pengajuan tiap divisi</p>
              </div>
              <Building2 className="text-slate-300" size={24} />
            </div>
            <div className="h-72 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={departmentBreakdown} layout="vertical" margin={{ top: 5, right: 20, bottom: 5, left: 10 }}>
                  <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="#e2e8f0" />
                  <XAxis type="number" tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} />
                  <YAxis dataKey="name" type="category" tick={{ fontSize: 10, fill: '#64748b' }} axisLine={false} tickLine={false} width={80} />
                  <RechartsTooltip contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                  <Bar dataKey="total" fill="#10b981" radius={[0, 4, 4, 0]} barSize={20} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Status Breakdown */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h3 className="text-base font-bold text-slate-900">Rasio Persetujuan (Status)</h3>
                <p className="text-[11px] text-slate-500">Approved vs Rejected vs Pending</p>
              </div>
              <BarChart3 className="text-slate-300" size={24} />
            </div>
            <div className="h-72 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={statusData}
                    cx="50%"
                    cy="50%"
                    outerRadius={100}
                    dataKey="value"
                    labelLine={false}
                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                  >
                    {statusData.map((entry, index) => {
                      let color = '#f59e0b'; // pending
                      if (entry.name === 'Disetujui') color = '#10b981';
                      if (entry.name === 'Ditolak') color = '#ef4444';
                      return <Cell key={`cell-${index}`} fill={color} />;
                    })}
                  </Pie>
                  <RechartsTooltip contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

      </div>
    </AuthenticatedLayout>
  );
}
