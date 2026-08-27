import React, { useState } from 'react';
import { useForm, usePage, Head } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Lock,
  Mail,
  ArrowRight,
  ShieldCheck,
  Smartphone,
  Sparkles,
  Building2,
  CheckCircle2,
  Eye,
  EyeOff,
  FileText,
  Receipt,
  Users,
  KeyRound
} from 'lucide-react';
import PwaInstallModal from '@/components/PwaInstallModal';

export default function Login() {
  const { app_settings } = usePage().props;
  const appName = app_settings?.app_name || 'PT. Sugiyama';
  const appSubname = app_settings?.app_subname || 'Sistem Pengajuan Cuti & E-Slip Gaji Terpadu';

  const appLogo = app_settings?.app_logo_url
    || (app_settings?.app_logo
      ? (app_settings.app_logo.startsWith('http')
          ? app_settings.app_logo
          : `${typeof window !== 'undefined' && window.Ziggy?.url ? window.Ziggy.url : ''}/storage/${app_settings.app_logo.replace(/^\/?storage\//, '')}`)
      : null);

  const [imgError, setImgError] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
    remember: true,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('login'));
  };

  return (
    <main className="min-h-[100dvh] bg-gradient-to-br from-[#0a2f23] via-[#0d4534] to-[#08231a] text-slate-900 flex flex-col justify-between relative overflow-hidden font-sans select-none">
      <Head title={`Masuk Sistem - ${appName}`} />

      {/* Ambient Background Glows */}
      <div className="absolute top-0 left-1/4 -mt-24 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute bottom-0 right-1/4 -mb-24 w-96 h-96 bg-teal-500/15 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none opacity-40" />

      {/* Top Navbar Header */}
      <header className="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 pt-5 pb-3 flex items-center justify-between">
        <div className="flex items-center space-x-3">
          {appLogo && !imgError ? (
            <img
              src={appLogo}
              alt={appName}
              onError={() => setImgError(true)}
              className="w-10 h-10 rounded-xl object-contain bg-white/90 p-1 shadow-md shadow-emerald-950/40 border border-white/20"
            />
          ) : (
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white font-black text-sm flex items-center justify-center shadow-md shadow-emerald-950/40 border border-white/20">
              SG
            </div>
          )}
          <div className="text-left">
            <span className="text-sm font-black tracking-tight text-white block leading-tight">
              {appName}
            </span>
            <span className="text-[10px] text-emerald-300/80 font-bold uppercase tracking-wider block">
              Portal Kepegawaian
            </span>
          </div>
        </div>

        {/* Security / SSL Indicator */}
        <div className="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-emerald-300 text-[11px] font-bold shadow-xs">
          <ShieldCheck size={14} className="text-emerald-400" />
          <span className="hidden sm:inline">Secure 256-Bit SSL</span>
          <span className="sm:hidden">SSL Terproteksi</span>
        </div>
      </header>

      {/* Main Content Area */}
      <div className="relative z-10 w-full max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-10 flex flex-col lg:flex-row items-center justify-center gap-8 lg:gap-12 flex-1">
        
        {/* Left Side: Corporate Welcome & Features (Visible on Desktop / Tablet) */}
        <motion.div
          initial={{ opacity: 0, x: -25 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.5, ease: 'easeOut' }}
          className="hidden lg:flex flex-col justify-center flex-1 text-white space-y-6 max-w-lg"
        >
          <div className="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-extrabold w-fit backdrop-blur-md">
            <Sparkles size={14} className="text-emerald-400" />
            <span>Digital Workplace Ecosystem</span>
          </div>

          <div className="space-y-2">
            <h1 className="text-3xl sm:text-4xl font-black tracking-tight text-white leading-tight">
              Kelola Pengajuan & Payroll Lebih Cepat.
            </h1>
            <p className="text-sm text-emerald-100/80 font-medium leading-relaxed">
              Platform internal karyawan untuk pengajuan cuti, ketidakhadiran, approval berjenjang, dan e-slip gaji bulanan secara real-time.
            </p>
          </div>

          {/* Value Props Grid */}
          <div className="grid grid-cols-2 gap-3 pt-2">
            <div className="p-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-xs space-y-1.5">
              <div className="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-300 flex items-center justify-center font-bold">
                <FileText size={16} />
              </div>
              <h2 className="text-xs font-black text-white">Pengajuan Cuti Online</h2>
              <p className="text-[11px] text-emerald-200/70 font-medium">Approval otomatis bertingkat ke atasan & HRD.</p>
            </div>

            <div className="p-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-xs space-y-1.5">
              <div className="w-8 h-8 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center font-bold">
                <Receipt size={16} />
              </div>
              <h2 className="text-xs font-black text-white">E-Slip Gaji Terenkripsi</h2>
              <p className="text-[11px] text-teal-200/70 font-medium">Akses dan unduh slip gaji bulanan privat Anda.</p>
            </div>
          </div>
        </motion.div>

        {/* Right Side: Modern Glassmorphic Login Card */}
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 15 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          transition={{ duration: 0.4, ease: 'easeOut' }}
          className="w-full max-w-md"
        >
          <div className="rounded-3xl bg-white/95 sm:bg-white backdrop-blur-xl border border-white/60 sm:border-slate-100 shadow-2xl shadow-emerald-950/30 p-6 sm:p-8 space-y-6 relative">
            
            {/* Form Header */}
            <div className="text-center space-y-2">
              <div className="lg:hidden mx-auto mb-3">
                {appLogo && !imgError ? (
                  <img
                    src={appLogo}
                    alt={appName}
                    onError={() => setImgError(true)}
                    className="w-16 h-16 rounded-2xl object-contain bg-white p-1.5 shadow-lg shadow-emerald-900/15 border-2 border-emerald-500/20 mx-auto"
                  />
                ) : (
                  <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-2xl shadow-lg shadow-emerald-900/20 mx-auto">
                    SG
                  </div>
                )}
              </div>
              <h2 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Selamat Datang Kembali
              </h2>
              <p className="text-xs text-slate-500 font-medium max-w-xs mx-auto">
                Silakan masuk menggunakan Email atau NIK akun karyawan Anda.
              </p>
            </div>

            {/* Login Form */}
            <form onSubmit={handleSubmit} className="space-y-4">
              {/* Email / NIK Input */}
              <div className="space-y-1.5">
                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                  Email atau NIK Karyawan
                </label>
                <div className="relative group">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                    <Mail size={17} />
                  </div>
                  <input
                    type="text"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder="nama@sgin.co.id atau NIK (SA-001)"
                    className={`w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 border ${
                      errors.email ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 focus:border-emerald-600'
                    } text-slate-900 placeholder-slate-400 text-xs sm:text-sm font-semibold focus:bg-white focus:ring-4 focus:ring-emerald-500/15 transition-all outline-none`}
                    required
                    autoFocus
                  />
                </div>
                {errors.email && (
                  <motion.p
                    initial={{ opacity: 0, y: -4 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="text-[11px] text-rose-600 font-bold pl-1"
                  >
                    {errors.email}
                  </motion.p>
                )}
              </div>

              {/* Password Input */}
              <div className="space-y-1.5">
                <div className="flex items-center justify-between">
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Kata Sandi
                  </label>
                </div>
                <div className="relative group">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                    <Lock size={17} />
                  </div>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    placeholder="••••••••"
                    className={`w-full pl-10 pr-10 py-3 rounded-2xl bg-slate-50 border ${
                      errors.password ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 focus:border-emerald-600'
                    } text-slate-900 placeholder-slate-400 text-xs sm:text-sm font-semibold focus:bg-white focus:ring-4 focus:ring-emerald-500/15 transition-all outline-none`}
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 focus:outline-none transition-colors"
                  >
                    {showPassword ? <EyeOff size={17} /> : <Eye size={17} />}
                  </button>
                </div>
                {errors.password && (
                  <motion.p
                    initial={{ opacity: 0, y: -4 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="text-[11px] text-rose-600 font-bold pl-1"
                  >
                    {errors.password}
                  </motion.p>
                )}
              </div>

              {/* Remember Me Checkbox */}
              <div className="flex items-center justify-between pt-1">
                <label className="flex items-center space-x-2 cursor-pointer select-none">
                  <input
                    type="checkbox"
                    checked={data.remember}
                    onChange={(e) => setData('remember', e.target.checked)}
                    className="w-4 h-4 rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-0 cursor-pointer"
                  />
                  <span className="text-xs font-semibold text-slate-600">
                    Ingat sesi saya
                  </span>
                </label>
                <span className="text-[11px] text-slate-400 font-medium">
                  Bantuan: Hubungi HRD
                </span>
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                disabled={processing}
                className="w-full py-3.5 px-5 rounded-2xl bg-gradient-to-r from-[#0E7A53] via-[#0FA172] to-[#1CB67C] hover:from-[#0b6343] hover:to-[#179c6a] active:scale-[0.99] text-white font-extrabold text-sm shadow-lg shadow-emerald-600/25 flex items-center justify-center space-x-2 transition-all disabled:opacity-50 mt-3 cursor-pointer"
              >
                {processing ? (
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                ) : (
                  <>
                    <span>Masuk Ke Sistem</span>
                    <ArrowRight size={17} className="transition-transform group-hover:translate-x-1" />
                  </>
                )}
              </button>
            </form>

            {/* Direct PWA Install Trigger */}
            <div className="pt-2 border-t border-slate-100 text-center">
              <button
                type="button"
                onClick={() => window.dispatchEvent(new CustomEvent('open-pwa-install-modal'))}
                className="w-full inline-flex items-center justify-center space-x-2 py-2.5 px-4 rounded-2xl bg-slate-50 hover:bg-emerald-50/80 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-emerald-800 font-bold text-xs shadow-2xs transition-all active:scale-[0.98] cursor-pointer"
              >
                <Smartphone size={15} className="text-emerald-600" />
                <span>Pasang Aplikasi di HP / PC (PWA)</span>
              </button>
            </div>
          </div>
        </motion.div>
      </div>

      {/* Corporate Footer */}
      <footer className="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 py-4 text-center">
        <p className="text-[11px] text-emerald-200/60 font-medium">
          &copy; {new Date().getFullYear()} {appName}. Hak Cipta Dilindungi Undang-Undang.
        </p>
      </footer>

      {/* PWA Floating Install Modal */}
      <PwaInstallModal />
    </main>
  );
}
