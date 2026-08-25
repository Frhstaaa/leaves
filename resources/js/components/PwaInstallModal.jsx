import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Download, X, Sparkles, Smartphone, Zap, Bell, CheckCircle, Share, PlusSquare, ArrowUpRight, HelpCircle } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { showToast } from '@/Utils/swal';

export default function PwaInstallModal() {
  const { app_settings } = usePage().props;
  const appName = app_settings?.app_name || 'Form SGIN';
  const appSubname = app_settings?.app_subname || 'Cuti & Ketidakhadiran';
  const appLogo = app_settings?.app_logo_url
    || (app_settings?.app_logo
      ? (app_settings.app_logo.startsWith('http')
          ? app_settings.app_logo
          : `/storage/${app_settings.app_logo.replace(/^\/?storage\//, '')}`)
      : null);

  const [deferredPrompt, setDeferredPrompt] = useState(window.deferredPWAInstallPrompt || null);
  const [isOpen, setIsOpen] = useState(false);
  const [isIOS, setIsIOS] = useState(false);
  const [isInstalled, setIsInstalled] = useState(false);
  const [isInstalling, setIsInstalling] = useState(false);

  useEffect(() => {
    // Check if already running in standalone mode (installed PWA)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone === true
      || document.referrer.includes('android-app://');

    if (isStandalone) {
      setIsInstalled(true);
      return;
    }

    // Detect iOS
    const userAgent = window.navigator.userAgent.toLowerCase();
    const isIosDevice = /iphone|ipad|ipod/.test(userAgent);
    setIsIOS(isIosDevice);

    // Initial check from global window prompt
    if (window.deferredPWAInstallPrompt) {
      setDeferredPrompt(window.deferredPWAInstallPrompt);
    }

    // Handler for native browser install prompt (Android, Chrome, Edge)
    const handleBeforeInstallPrompt = (e) => {
      e.preventDefault();
      window.deferredPWAInstallPrompt = e;
      setDeferredPrompt(e);
    };

    const handlePromptReady = (e) => {
      if (e.detail) {
        setDeferredPrompt(e.detail);
      } else if (window.deferredPWAInstallPrompt) {
        setDeferredPrompt(window.deferredPWAInstallPrompt);
      }
    };

    const handleAppInstalled = () => {
      setIsInstalled(true);
      setIsOpen(false);
      window.deferredPWAInstallPrompt = null;
      setDeferredPrompt(null);
      showToast('Aplikasi berhasil dipasang di perangkat Anda!');
    };

    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    window.addEventListener('pwa-prompt-ready', handlePromptReady);
    window.addEventListener('appinstalled', handleAppInstalled);

    // Custom event to manually open install prompt
    const handleManualOpen = () => {
      if (window.deferredPWAInstallPrompt) {
        setDeferredPrompt(window.deferredPWAInstallPrompt);
      }
      setIsOpen(true);
    };
    window.addEventListener('open-pwa-install-modal', handleManualOpen);

    return () => {
      window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
      window.removeEventListener('pwa-prompt-ready', handlePromptReady);
      window.removeEventListener('appinstalled', handleAppInstalled);
      window.removeEventListener('open-pwa-install-modal', handleManualOpen);
    };
  }, []);

  const handleInstallClick = async () => {
    const activePrompt = deferredPrompt || window.deferredPWAInstallPrompt;

    if (activePrompt) {
      try {
        setIsInstalling(true);
        activePrompt.prompt();
        const { outcome } = await activePrompt.userChoice;
        if (outcome === 'accepted') {
          setIsInstalled(true);
          setIsOpen(false);
          window.deferredPWAInstallPrompt = null;
          setDeferredPrompt(null);
          showToast('Aplikasi berhasil dipasang di perangkat Anda!');
        }
      } catch (err) {
        console.error('PWA install prompt error: ', err);
      } finally {
        setIsInstalling(false);
      }
    } else if (isIOS) {
      showToast('Ketuk tombol Bagikan (Share) di Safari, lalu pilih "Tambahkan ke Layar Utama".');
    } else {
      showToast('Gunakan ikon Install di bilah alamat browser atau menu titik tiga (⋮).');
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
        <div className="fixed inset-0 z-[120] flex items-center justify-center p-4">
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 bg-slate-950/75 backdrop-blur-sm"
            onClick={handleDismiss}
          />

          {/* Floating Centered Modal Card */}
          <motion.div
            initial={{ opacity: 0, scale: 0.92, y: 20 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.92, y: 20 }}
            transition={{ type: 'spring', damping: 28, stiffness: 380 }}
            className="relative z-10 w-full max-w-sm sm:max-w-md p-6 rounded-3xl bg-white border border-slate-200/90 text-slate-900 shadow-2xl space-y-4 overflow-hidden"
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
                <div className="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-bold text-white text-xl shadow-lg shadow-emerald-600/25 border border-white/20 shrink-0">
                  SG
                </div>
              )}
              <div className="min-w-0 flex-1">
                <div className="flex items-center space-x-1.5">
                  <span className="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wide">
                    PWA Official
                  </span>
                  <span className="text-[11px] text-emerald-600 font-semibold flex items-center space-x-0.5">
                    <Sparkles size={12} />
                    <span>Cepat & Ringan</span>
                  </span>
                </div>
                <h3 className="text-base sm:text-lg font-bold text-slate-900 truncate mt-0.5">
                  {appName}
                </h3>
                <p className="text-xs text-slate-500 truncate">{appSubname}</p>
              </div>
            </div>

            {/* Benefit Highlights */}
            <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-2 text-xs">
              <p className="font-semibold text-slate-900 flex items-center space-x-1.5">
                <Smartphone size={15} className="text-emerald-600" />
                <span>Pasang di Layar Utama HP / Desktop</span>
              </p>
              <div className="grid grid-cols-1 gap-1.5 text-[11px] text-slate-600">
                <div className="flex items-center space-x-2">
                  <Zap size={14} className="text-amber-500 shrink-0" />
                  <span><strong>Akses 1-Ketukan</strong> langsung tanpa buka browser</span>
                </div>
                <div className="flex items-center space-x-2">
                  <Bell size={14} className="text-emerald-500 shrink-0" />
                  <span><strong>Notifikasi Approval</strong> langsung ke perangkat Anda</span>
                </div>
                <div className="flex items-center space-x-2">
                  <CheckCircle size={14} className="text-teal-500 shrink-0" />
                  <span><strong>Ukuran Super Ringan</strong> (&lt; 2MB) & hemat kuota</span>
                </div>
              </div>
            </div>

            {/* iOS Safari Instructions */}
            {isIOS && (
              <div className="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs space-y-2">
                <p className="font-bold flex items-center space-x-1.5 text-amber-950">
                  <Share size={15} className="text-amber-700" />
                  <span>Panduan Pasang di iPhone / iPad (Safari):</span>
                </p>
                <ol className="list-decimal list-inside text-[11px] text-amber-800 space-y-1 pl-1 leading-relaxed">
                  <li>Ketuk tombol <strong>Bagikan (Share)</strong> <Share size={12} className="inline mx-0.5 text-amber-700" /> di bilah bawah Safari.</li>
                  <li>Pilih <strong>'Tambahkan ke Layar Utama'</strong> (Add to Home Screen) <PlusSquare size={12} className="inline mx-0.5 text-amber-700" />.</li>
                  <li>Ketuk <strong>'Tambah'</strong> di kanan atas layar iPhone Anda.</li>
                </ol>
              </div>
            )}

            {/* Android / Desktop Guidance */}
            {!isIOS && !deferredPrompt && !window.deferredPWAInstallPrompt && (
              <div className="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-950 text-xs space-y-2">
                <p className="font-bold flex items-center space-x-1.5 text-emerald-900">
                  <Smartphone size={15} className="text-emerald-700" />
                  <span>Panduan Pasang di Chrome / Edge / Android:</span>
                </p>
                <ol className="list-decimal list-inside text-[11px] text-emerald-800 space-y-1 pl-1 leading-relaxed">
                  <li>Klik tombol <strong>"Install / Pasang Aplikasi"</strong> di bawah ini.</li>
                  <li>Atau ketuk menu <strong>titik tiga (⋮)</strong> di kanan atas browser &gt; pilih <strong>'Install Aplikasi'</strong>.</li>
                </ol>
              </div>
            )}

            {/* Direct Action Buttons */}
            <div className="space-y-2 pt-1">
              <button
                type="button"
                onClick={handleInstallClick}
                disabled={isInstalling}
                className="w-full py-3 rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs flex items-center justify-center space-x-2 shadow-lg shadow-emerald-600/25 active:scale-[0.98] transition-all cursor-pointer"
              >
                <Download size={16} className="animate-bounce" />
                <span>{isInstalling ? 'Memproses Pemasangan...' : '📲 Pasang / Install Aplikasi Sekarang'}</span>
              </button>

              <button
                type="button"
                onClick={handleDismiss}
                className="w-full py-2 rounded-xl text-slate-500 hover:text-slate-700 font-semibold text-xs transition-colors"
              >
                Nanti Saja
              </button>
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
