<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Data Pekerjaan
            if (!Schema::hasColumn('users', 'join_date')) {
                $table->date('join_date')->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('users', 'employee_status')) {
                $table->string('employee_status')->nullable()->after('join_date'); // Tetap, Kontrak, PKWT, PKWTT, Magang
            }
            if (!Schema::hasColumn('users', 'education')) {
                $table->string('education')->nullable()->after('employee_status'); // SMA/SMK, D3, S1, S2, dll
            }
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('education'); // Jabatan (Operator, Staff, Leader, SPV, dll)
            }
            if (!Schema::hasColumn('users', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('position'); // Aktif Bekerja Sampai
            }

            // Data Pribadi & Kependudukan
            if (!Schema::hasColumn('users', 'ktp_number')) {
                $table->string('ktp_number', 30)->nullable()->after('contract_end_date'); // NIK KTP
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 20)->nullable()->after('ktp_number'); // Laki-laki / Perempuan
            }
            if (!Schema::hasColumn('users', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('birth_place');
            }
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number', 30)->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('users', 'ktp_address')) {
                $table->text('ktp_address')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('users', 'domicile_address')) {
                $table->text('domicile_address')->nullable()->after('ktp_address');
            }
            if (!Schema::hasColumn('users', 'marital_status')) {
                $table->string('marital_status', 50)->nullable()->after('domicile_address');
            }
            if (!Schema::hasColumn('users', 'mother_maiden_name')) {
                $table->string('mother_maiden_name')->nullable()->after('marital_status');
            }
            if (!Schema::hasColumn('users', 'kk_number')) {
                $table->string('kk_number', 30)->nullable()->after('mother_maiden_name');
            }
            if (!Schema::hasColumn('users', 'blood_type')) {
                $table->string('blood_type', 10)->nullable()->after('kk_number'); // A, B, AB, O
            }

            // Jaminan Sosial & Keuangan
            if (!Schema::hasColumn('users', 'npwp')) {
                $table->string('npwp', 50)->nullable()->after('blood_type');
            }
            if (!Schema::hasColumn('users', 'bpjs_kesehatan_number')) {
                $table->string('bpjs_kesehatan_number', 50)->nullable()->after('npwp');
            }
            if (!Schema::hasColumn('users', 'bpjs_health_facility')) {
                $table->string('bpjs_health_facility')->nullable()->after('bpjs_kesehatan_number'); // Faskes BPJS Kes
            }
            if (!Schema::hasColumn('users', 'bpjs_ketenagakerjaan_number')) {
                $table->string('bpjs_ketenagakerjaan_number', 50)->nullable()->after('bpjs_health_facility'); // BPJS TKU
            }
            if (!Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name', 50)->nullable()->after('bpjs_ketenagakerjaan_number');
            }
            if (!Schema::hasColumn('users', 'bank_account_number')) {
                $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            }

            // Logistik & Lapangan
            if (!Schema::hasColumn('users', 'vehicle_plate_number')) {
                $table->string('vehicle_plate_number', 30)->nullable()->after('bank_account_number'); // No Pol
            }
            if (!Schema::hasColumn('users', 'sim_number')) {
                $table->string('sim_number', 50)->nullable()->after('vehicle_plate_number'); // No SIM
            }
            if (!Schema::hasColumn('users', 'sim_valid_until')) {
                $table->date('sim_valid_until')->nullable()->after('sim_number'); // Berlaku sampai
            }
            if (!Schema::hasColumn('users', 'shoe_size')) {
                $table->string('shoe_size', 10)->nullable()->after('sim_valid_until'); // No Sepatu Safety
            }

            // Kontak Darurat
            if (!Schema::hasColumn('users', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('shoe_size');
            }
            if (!Schema::hasColumn('users', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship', 50)->nullable()->after('emergency_contact_name'); // Hubungan
            }
            if (!Schema::hasColumn('users', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_relationship'); // No Tlp
            }
            if (!Schema::hasColumn('users', 'emergency_contact_address')) {
                $table->text('emergency_contact_address')->nullable()->after('emergency_contact_phone'); // Alamat
            }

            // Data Pasangan & Anak
            if (!Schema::hasColumn('users', 'spouse_name')) {
                $table->string('spouse_name')->nullable()->after('emergency_contact_address'); // Suami / Istri
            }
            if (!Schema::hasColumn('users', 'spouse_ktp_number')) {
                $table->string('spouse_ktp_number', 30)->nullable()->after('spouse_name'); // NIK Suami / Istri
            }
            if (!Schema::hasColumn('users', 'spouse_birth_place')) {
                $table->string('spouse_birth_place')->nullable()->after('spouse_ktp_number');
            }
            if (!Schema::hasColumn('users', 'spouse_birth_date')) {
                $table->date('spouse_birth_date')->nullable()->after('spouse_birth_place');
            }
            if (!Schema::hasColumn('users', 'child_1_name')) {
                $table->string('child_1_name')->nullable()->after('spouse_birth_date'); // Anak ke 1
            }
            if (!Schema::hasColumn('users', 'child_2_name')) {
                $table->string('child_2_name')->nullable()->after('child_1_name'); // Anak ke 2
            }
            if (!Schema::hasColumn('users', 'child_3_name')) {
                $table->string('child_3_name')->nullable()->after('child_2_name'); // Anak ke 3
            }

            // Status Kelengkapan Profil
            if (!Schema::hasColumn('users', 'is_profile_completed')) {
                $table->boolean('is_profile_completed')->default(false)->after('child_3_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'join_date',
                'employee_status',
                'education',
                'position',
                'contract_end_date',
                'ktp_number',
                'gender',
                'birth_place',
                'birth_date',
                'phone_number',
                'ktp_address',
                'domicile_address',
                'marital_status',
                'mother_maiden_name',
                'kk_number',
                'blood_type',
                'npwp',
                'bpjs_kesehatan_number',
                'bpjs_health_facility',
                'bpjs_ketenagakerjaan_number',
                'bank_name',
                'bank_account_number',
                'vehicle_plate_number',
                'sim_number',
                'sim_valid_until',
                'shoe_size',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_phone',
                'emergency_contact_address',
                'spouse_name',
                'spouse_ktp_number',
                'spouse_birth_place',
                'spouse_birth_date',
                'child_1_name',
                'child_2_name',
                'child_3_name',
                'is_profile_completed',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
