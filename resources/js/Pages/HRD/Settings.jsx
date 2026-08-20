import React, { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { motion } from 'framer-motion';
import {
  Settings as SettingsIcon,
  Building,
  Upload,
  Image as ImageIcon,
  Smartphone,
  CheckCircle,
  Save,
  Globe,
  Phone,
  Mail,
  MapPin,
  Sparkles,
  Download,
  Palette,
  ShieldAlert
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { showToast } from '@/Utils/swal';

export default function Settings({ settings = {} }) {
  const { app_settings } = usePage().props;
  const currentSettings = settings || app_settings || {};

  const { data, setData, post, processing, errors } = useForm({
    app_name: currentSettings.app_name || 'Form SGIN',
    app_subname: currentSettings.app_subname || 'Cuti & Ketidakhadiran',
    company_name: currentSettings.company_name || 'PT. SGIN Indonesia',
    company_address: currentSettings.company_address || '',
    company_phone: currentSettings.company_phone || '',
    company_email: currentSettings.company_email || '',
    theme_color: currentSettings.theme_color || '#059669',
    app_description: currentSettings.app_description || '',
    app_logo: null,
  });

  const [logoPreview, setLogoPreview] = useState(
    currentSettings.app_logo
      ? (currentSettings.app_logo.startsWith('http') ? currentSettings.app_logo : `/storage/${currentSettings.app_logo}`)
      : null
  );

  const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setData('app_logo', file);
      const reader = new FileReader();
      reader.onloadend = () => {
        setLogoPreview(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('hrd.settings.update'), {
      preserveScroll: true,
      onSuccess: () => {
        showToast('Pengaturan aplikasi & logo berhasil disimpan!', 'success');
      },
      onError: () => {
        showToast('Gagal menyimpan pengaturan. Silakan periksa formulir.', 'error');
      },
    });
  };

  const triggerPwaModal = () => {
    window.dispatchEvent(new CustomEvent('open-pwa-install-modal'));
  };

  const themeColors = [
    { label: 'Emerald (Default)', value: '#059669', bg: 'bg-emerald-600' },
    { label: 'Teal Modern', value: '#0d9488', bg: 'bg-teal-600' },
    { label: 'Indigo Pro', value: '#4f46e5', bg: 'bg-indigo-600' },
    { label: 'Ocean Blue', value: '#0284c7', bg: 'bg-sky-600' },
    { label: 'Rose Accent', value: '#e11d48', bg: 'bg-rose-600' },
    { label: 'Slate Dark', value: '#334155', bg: 'bg-slate-700' },
  ];

  return (
    <AuthenticatedLayout title="Pengaturan Aplikasi & PWA">
      <motion.div
        initial={{ opacity: 0, y: 12 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.25 }}
        className="space-y-6 max-w-5xl mx-auto"
      >
        {/* Page Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-5 sm:p-6 rounded-3xl bg-white border border-slate-200 shadow-sm">
          <div className="flex items-center space-x-3.5">
            <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-lg shadow-emerald-600/20">
              <SettingsIcon size={24} />
            </div>
            <div>
              <h2 className="text-lg sm:text-xl font-extrabold text-slate-900">Setup & Pengaturan Aplikasi</h2>
              <p className="text-xs text-slate-500">Sesuaikan nama aplikasi, logo, alamat perusahaan, dan integrasi PWA</p>
            </div>
          </div>

          <div className="flex items-center space-x-2">
            <Button
              type="button"
              variant="outline"
              onClick={triggerPwaModal}
              className="rounded-2xl text-xs font-bold space-x-1.5"
            >
              <Smartphone size={16} className="text-emerald-600" />
              <span>Tes Pop-up Install PWA</span>
            </Button>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {/* LEFT COLUMN: LOGO & PWA APP ICON */}
            <div className="space-y-6">
              <Card className="rounded-3xl border-slate-200 shadow-sm overflow-hidden">
                <CardHeader className="pb-3">
                  <div className="flex items-center space-x-2">
                    <ImageIcon size={18} className="text-emerald-600" />
                    <CardTitle className="text-sm font-bold text-slate-900">Logo Aplikasi & PWA</CardTitle>
                  </div>
                  <CardDescription className="text-xs text-slate-500">
                    Logo ini akan otomatis digunakan di seluruh header, sidebar, PDF, dan ikon install PWA.
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {/* Logo Preview */}
                  <div className="flex flex-col items-center justify-center p-6 rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 space-y-3 text-center">
                    {logoPreview ? (
                      <div className="relative group">
                        <img
                          src={logoPreview}
                          alt="Logo Preview"
                          className="w-24 h-24 rounded-2xl object-cover border-2 border-white shadow-lg shadow-emerald-600/15"
                        />
                      </div>
                    ) : (
                      <div className="w-24 h-24 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-black text-white text-3xl shadow-lg shadow-emerald-600/20">
                        SG
                      </div>
                    )}
                    <div className="space-y-1">
                      <p className="text-xs font-bold text-slate-800">
                        {logoPreview ? 'Logo Kustom Aktif' : 'Logo Default (SG Gradient)'}
                      </p>
                      <p className="text-[10px] text-slate-500">
                        Format: PNG, JPG, WebP, SVG (Maks. 5MB)
                      </p>
                    </div>
                  </div>

                  {/* Upload Button */}
                  <div>
                    <label className="w-full flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl border border-emerald-300 bg-emerald-50/80 hover:bg-emerald-100/80 text-emerald-800 text-xs font-bold cursor-pointer transition-colors active:scale-[0.98]">
                      <Upload size={16} />
                      <span>{logoPreview ? 'Ganti Logo Aplikasi' : 'Unggah Logo Baru'}</span>
                      <input
                        type="file"
                        accept="image/*"
                        onChange={handleLogoChange}
                        className="hidden"
                      />
                    </label>
                    {errors.app_logo && (
                      <p className="text-[10px] text-rose-600 mt-1">{errors.app_logo}</p>
                    )}
                  </div>

                  {/* PWA Info Box */}
                  <div className="p-3 rounded-2xl bg-teal-50/70 border border-teal-200/80 text-xs space-y-1.5">
                    <span className="text-[10px] font-bold text-teal-800 uppercase flex items-center space-x-1">
                      <Sparkles size={12} />
                      <span>PWA Auto-Sync</span>
                    </span>
                    <p className="text-[11px] text-slate-600 leading-relaxed">
                      Sistem otomatis mengonversi logo ke <strong>WebP</strong> untuk tampilan web dan menghasilkan <strong>PNG 192x192 & 512x512</strong> untuk ikon aplikasi ponsel.
                    </p>
                  </div>
                </CardContent>
              </Card>

              {/* Theme Color Picker */}
              <Card className="rounded-3xl border-slate-200 shadow-sm">
                <CardHeader className="pb-3">
                  <div className="flex items-center space-x-2">
                    <Palette size={18} className="text-emerald-600" />
                    <CardTitle className="text-sm font-bold text-slate-900">Warna Aksen PWA</CardTitle>
                  </div>
                  <CardDescription className="text-xs text-slate-500">
                    Warna status bar dan tema saat aplikasi diinstal di ponsel.
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="grid grid-cols-3 gap-2">
                    {themeColors.map((col) => (
                      <button
                        key={col.value}
                        type="button"
                        onClick={() => setData('theme_color', col.value)}
                        className={`p-2 rounded-xl border flex flex-col items-center space-y-1 transition-all ${
                          data.theme_color === col.value
                            ? 'border-slate-900 bg-slate-100 ring-2 ring-slate-900/10 font-bold'
                            : 'border-slate-200 hover:bg-slate-50'
                        }`}
                      >
                        <span className={`w-5 h-5 rounded-full ${col.bg} shadow-xs`} />
                        <span className="text-[9px] text-slate-700 truncate w-full text-center">{col.label.split(' ')[0]}</span>
                      </button>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* RIGHT COLUMN: APP & COMPANY INFORMATION */}
            <div className="lg:col-span-2 space-y-6">
              {/* Identitas Aplikasi */}
              <Card className="rounded-3xl border-slate-200 shadow-sm">
                <CardHeader className="pb-3">
                  <div className="flex items-center space-x-2">
                    <Globe size={18} className="text-emerald-600" />
                    <CardTitle className="text-sm font-bold text-slate-900">Identitas Aplikasi</CardTitle>
                  </div>
                  <CardDescription className="text-xs text-slate-500">
                    Nama dan judul sistem yang tampil di dashboard, menu, dan judul halaman.
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Nama Aplikasi *</label>
                      <input
                        type="text"
                        value={data.app_name}
                        onChange={(e) => setData('app_name', e.target.value)}
                        placeholder="Contoh: Form SGIN"
                        className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                        required
                      />
                      {errors.app_name && <p className="text-[10px] text-rose-600 mt-1">{errors.app_name}</p>}
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Sub-judul / Tagline</label>
                      <input
                        type="text"
                        value={data.app_subname}
                        onChange={(e) => setData('app_subname', e.target.value)}
                        placeholder="Contoh: Cuti & Ketidakhadiran"
                        className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                      />
                      {errors.app_subname && <p className="text-[10px] text-rose-600 mt-1">{errors.app_subname}</p>}
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Deskripsi Singkat Aplikasi</label>
                    <textarea
                      rows={2}
                      value={data.app_description}
                      onChange={(e) => setData('app_description', e.target.value)}
                      placeholder="Deskripsi sistem untuk metadata dan PWA..."
                      className="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none"
                    />
                  </div>
                </CardContent>
              </Card>

              {/* Data Profil Perusahaan */}
              <Card className="rounded-3xl border-slate-200 shadow-sm">
                <CardHeader className="pb-3">
                  <div className="flex items-center space-x-2">
                    <Building size={18} className="text-emerald-600" />
                    <CardTitle className="text-sm font-bold text-slate-900">Profil & Kontak Perusahaan</CardTitle>
                  </div>
                  <CardDescription className="text-xs text-slate-500">
                    Informasi perusahaan yang dicantumkan pada slip gaji, header cetak formulir, dan ekspor data.
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Nama Perusahaan / Organisasi *</label>
                    <input
                      type="text"
                      value={data.company_name}
                      onChange={(e) => setData('company_name', e.target.value)}
                      placeholder="Contoh: PT. SGIN Indonesia"
                      className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                      required
                    />
                    {errors.company_name && <p className="text-[10px] text-rose-600 mt-1">{errors.company_name}</p>}
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap Kantor</label>
                    <textarea
                      rows={2}
                      value={data.company_address}
                      onChange={(e) => setData('company_address', e.target.value)}
                      placeholder="Alamat kantor, gedung, jalan, kota..."
                      className="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none"
                    />
                    {errors.company_address && <p className="text-[10px] text-rose-600 mt-1">{errors.company_address}</p>}
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Nomor Telepon / WhatsApp</label>
                      <input
                        type="text"
                        value={data.company_phone}
                        onChange={(e) => setData('company_phone', e.target.value)}
                        placeholder="+62 21 8901234"
                        className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Email Resmi HRD / Kontak</label>
                      <input
                        type="email"
                        value={data.company_email}
                        onChange={(e) => setData('company_email', e.target.value)}
                        placeholder="hrd@perusahaan.co.id"
                        className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                      />
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Submit Button */}
              <div className="flex justify-end pt-2">
                <Button
                  type="submit"
                  disabled={processing}
                  className="rounded-2xl px-6 py-2.5 space-x-2 shadow-lg shadow-emerald-600/20"
                >
                  <Save size={16} />
                  <span>{processing ? 'Menyimpan...' : 'Simpan Semua Pengaturan'}</span>
                </Button>
              </div>
            </div>

          </div>
        </form>
      </motion.div>
    </AuthenticatedLayout>
  );
}
