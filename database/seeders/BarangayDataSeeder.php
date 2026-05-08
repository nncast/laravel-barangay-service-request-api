<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BarangayDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@barangay.gov.ph'],
            [
                'name' => 'Barangay Admin',
                'email' => 'admin@barangay.gov.ph',
                'password' => Hash::make('Admin1234'),
                'phone' => '09171234567',
                'address' => 'Barangay Hall, CM Recto St, Dubinan East',
                'role' => 'admin',
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
                'email' => 'staff@barangay.gov.ph',
                'password' => Hash::make('Staff1234'),
                'phone' => '09171234568',
                'address' => 'Block 2 Lot 8, CM Recto St, Dubinan East',
                'role' => 'staff',
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
                'email' => 'maria@example.com',
                'password' => Hash::make('User1234'),
                'phone' => '09171234569',
                'address' => 'Block 1 Lot 5, CM Recto St, Dubinan East',
                'role' => 'resident',
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
                'description' => 'Certificate of good moral standing and residency',
                'icon' => 'document_text',
                'color_hex' => '#2563EB',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Certificate of Indigency',
                'slug' => 'certificate-of-indigency',
                'description' => 'Proof of low-income status for government assistance',
                'icon' => 'shield_check',
                'color_hex' => '#7C3AED',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business Permit',
                'slug' => 'business-permit',
                'description' => 'Barangay business permit and endorsement',
                'icon' => 'briefcase',
                'color_hex' => '#0891B2',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Complaint',
                'slug' => 'complaint',
                'description' => 'File a complaint against a resident or business',
                'icon' => 'exclamation_circle',
                'color_hex' => '#DC2626',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Blotter Report',
                'slug' => 'blotter-report',
                'description' => 'Incident or blotter report filing',
                'icon' => 'document_report',
                'color_hex' => '#EA580C',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Certificate of Residency',
                'slug' => 'certificate-of-residency',
                'description' => 'Proof that you reside in this barangay',
                'icon' => 'home',
                'color_hex' => '#16A34A',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Social Services',
                'slug' => 'social-services',
                'description' => 'Request social welfare assistance (AYUDA, medical, burial, etc.)',
                'icon' => 'heart',
                'color_hex' => '#DB2777',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Other service requests not covered above',
                'icon' => 'dots_horizontal',
                'color_hex' => '#BE5633',
                'is_active' => true,
                'sort_order' => 8,
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

        $this->command->info('==========================================');
        $this->command->info('Database seeded successfully!');
        $this->command->info('==========================================');
        $this->command->info('');
        $this->command->info('USERS:');
        $this->command->info('  Admin:    admin@barangay.gov.ph / Admin1234');
        $this->command->info('  Staff:    staff@barangay.gov.ph / Staff1234');
        $this->command->info('  Resident: maria@example.com / User1234');
        $this->command->info('');
        $this->command->info('CATEGORIES: ' . DB::table('categories')->count() . ' records created');
        $this->command->info('');
        $this->command->info('==========================================');
    }
}