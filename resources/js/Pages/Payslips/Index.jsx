import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Receipt,
  Download,
  Eye,
  Calendar,
  CheckCircle2,
  Clock,
  FileText,
  Lock,
  Sparkles,
  ChevronRight,
  X,
  ExternalLink,
  ShieldCheck
} from 'lucide-react';
import { showToast } from '@/Utils/swal';

const containerVariants = {
  hidden: { opacity: 0 },
  show: {
    opacity: 1,
    transition: {
      staggerChildren: 0.05
    }
  }
};

const itemVariants = {
  hidden: { opacity: 0, y: 12 },
  show: { opacity: 1, y: 0, transition: { duration: 0.2, ease: 'easeOut' } }
};

const MONTH_NAMES = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export default function PayslipsIndex({ payslips = [], selectedYear = new Date().getFullYear(), availableYears = [], stats = {} }) {
  const [activeYear, setActiveYear] = useState(selectedYear);
  const [previewPayslip, setPreviewPayslip] = useState(null);

  const handleYearChange = (year) => {
    setActiveYear(year);
    router.get(route('payslips.index'), { year }, { preserveState: true });
  };

  const handleOpenPreview = (payslip) => {
    setPreviewPayslip(payslip);
  };

  // Map payslips by month number (1 - 12) for the 12-month calendar view
  const payslipByMonth = {};
  payslips.forEach((p) => {
    payslipByMonth[p.month] = p;
  });

  return (
    <AuthenticatedLayout title="Slip Gaji Saya">
      <Head title="Slip Gaji Saya - Form SGIN" />

      <motion.div
        variants={containerVariants}
        initial="hidden"
        animate="show"
        className="space-y-6"
      >
        {/* ========================================================================= */}
        {/* 1. HERO HEADER BANNER                                                     */}
        {/* ========================================================================= */}
        <motion.div
          variants={itemVariants}
          className="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#0E7A53] via-[#0FA172] to-[#1CB67C] text-white shadow-lg shadow-emerald-600/20 relative overflow-hidden"
        >
          <div className="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none" />

          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white/20 border border-white/30 text-white text-xs font-bold uppercase tracking-wider mb-3 backdrop-blur-md">
                <Receipt size={14} className="text-emerald-100" />
                <span>Dokumen Payroll Resmi</span>
              </div>
              <h1 className="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Slip Gaji Saya
              </h1>
              <p className="text-sm text-emerald-50 mt-1 max-w-2xl font-medium leading-relaxed">
                Akses dan unduh slip gaji bulanan Anda secara aman dan privat. Seluruh riwayat penghasilan Anda tersimpan dengan rapi di sini.
              </p>
            </div>

            {/* Year Selector */}
            <div className="w-[150px] shrink-0">
              <Select
                value={String(activeYear)}
                onValueChange={(val) => handleYearChange(val)}
              >
                <SelectTrigger className="bg-white/20 border-white/30 text-white font-black text-xs rounded-2xl backdrop-blur-md hover:bg-white/30 focus:bg-white focus:text-slate-900 transition-colors">
                  <SelectValue placeholder={`Tahun ${activeYear}`} />
                </SelectTrigger>
                <SelectContent>
                  {availableYears.map((yr) => (
                    <SelectItem key={yr} value={String(yr)}>
                      Tahun {yr}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        </motion.div>

        {/* ========================================================================= */}
        {/* 2. STATS ROW                                                              */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <Receipt size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Tersedia Tahun {activeYear}</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.total_this_year || payslips.length} Bulan</h3>
              </div>
            </div>
          </Card>

          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <Clock size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Slip Baru / Belum Dibuka</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.unviewed_count || 0} Slip</h3>
              </div>
            </div>
          </Card>

          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                <ShieldCheck size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Keamanan Dokumen</p>
                <h3 className="text-sm sm:text-base font-extrabold text-slate-800">Terenkripsi & Privat</h3>
              </div>
            </div>
          </Card>
        </motion.div>

        {/* ========================================================================= */}
        {/* 3. 12-MONTH PAYSLIP GRID                                                  */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-base font-extrabold text-slate-900 flex items-center space-x-2">
              <FileText size={18} className="text-emerald-600" />
              <span>Daftar Slip Gaji Periode Tahun {activeYear}</span>
            </h2>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {MONTH_NAMES.map((monthName, idx) => {
              const monthNum = idx + 1;
              const payslip = payslipByMonth[monthNum];

              if (payslip) {
                const isViewed = payslip.is_viewed || Boolean(payslip.viewed_at);

                return (
                  <motion.div
                    key={monthNum}
                    whileHover={{ y: -3 }}
                    transition={{ duration: 0.15 }}
                  >
                    <Card className="border-emerald-200/80 shadow-xs hover:shadow-md transition-all bg-gradient-to-b from-white to-emerald-50/20 flex flex-col justify-between h-full">
                      <CardHeader className="p-5 pb-3">
                        <div className="flex items-start justify-between">
                          <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-xs shrink-0 uppercase">
                            {monthName.substring(0, 3)}
                          </div>
                          <div>
                            {isViewed ? (
                              <Badge variant="secondary" className="text-[10px] font-bold bg-slate-100 text-slate-600 space-x-1">
                                <CheckCircle2 size={11} className="text-emerald-600" />
                                <span>Sudah Dilihat</span>
                              </Badge>
                            ) : (
                              <Badge variant="success" className="text-[10px] font-black space-x-1 animate-pulse">
                                <Sparkles size={11} />
                                <span>Slip Baru</span>
                              </Badge>
                            )}
                          </div>
                        </div>

                        <div className="pt-2">
                          <CardTitle className="text-base font-black text-slate-900">
                            {monthName} {activeYear}
                          </CardTitle>
                          <CardDescription className="text-xs text-slate-500 font-medium mt-0.5">
                            Slip Gaji Bulanan &bull; {payslip.formatted_file_size || 'PDF'}
                          </CardDescription>
                        </div>
                      </CardHeader>

                      <CardContent className="p-5 pt-0 space-y-2">
                        {payslip.notes && (
                          <p className="text-xs text-slate-600 bg-white p-2.5 rounded-xl border border-slate-200/70 italic line-clamp-2">
                            "{payslip.notes}"
                          </p>
                        )}
                        {payslip.viewed_at && (
                          <p className="text-[10px] text-slate-400 font-medium">
                            Dibuka: {new Date(payslip.viewed_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                          </p>
                        )}
                      </CardContent>

                      <CardFooter className="p-5 pt-0 border-t border-slate-100/80 flex items-center gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          className="flex-1 rounded-xl text-xs font-bold space-x-1 border-slate-300 hover:bg-emerald-50 hover:text-emerald-700"
                          onClick={() => handleOpenPreview(payslip)}
                        >
                          <Eye size={14} />
                          <span>Lihat</span>
                        </Button>

                        <a
                          href={route('payslips.download', payslip.id)}
                          className="flex-1"
                          download
                        >
                          <Button
                            variant="emerald"
                            size="sm"
                            className="w-full rounded-xl text-xs font-extrabold space-x-1"
                          >
                            <Download size={14} />
                            <span>Unduh</span>
                          </Button>
                        </a>
                      </CardFooter>
                    </Card>
                  </motion.div>
                );
              }

              // Empty Month Card
              return (
                <Card key={monthNum} className="border-dashed border-slate-200 bg-slate-50/40 flex flex-col justify-between opacity-60 hover:opacity-80 transition-opacity">
                  <CardHeader className="p-5 pb-3">
                    <div className="flex items-start justify-between">
                      <div className="w-10 h-10 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center font-black text-xs shrink-0 uppercase">
                        {monthName.substring(0, 3)}
                      </div>
                      <Badge variant="outline" className="text-[10px] font-medium text-slate-400 border-slate-200">
                        Belum Rilis
                      </Badge>
                    </div>

                    <div className="pt-2">
                      <CardTitle className="text-base font-bold text-slate-500">
                        {monthName} {activeYear}
                      </CardTitle>
                      <CardDescription className="text-xs text-slate-400 font-medium mt-0.5">
                        Belum ada slip gaji yang diterbitkan
                      </CardDescription>
                    </div>
                  </CardHeader>

                  <CardFooter className="p-5 pt-0 text-[11px] text-slate-400 italic">
                    Menunggu penerbitan oleh HRD
                  </CardFooter>
                </Card>
              );
            })}
          </div>
        </motion.div>
      </motion.div>

      {/* ========================================================================= */}
      {/* MODAL: INTERACTIVE PDF PREVIEW                                            */}
      {/* ========================================================================= */}
      <AnimatePresence>
        {previewPayslip && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-2 sm:p-4 overflow-hidden">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-slate-950/75 backdrop-blur-xs"
              onClick={() => setPreviewPayslip(null)}
            />

            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              transition={{ type: 'spring', stiffness: 380, damping: 30 }}
              className="relative z-10 w-full max-w-4xl h-[92vh] rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl overflow-hidden flex flex-col transform-gpu"
            >
              {/* Modal Header */}
              <div className="p-4 sm:p-5 border-b border-slate-200 flex items-center justify-between shrink-0 bg-slate-50">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0">
                    <Receipt size={20} />
                  </div>
                  <div>
                    <h3 className="text-base font-black text-slate-900 leading-tight">
                      Slip Gaji - {previewPayslip.period_label || `${previewPayslip.month_name} ${previewPayslip.year}`}
                    </h3>
                    <p className="text-xs text-slate-500 font-medium mt-0.5">
                      Dokumen Resmi Payroll Form SGIN &bull; {previewPayslip.original_filename}
                    </p>
                  </div>
                </div>

                <div className="flex items-center space-x-2">
                  <a
                    href={route('payslips.download', previewPayslip.id)}
                    download
                  >
                    <Button variant="emerald" size="sm" className="rounded-xl space-x-1">
                      <Download size={14} />
                      <span className="hidden sm:inline">Download PDF</span>
                    </Button>
                  </a>
                  <button
                    type="button"
                    onClick={() => setPreviewPayslip(null)}
                    className="p-2 rounded-xl bg-slate-200/70 text-slate-600 hover:bg-slate-300 transition-colors"
                  >
                    <X size={18} />
                  </button>
                </div>
              </div>

              {/* PDF Viewer Iframe */}
              <div className="flex-1 bg-slate-100 relative overflow-hidden">
                <iframe
                  src={route('payslips.preview', previewPayslip.id)}
                  title={`Slip Gaji ${previewPayslip.period_label}`}
                  className="w-full h-full border-0"
                />
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </AuthenticatedLayout>
  );
}
