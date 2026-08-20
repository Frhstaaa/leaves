<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\LeaveCategory;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Departments
        $deptIT = Department::firstOrCreate(['code' => 'DEPT-IT'], ['name' => 'Information Technology']);
        $deptHR = Department::firstOrCreate(['code' => 'DEPT-HRD'], ['name' => 'Human Resources & PGA']);
        $deptFinance = Department::firstOrCreate(['code' => 'DEPT-FIN'], ['name' => 'Finance & Accounting']);
        $deptOps = Department::firstOrCreate(['code' => 'DEPT-OPS'], ['name' => 'Operations & Supply']);

        // 2. Create Manager User (1 Tier Approval -> Direct HRD)
        $manager = User::firstOrCreate(
            ['email' => 'manager@sgin.com'],
            [
                'nik' => 'MGR-101',
                'name' => 'Ahmad Dahlan, S.T. (Manager IT)',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'department_id' => $deptIT->id,
                'approver_1_id' => null,
                'approver_2_id' => null,
            ]
        );
        $deptIT->update(['manager_id' => $manager->id]);

        // 3. Create Supervisor User (2 Tier Approval -> Manager -> HRD)
        $supervisor = User::firstOrCreate(
            ['email' => 'spv@sgin.com'],
            [
                'nik' => 'SPV-102',
                'name' => 'Bambang Sudiro (Supervisor IT)',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'department_id' => $deptIT->id,
                'approver_1_id' => null,
                'approver_2_id' => $manager->id,
                'manager_id' => $manager->id,
            ]
        );

        // 4. Create HRD / Admin User
        $hrdAdmin = User::firstOrCreate(
            ['email' => 'hrd@sgin.com'],
            [
                'nik' => 'HRD-001',
                'name' => 'Citra Lestari, S.Psi (HRD / PGA Admin)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department_id' => $deptHR->id,
                'approver_1_id' => null,
                'approver_2_id' => null,
            ]
        );
        $deptHR->update(['manager_id' => $hrdAdmin->id]);

        // 5. Create Employee 1: 3-Tier Approval (Supervisor -> Manager -> HRD)
        $employee1 = User::firstOrCreate(
            ['email' => 'karyawan@sgin.com'],
            [
                'nik' => 'EMP-201',
                'name' => 'Budi Santoso (Staf IT - 3 Tier)',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'department_id' => $deptIT->id,
                'approver_1_id' => $supervisor->id,
                'approver_2_id' => $manager->id,
                'manager_id' => $manager->id,
            ]
        );

        // 6. Create Employee 2: 2-Tier Approval (Manager -> HRD)
        $employee2 = User::firstOrCreate(
            ['email' => 'siti@sgin.com'],
            [
                'nik' => 'EMP-202',
                'name' => 'Siti Rahmawati (Staf IT - 2 Tier)',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'department_id' => $deptIT->id,
                'approver_1_id' => null,
                'approver_2_id' => $manager->id,
                'manager_id' => $manager->id,
            ]
        );

        // 7. Create Employee 3: 1-Tier Approval (Direct HRD)
        $employee3 = User::firstOrCreate(
            ['email' => 'doni@sgin.com'],
            [
                'nik' => 'EMP-203',
                'name' => 'Doni Kusuma (Staf Ops - 1 Tier)',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'department_id' => $deptOps->id,
                'approver_1_id' => null,
                'approver_2_id' => null,
                'manager_id' => null,
            ]
        );

        // 5. Create Leave Categories (All options from Form SGIN)
        $categories = [
            [
                'name' => 'Cuti Tahunan',
                'unit_type' => 'hari',
                'requires_attachment' => false,
                'default_quota' => 12,
                'description' => 'Cuti tahunan reguler karyawan (12 hari/tahun).',
            ],
            [
                'name' => 'Cuti Haid',
                'unit_type' => 'hari',
                'requires_attachment' => false,
                'default_quota' => 2,
                'description' => 'Izin khusus haid hari pertama/kedua untuk karyawan perempuan.',
            ],
            [
                'name' => 'Sakit (Dengan Surat Dokter)',
                'unit_type' => 'hari',
                'requires_attachment' => true,
                'default_quota' => 14,
                'description' => 'Ketidakhadiran karena sakit disertai bukti surat keterangan dokter.',
            ],
            [
                'name' => 'Sakit (Tanpa Surat Dokter)',
                'unit_type' => 'hari',
                'requires_attachment' => false,
                'default_quota' => 3,
                'description' => 'Ketidakhadiran karena sakit ringan tanpa surat dokter.',
            ],
            [
                'name' => 'Sakit Karena Kecelakaan Kerja',
                'unit_type' => 'hari',
                'requires_attachment' => true,
                'default_quota' => 14,
                'description' => 'Ketidakhadiran karena kecelakaan pada saat menjalankan tugas pekerjaan.',
            ],
            [
                'name' => 'Ijin tidak masuk karena Suami/Istri/Anak/Orang tua/Mertua/Saudara Kandung Meninggal/ Istri Melahirkan',
                'unit_type' => 'hari',
                'requires_attachment' => false,
                'default_quota' => 3,
                'description' => 'Izin khusus musibah keluarga inti atau anggota keluarga melahirkan.',
            ],
            [
                'name' => 'Mangkir',
                'unit_type' => 'hari',
                'requires_attachment' => false,
                'default_quota' => 0,
                'description' => 'Ketidakhadiran kerja tanpa pemberitahuan/izin sah.',
            ],
            [
                'name' => 'Ijin Datang terlambat (Kurang dari 4 jam)',
                'unit_type' => 'jam',
                'requires_attachment' => false,
                'default_quota' => 24,
                'description' => 'Izin keterlambatan masuk kantor kurang dari 4 jam.',
            ],
            [
                'name' => 'Ijin Datang terlambat (Lebih dari 4 jam)',
                'unit_type' => 'jam',
                'requires_attachment' => false,
                'default_quota' => 24,
                'description' => 'Izin keterlambatan masuk kantor lebih dari 4 jam.',
            ],
            [
                'name' => 'Pulang lebih cepat tanpa ijin',
                'unit_type' => 'jam',
                'requires_attachment' => false,
                'default_quota' => 0,
                'description' => 'Meninggalkan pekerjaan sebelum jam pulang tanpa persetujuan.',
            ],
            [
                'name' => 'Ijin Tidak Masuk Kerja Tanpa Menerima Upah',
                'unit_type' => 'hari',
                'requires_attachment' => false,
                'default_quota' => 0,
                'description' => 'Izin tidak masuk kerja di luar hak cuti yang memotong upah harian.',
            ],
            [
                'name' => 'Izin Meninggalkan Pekerjaan (Jam)',
                'unit_type' => 'jam',
                'requires_attachment' => false,
                'default_quota' => 24,
                'description' => 'Izin keluar kantor untuk urusan mendesak hitungan jam.',
            ],
        ];

        $catCuti = null;
        foreach ($categories as $catData) {
            $created = LeaveCategory::firstOrCreate(['name' => $catData['name']], $catData);
            if ($catData['name'] === 'Cuti Tahunan') {
                $catCuti = $created;
            }
        }

        // 6. Create Quotas for current year
        $currentYear = date('Y');
        foreach ([$manager, $hrdAdmin, $employee1, $employee2, $employee3] as $usr) {
            LeaveQuota::firstOrCreate(
                ['user_id' => $usr->id, 'year' => $currentYear],
                [
                    'total_quota' => 12,
                    'used_quota' => 0,
                    'remaining_quota' => 12,
                ]
            );
        }

        // 7. Seed Spatie Roles & Permissions
        $this->call(RolePermissionSeeder::class);
    }
}
