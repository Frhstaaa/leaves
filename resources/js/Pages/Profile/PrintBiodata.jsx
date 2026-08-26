import React, { useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Printer, ArrowLeft, Building2 } from 'lucide-react';

export default function PrintBiodata({ employee = {} }) {
  const deptName = employee.department?.name || 'General';

  useEffect(() => {
    // Auto trigger print after short delay when page loads
    const timer = setTimeout(() => {
      window.print();
    }, 600);
    return () => clearTimeout(timer);
  }, []);

  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      });
    } catch {
      return dateStr;
    }
  };

  return (
    <>
      <Head title={`Form Data Diri - ${employee.name || 'Karyawan'} - PT SUGIYAMA INDONESIA`} />

      <div className="min-h-screen bg-slate-100 text-slate-900 py-6 px-4 print:p-0 print:bg-white print:text-black">
        {/* Floating Action Header for Screen View */}
        <div className="max-w-4xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-slate-200 print:hidden">
          <div className="flex items-center space-x-3">
            <Link
              href={route('profile.biodata')}
              className="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center space-x-2 transition-colors"
            >
              <ArrowLeft size={16} />
              <span>Kembali ke Form</span>
            </Link>
            <span className="text-xs text-slate-500 font-medium">| Form Data Diri Karyawan</span>
          </div>

          <button
            onClick={() => window.print()}
            className="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center space-x-2 shadow-md shadow-emerald-600/20 transition-all"
          >
            <Printer size={16} />
            <span>Cetak / Simpan PDF</span>
          </button>
        </div>

        {/* Paper Document Container matching the exact PT SUGIYAMA INDONESIA Layout */}
        <div className="max-w-4xl mx-auto bg-white p-6 sm:p-8 rounded-2xl shadow-md border border-slate-300 print:border-none print:shadow-none print:p-0 print:max-w-full font-sans text-xs">
          
          {/* Document Header */}
          <div className="border-b-2 border-slate-800 pb-3 mb-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <div className="w-12 h-12 bg-emerald-600 text-white rounded-xl flex items-center justify-center font-black text-xl shadow-sm print:border print:border-slate-800">
                  S
                </div>
                <div>
                  <h1 className="text-xl sm:text-2xl font-black text-emerald-800 tracking-tight print:text-black uppercase">
                    PT SUGIYAMA INDONESIA
                  </h1>
                  <p className="text-[10px] text-slate-600 font-bold tracking-wider uppercase print:text-black">
                    Industrial Components & Precision Manufacturing
                  </p>
                </div>
              </div>

              <div className="text-right">
                <div className="inline-block border border-slate-800 px-3 py-1 bg-slate-50 font-bold text-[11px] rounded print:bg-transparent">
                  FORM DATA DIRI
                </div>
                <p className="text-[9px] text-slate-500 mt-1">Ref: HRD-SGIN-F01</p>
              </div>
            </div>
          </div>

          {/* Form Content Grid (2 Columns matching original form layout) */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 print:grid-cols-2">
            
            {/* LEFT COLUMN: Main Employment & Personal Data */}
            <div className="space-y-1.5">
              <div className="text-[11px] font-bold text-slate-800 uppercase tracking-wider border-b border-slate-300 pb-1 mb-2 bg-slate-100 px-2 py-0.5 rounded print:bg-slate-200">
                1. Data Pekerjaan & Identitas
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Nama Lengkap</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-bold text-slate-900">{employee.name || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">NIK SGIN</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-mono font-bold text-slate-900">{employee.nik || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Tanggal Bergabung</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium">{formatDate(employee.join_date)}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Status Karyawan</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-bold">{employee.employee_status || 'Tetap'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Pendidikan</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium">{employee.education || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Jabatan</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-bold">{employee.position || employee.role || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Departemen/Bagian</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-bold">{deptName}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">NIK. KTP</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-mono font-bold">{employee.ktp_number || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Jenis Kelamin</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium">{employee.gender || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Tempat Tanggal Lahir</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium">
                  {employee.birth_place ? `${employee.birth_place}, ` : ''}{formatDate(employee.birth_date)}
                </span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Nomor HP</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium font-mono">{employee.phone_number || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Alamat KTP</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-normal leading-relaxed">{employee.ktp_address || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Alamat Domisili</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-normal leading-relaxed">{employee.domicile_address || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Status Perkawinan</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium">{employee.marital_status || 'Belum Menikah'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">NPWP</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-mono font-medium">{employee.npwp || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">No. BPJS Kesehatan</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-mono font-medium">{employee.bpjs_kesehatan_number || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">No. BPJS TKU</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-mono font-medium">{employee.bpjs_ketenagakerjaan_number || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">No. Rekening</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-mono font-bold">
                  {employee.bank_account_number ? `${employee.bank_account_number} (${employee.bank_name || 'Bank'})` : '-'}
                </span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Aktif Bekerja Sampai</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium">{formatDate(employee.contract_end_date)}</span>
              </div>

              {/* Kontak Darurat Sub-section */}
              <div className="pt-2">
                <div className="text-[10px] font-bold text-slate-800 uppercase tracking-wider bg-slate-50 px-2 py-0.5 rounded border border-slate-200 mb-1.5 print:bg-slate-100">
                  Keluarga Yang Dapat Dihubungi (Kontak Darurat):
                </div>

                <div className="grid grid-cols-12 gap-1 py-0.5">
                  <span className="col-span-4 font-semibold text-slate-600 pl-2">Hubungan</span>
                  <span className="col-span-1 text-center">:</span>
                  <span className="col-span-7 font-medium">
                    {employee.emergency_contact_relationship || employee.emergency_contact_name ? `${employee.emergency_contact_name || ''} (${employee.emergency_contact_relationship || '-'})` : '-'}
                  </span>
                </div>

                <div className="grid grid-cols-12 gap-1 py-0.5">
                  <span className="col-span-4 font-semibold text-slate-600 pl-2">No. Tlp</span>
                  <span className="col-span-1 text-center">:</span>
                  <span className="col-span-7 font-mono font-medium">{employee.emergency_contact_phone || '-'}</span>
                </div>

                <div className="grid grid-cols-12 gap-1 py-0.5">
                  <span className="col-span-4 font-semibold text-slate-600 pl-2">Alamat</span>
                  <span className="col-span-1 text-center">:</span>
                  <span className="col-span-7 font-normal leading-tight">{employee.emergency_contact_address || '-'}</span>
                </div>
              </div>
            </div>

            {/* RIGHT COLUMN: Additional Info & Family Data */}
            <div className="space-y-1.5">
              <div className="text-[11px] font-bold text-slate-800 uppercase tracking-wider border-b border-slate-300 pb-1 mb-2 bg-slate-100 px-2 py-0.5 rounded print:bg-slate-200">
                2. Faskes, Perlengkapan & Keluarga
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Faskes BPJS Kes</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium">{employee.bpjs_health_facility || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Email</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium font-mono">{employee.email || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">No Pol Kendaraan</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-mono font-bold uppercase">{employee.vehicle_plate_number || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">No SIM</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-mono font-medium">
                  {employee.sim_number || '-'} {employee.sim_valid_until ? `(Berlaku: ${formatDate(employee.sim_valid_until)})` : ''}
                </span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">No Sepatu (Safety)</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-bold">{employee.shoe_size || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Gol Darah</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-bold text-rose-700 print:text-black">{employee.blood_type || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">Nama Gadis Ibu Kandung</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-medium">{employee.mother_maiden_name || '-'}</span>
              </div>

              <div className="grid grid-cols-12 gap-1 py-1 border-b border-slate-100">
                <span className="col-span-4 font-semibold text-slate-700">No KK</span>
                <span className="col-span-1 text-center">:</span>
                <span className="col-span-7 font-mono font-bold">{employee.kk_number || '-'}</span>
              </div>

              {/* Data Pasangan */}
              <div className="pt-2">
                <div className="text-[10px] font-bold text-slate-800 uppercase tracking-wider bg-slate-50 px-2 py-0.5 rounded border border-slate-200 mb-1.5 print:bg-slate-100">
                  Data Pasangan (Suami / Istri):
                </div>

                <div className="grid grid-cols-12 gap-1 py-0.5">
                  <span className="col-span-4 font-semibold text-slate-600 pl-2">Nama Suami / Istri</span>
                  <span className="col-span-1 text-center">:</span>
                  <span className="col-span-7 font-medium">{employee.spouse_name || '-'}</span>
                </div>

                <div className="grid grid-cols-12 gap-1 py-0.5">
                  <span className="col-span-4 font-semibold text-slate-600 pl-2">NIK Suami / Istri</span>
                  <span className="col-span-1 text-center">:</span>
                  <span className="col-span-7 font-mono font-medium">{employee.spouse_ktp_number || '-'}</span>
                </div>

                <div className="grid grid-cols-12 gap-1 py-0.5">
                  <span className="col-span-4 font-semibold text-slate-600 pl-2">Tempat Tgl Lahir</span>
                  <span className="col-span-1 text-center">:</span>
                  <span className="col-span-7 font-medium">
                    {employee.spouse_birth_place ? `${employee.spouse_birth_place}, ` : ''}{formatDate(employee.spouse_birth_date)}
                  </span>
                </div>
              </div>

              {/* Data Anak */}
              <div className="pt-2">
                <div className="text-[10px] font-bold text-slate-800 uppercase tracking-wider bg-slate-50 px-2 py-0.5 rounded border border-slate-200 mb-1.5 print:bg-slate-100">
                  Data Tanggungan Anak:
                </div>

                <div className="grid grid-cols-12 gap-1 py-0.5">
                  <span className="col-span-4 font-semibold text-slate-600 pl-2">Anak ke-1</span>
                  <span className="col-span-1 text-center">:</span>
                  <span className="col-span-7 font-medium">{employee.child_1_name || '-'}</span>
                </div>

                <div className="grid grid-cols-12 gap-1 py-0.5">
                  <span className="col-span-4 font-semibold text-slate-600 pl-2">Anak ke-2</span>
                  <span className="col-span-1 text-center">:</span>
                  <span className="col-span-7 font-medium">{employee.child_2_name || '-'}</span>
                </div>

                <div className="grid grid-cols-12 gap-1 py-0.5">
                  <span className="col-span-4 font-semibold text-slate-600 pl-2">Anak ke-3</span>
                  <span className="col-span-1 text-center">:</span>
                  <span className="col-span-7 font-medium">{employee.child_3_name || '-'}</span>
                </div>
              </div>

              {/* Signature Box */}
              <div className="pt-6 mt-4 border-t border-slate-200">
                <p className="text-[10px] text-slate-500 italic text-center mb-6">
                  Saya menyatakan bahwa data yang diisi di atas adalah benar dan sesuai dengan keadaan sebenarnya.
                </p>

                <div className="grid grid-cols-2 gap-4 text-center">
                  <div>
                    <p className="text-[10px] text-slate-600">Dibuat oleh Karyawan,</p>
                    <div className="h-14 border-b border-slate-400 mx-6 mb-1"></div>
                    <p className="font-bold text-slate-800 text-[11px]">{employee.name}</p>
                    <p className="text-[9px] text-slate-500">Tgl: ________________</p>
                  </div>

                  <div>
                    <p className="text-[10px] text-slate-600">Diverifikasi oleh HRD / GA,</p>
                    <div className="h-14 border-b border-slate-400 mx-6 mb-1"></div>
                    <p className="font-bold text-slate-800 text-[11px]">( HRD Department )</p>
                    <p className="text-[9px] text-slate-500">Tgl: ________________</p>
                  </div>
                </div>
              </div>

            </div>

          </div>

          {/* Document Footer */}
          <div className="mt-8 pt-3 border-t border-slate-300 flex justify-between items-center text-[9px] text-slate-400 print:text-slate-600">
            <span>Sistem Informasi Manajemen Form SGIN - PT SUGIYAMA INDONESIA</span>
            <span>Dicetak secara otomatis pada: {new Date().toLocaleString('id-ID')}</span>
          </div>

        </div>
      </div>
    </>
  );
}
