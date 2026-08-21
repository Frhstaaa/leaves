import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Download, X, Sparkles, Smartphone, Zap, Bell, CheckCircle, Share, PlusSquare } from 'lucide-react';
import { usePage } from '@inertiajs/react';

export default function PwaInstallModal() {
  const { app_settings } = usePage().props;
  const appName = app_settings?.app_name || 'Form SGIN';
  const appSubname = app_settings?.app_subname || 'Cuti & Ketidakhadiran';
  const appLogo = app_settings?.app_logo ? (app_settings.app_logo.startsWith('http') ? app_settings.app_logo : `/storage/${app_settings.app_logo}`) : null;

  const [deferredPrompt, setDeferredPrompt] = useState(null);
  const [isOpen, setIsOpen] = useState(false);
  const [isIOS, setIsIOS] = useState(false);
  const [isInstalled, setIsInstalled] = useState(false);

  useEffect(() => {
    // Check if already running in standalone mode (installed PWA)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true || document.referrer.includes('android-app://');
    if (isStandalone) {
      setIsInstalled(true);
      return;
    }

    // Detect iOS
    const userAgent = window.navigator.userAgent.toLowerCase();
    const isIosDevice = /iphone|ipad|ipod/.test(userAgent);
    setIsIOS(isIosDevice);

    // Handler for native browser install prompt (Android, Chrome, Edge)
    const handleBeforeInstallPrompt = (e) => {
      e.preventDefault();
      setDeferredPrompt(e);
      // Auto open modal immediately when install prompt is ready
      const dismissed = sessionStorage.getItem('pwa_prompt_dismissed_session');
      if (!dismissed) {
        setIsOpen(true);
      }
    };

    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

    // Custom event to manually open install prompt
    const handleManualOpen = () => {
      setIsOpen(true);
    };
    window.addEventListener('open-pwa-install-modal', handleManualOpen);

    // Show popup after 1 second if not installed and not dismissed in current session
    const dismissed = sessionStorage.getItem('pwa_prompt_dismissed_session');
    if (!dismissed) {
      const timer = setTimeout(() => {
        setIsOpen(true);
      }, 1000);
      return () => clearTimeout(timer);
    }

    return () => {
      window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
      window.removeEventListener('open-pwa-install-modal', handleManualOpen);
    };
  }, []);

  const handleInstallClick = async () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      if (outcome === 'accepted') {
        setIsInstalled(true);
        setIsOpen(false);
      }
      setDeferredPrompt(null);
    } else {
      // If no native prompt is captured yet, give immediate visual guidance
      setIsOpen(true);
    }
  };

  const handleDismiss = () => {
    setIsOpen(false);
    sessionStorage.setItem('pwa_prompt_dismissed_session', 'true');
  };

  if (isInstalled) return null;

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-[120] flex items-start sm:items-center justify-center p-4">
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm"
            onClick={handleDismiss}
          />

          {/* Floating Centered Modal Card */}
          <motion.div
            initial={{ opacity: 0, scale: 0.9, y: 25 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.9, y: 25 }}
            transition={{ type: 'spring', damping: 28, stiffness: 380 }}
            className="relative z-10 w-full max-w-sm sm:max-w-md p-6 rounded-3xl bg-white border border-slate-200/90 text-slate-900 shadow-2xl space-y-5 overflow-hidden my-auto sm:my-auto"
          >
            {/* Top decorative gradient glow */}
            <div className="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-600" />

            {/* Close Button */}
            <button
              onClick={handleDismiss}
              className="absolute top-4 right-4 p-1.5 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition-colors"
            >
              <X size={18} />
            </button>

            {/* Header & Logo Section */}
            <div className="flex items-center space-x-3.5 pt-1">
              {appLogo ? (
                <img
                  src={appLogo}
                  alt={appName}
                  className="w-14 h-14 rounded-2xl object-contain bg-white p-1 border-2 border-emerald-500/30 shadow-lg shadow-emerald-600/15 shrink-0"
                />
              ) : (
                <div className="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-emerald-600/25 border border-white/20 shrink-0">
                  SG
                </div>
              )}
              <div className="min-w-0 flex-1">
                <div className="flex items-center space-x-1.5">
                  <span className="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-extrabold uppercase tracking-wide">
                    PWA App
                  </span>
                  <span className="text-[11px] text-emerald-600 font-bold flex items-center space-x-0.5">
                    <Sparkles size={12} />
                    <span>Resmi & Ringan</span>
                  </span>
                </div>
                <h3 className="text-base sm:text-lg font-black text-slate-900 truncate mt-0.5">
                  {appName}
                </h3>
                <p className="text-xs text-slate-500 truncate">{appSubname}</p>
              </div>
            </div>

            {/* Benefit Highlights */}
            <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-2 text-xs">
              <p className="font-bold text-slate-900 flex items-center space-x-1.5">
                <Smartphone size={15} className="text-emerald-600" />
                <span>Pasang di Layar Utama HP / Desktop</span>
              </p>
              <div className="grid grid-cols-1 gap-1.5 text-[11px] text-slate-600">
                <div className="flex items-center space-x-2">
                  <Zap size={14} className="text-amber-500 shrink-0" />
                  <span><strong>Akses Cepat 1-Ketukan</strong> tanpa perlu buka browser</span>
                </div>
                <div className="flex items-center space-x-2">
                  <Bell size={14} className="text-emerald-500 shrink-0" />
                  <span><strong>Notifikasi Real-time</strong> langsung ke perangkat Anda</span>
                </div>
                <div className="flex items-center space-x-2">
                  <CheckCircle size={14} className="text-teal-500 shrink-0" />
                  <span><strong>Hemat Kuota & Memori</strong>, ukuran super ringan (&lt; 2MB)</span>
                </div>
              </div>
            </div>

            {/* iOS Safari Instructions */}
            {isIOS && !deferredPrompt && (
              <div className="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs space-y-2">
                <p className="font-extrabold flex items-center space-x-1.5 text-amber-950">
                  <Share size={15} className="text-amber-700" />
                  <span>Cara Pasang di iPhone / iPad (Safari):</span>
                </p>
                <ol className="list-decimal list-inside text-[11px] text-amber-800 space-y-1.5 pl-1 leading-relaxed">
                  <li>Ketuk ikon <strong>Bagikan (Share)</strong> <Share size={12} className="inline mx-0.5 text-amber-700" /> di bilah bawah Safari.</li>
                  <li>Gulir ke bawah dan pilih <strong>'Tambahkan ke Layar Utama'</strong> (Add to Home Screen) <PlusSquare size={12} className="inline mx-0.5 text-amber-700" />.</li>
                  <li>Ketuk <strong>'Tambah'</strong> di pojok kanan atas layar iPhone Anda.</li>
                </ol>
              </div>
            )}

            {/* Android / Desktop Manual Guide when prompt not yet fired */}
            {!isIOS && !deferredPrompt && (
              <div className="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-950 text-xs space-y-2">
                <p className="font-extrabold flex items-center space-x-1.5 text-emerald-900">
                  <Smartphone size={15} className="text-emerald-700" />
                  <span>Cara Pasang di Android / Google Chrome:</span>
                </p>
                <ol className="list-decimal list-inside text-[11px] text-emerald-800 space-y-1.5 pl-1 leading-relaxed">
                  <li>Ketuk tombol <strong>titik tiga (⋮)</strong> di pojok kanan atas browser.</li>
                  <li>Pilih menu <strong>'Install aplikasi'</strong> atau <strong>'Tambahkan ke Layar Utama'</strong>.</li>
                  <li>Ketuk <strong>'Install'</strong> untuk memasang icon logo di layar HP Anda.</li>
                </ol>
              </div>
            )}

            {/* Action Buttons */}
            <div className="flex items-center space-x-2.5 pt-1">
              <button
                type="button"
                onClick={handleDismiss}
                className="w-1/3 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition-colors"
              >
                Nanti Saja
              </button>

              {deferredPrompt ? (
                <button
                  type="button"
                  onClick={handleInstallClick}
                  className="w-2/3 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs flex items-center justify-center space-x-2 shadow-lg shadow-emerald-600/25 active:scale-[0.98] transition-all"
                >
                  <Download size={16} />
                  <span>Install Sekarang</span>
                </button>
              ) : (
                <button
                  type="button"
                  onClick={handleDismiss}
                  className="w-2/3 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs flex items-center justify-center space-x-1.5 shadow-md shadow-emerald-600/20 active:scale-[0.98] transition-all"
                >
                  <CheckCircle size={15} className="mr-1" />
                  <span>Saya Mengerti</span>
                </button>
              )}
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
