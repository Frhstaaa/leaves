import React from 'react';
import { useForm, router, usePage } from '@inertiajs/react';
import { Lock, Mail, ArrowRight, ShieldCheck, User, Briefcase, Smartphone } from 'lucide-react';
import PwaInstallModal from '@/components/PwaInstallModal';

export default function Login() {
  const { app_settings } = usePage().props;
  const appName = app_settings?.app_name || 'Form SGIN';
  const appSubname = app_settings?.app_subname || 'Cuti & Ketidakhadiran';
  const appLogo = app_settings?.app_logo
    ? (app_settings.app_logo.startsWith('http') ? app_settings.app_logo : `/storage/${app_settings.app_logo}`)
    : null;

  const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
    remember: false,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post('/login');
  };

  const handleQuickLogin = (role) => {
    router.post(route('quick-login'), { role });
  };

  return (
    <main className="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-center items-center px-4 py-12 relative overflow-hidden font-sans">
      <div className="w-full max-w-md z-10">
        {/* Header Logo & Title */}
        <div className="text-center mb-8">
          {appLogo ? (
            <img
              src={appLogo}
              alt={appName}
              className="w-16 h-16 rounded-2xl object-cover shadow-lg shadow-teal-600/25 border-2 border-white mx-auto mb-4"
            />
          ) : (
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-teal-600 to-emerald-500 text-white font-black text-2xl shadow-lg shadow-teal-600/30 mb-4">
              SG
            </div>
          )}
          <h1 className="text-2xl font-black tracking-tight text-slate-900">{appName}</h1>
          <p className="text-sm text-slate-500 mt-1">{appSubname}</p>
        </div>

        {/* Quick Demo Login Preset Buttons */}
        <div className="mb-6 p-4 rounded-2xl bg-white border border-teal-200 shadow-sm">
          <p className="text-xs font-bold text-teal-700 uppercase tracking-wider text-center mb-3">
            ⚡ Quick Demo Switcher (Masuk Instan)
          </p>
          <div className="grid grid-cols-4 gap-2">
            <button
              type="button"
              onClick={() => handleQuickLogin('employee')}
              className="flex flex-col items-center justify-center p-2.5 rounded-xl bg-slate-50 hover:bg-teal-50 border border-slate-200 hover:border-teal-300 text-slate-800 transition-all duration-200 text-xs font-bold"
            >
              <User size={18} className="text-teal-600 mb-1" />
              <span>Karyawan</span>
            </button>

            <button
              type="button"
              onClick={() => handleQuickLogin('manager')}
              className="flex flex-col items-center justify-center p-2.5 rounded-xl bg-slate-50 hover:bg-teal-50 border border-slate-200 hover:border-teal-300 text-slate-800 transition-all duration-200 text-xs font-bold"
            >
              <Briefcase size={18} className="text-blue-600 mb-1" />
              <span>Manager</span>
            </button>

            <button
              type="button"
              onClick={() => handleQuickLogin('admin')}
              className="flex flex-col items-center justify-center p-2.5 rounded-xl bg-slate-50 hover:bg-teal-50 border border-slate-200 hover:border-teal-300 text-slate-800 transition-all duration-200 text-xs font-bold"
            >
              <ShieldCheck size={18} className="text-purple-600 mb-1" />
              <span>HRD Admin</span>
            </button>

            <button
              type="button"
              onClick={() => handleQuickLogin('superadmin')}
              className="flex flex-col items-center justify-center p-2.5 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-200 hover:border-amber-400 text-slate-900 transition-all duration-200 text-xs font-bold"
            >
              <ShieldCheck size={18} className="text-amber-600 mb-1" />
              <span className="text-[11px]">Super Admin</span>
            </button>
          </div>
        </div>

        {/* Main Login Form Card */}
        <div className="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-md">
          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Email Perusahaan
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                  <Mail size={18} />
                </div>
                <input
                  type="email"
                  value={data.email}
                  onChange={(e) => setData('email', e.target.value)}
                  placeholder="karyawan@sgin.com"
                  className="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 placeholder-slate-400 text-sm font-semibold focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all outline-none"
                  required
                />
              </div>
              {errors.email && (
                <p className="mt-1.5 text-xs text-rose-600 font-bold">{errors.email}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Password
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                  <Lock size={18} />
                </div>
                <input
                  type="password"
                  value={data.password}
                  onChange={(e) => setData('password', e.target.value)}
                  placeholder="••••••••"
                  className="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 placeholder-slate-400 text-sm font-semibold focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all outline-none"
                  required
                />
              </div>
              {errors.password && (
                <p className="mt-1.5 text-xs text-rose-600 font-bold">{errors.password}</p>
              )}
            </div>

            <button
              type="submit"
              disabled={processing}
              className="w-full py-3.5 px-4 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-black text-sm shadow-md shadow-teal-600/20 flex items-center justify-center space-x-2 transition-all disabled:opacity-50 mt-2"
            >
              <span>Masuk Ke Sistem</span>
              <ArrowRight size={18} />
            </button>
          </form>
        </div>
      </div>

      {/* PWA Floating Install Modal */}
      <PwaInstallModal />
    </main>
  );
}
