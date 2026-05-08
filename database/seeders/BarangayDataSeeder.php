<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BarangayDataSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data (optional - be careful in production)
        // DB::table('users')->truncate();
        // DB::table('categories')->truncate();

        // Create admin user
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@barangay.gov.ph'],
            [
                'name' => 'Barangay Admin',
                'full_name' => 'Barangay Admin',
                'email' => 'admin@barangay.gov.ph',
                'password' => Hash::make('Admin1234'),
                'role' => 'admin',
                'is_verified' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create staff user
        DB::table('users')->updateOrInsert(
            ['email' => 'staff@barangay.gov.ph'],
            [
                'name' => 'Juan dela Cruz',
                'full_name' => 'Juan dela Cruz',
                'email' => 'staff@barangay.gov.ph',
                'password' => Hash::make('Staff1234'),
                'role' => 'staff',
                'is_verified' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create resident user
        DB::table('users')->updateOrInsert(
            ['email' => 'maria@example.com'],
            [
                'name' => 'Maria Santos',
                'full_name' => 'Maria Santos',
                'email' => 'maria@example.com',
                'password' => Hash::make('User1234'),
                'phone' => '09171234567',
                'address' => 'Block 1 Lot 5, Sample Street, Barangay Sample',
                'role' => 'resident',
                'is_verified' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Insert categories
        $categories = [
            [
                'name' => 'Barangay Clearance',
                'slug' => 'barangay-clearance',
                'description' => 'Certificate of good moral standing',
                'icon' => 'document_text',
                'color_hex' => '#2563EB',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Certificate of Indigency',
                'slug' => 'cert-indigency',
                'description' => 'Proof of low-income status',
                'icon' => 'shield_check',
                'color_hex' => '#7C3AED',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Business Permit',
                'slug' => 'business-permit',
                'description' => 'Barangay business permit/endorsement',
                'icon' => 'briefcase',
                'color_hex' => '#0891B2',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Complaint',
                'slug' => 'complaint',
                'description' => 'File a complaint against a resident or business',
                'icon' => 'exclamation_circle',
                'color_hex' => '#DC2626',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Blotter Report',
                'slug' => 'blotter-report',
                'description' => 'Incident/blotter report filing',
                'icon' => 'document_report',
                'color_hex' => '#EA580C',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Certificate of Residency',
                'slug' => 'cert-residency',
                'description' => 'Proof that you reside in this barangay',
                'icon' => 'home',
                'color_hex' => '#16A34A',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Social Services',
                'slug' => 'social-services',
                'description' => 'Request social welfare assistance',
                'icon' => 'heart',
                'color_hex' => '#DB2777',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Other service requests',
                'icon' => 'dots_horizontal',
                'color_hex' => '#6B7280',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Users:');
        $this->command->info('  Admin: admin@barangay.gov.ph / Admin1234');
        $this->command->info('  Staff: staff@barangay.gov.ph / Staff1234');
        $this->command->info('  Resident: maria@example.com / User1234');
        $this->command->info('Categories: ' . DB::table('categories')->count() . ' records');
    }
}