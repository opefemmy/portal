<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class ERPRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Hospital Roles
        $hospitalRoles = [
            [
                'name' => 'Chief Medical Director',
                'slug' => 'cmd',
                'description' => 'Chief Medical Director - Hospital Administration',
                'permissions' => [
                    'hospital.*',
                    'hospital.reports.*',
                    'hospital.staff.*',
                    'hospital.patients.*',
                    'hospital.appointments.*',
                    'hospital.prescriptions.*',
                    'hospital.lab.*',
                    'hospital.pharmacy.*',
                    'hospital.store.*',
                ]
            ],
            [
                'name' => 'Doctor',
                'slug' => 'doctor',
                'description' => 'Medical Doctor',
                'permissions' => [
                    'hospital.patients.view',
                    'hospital.patients.diagnose',
                    'hospital.prescriptions.*',
                    'hospital.lab.request',
                    'hospital.appointments.*',
                    'hospital.records.*',
                    'hospital.admit',
                ]
            ],
            [
                'name' => 'Nurse',
                'slug' => 'nurse',
                'description' => 'Nursing Staff',
                'permissions' => [
                    'hospital.patients.view',
                    'hospital.vitals.*',
                    'hospital.appointments.assist',
                    'hospital.records.view',
                ]
            ],
            [
                'name' => 'Laboratory Scientist',
                'slug' => 'lab_scientist',
                'description' => 'Laboratory Staff',
                'permissions' => [
                    'hospital.lab.*',
                    'hospital.patients.view',
                ]
            ],
            [
                'name' => 'Pharmacist',
                'slug' => 'pharmacist',
                'description' => 'Pharmacy Staff',
                'permissions' => [
                    'hospital.pharmacy.*',
                    'hospital.prescriptions.dispense',
                    'hospital.drugs.*',
                    'hospital.inventory.*',
                ]
            ],
            [
                'name' => 'Hospital Receptionist',
                'slug' => 'hospital_receptionist',
                'description' => 'Hospital Front Desk',
                'permissions' => [
                    'hospital.patients.register',
                    'hospital.patients.search',
                    'hospital.appointments.schedule',
                    'hospital.queue.*',
                ]
            ],
            [
                'name' => 'Hospital Store Keeper',
                'slug' => 'store_keeper',
                'description' => 'Hospital Store Management',
                'permissions' => [
                    'hospital.store.*',
                    'hospital.inventory.*',
                    'hospital.purchases.*',
                ]
            ],
        ];

        // Finance Roles
        $financeRoles = [
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Financial Auditor - Read Only',
                'permissions' => [
                    'finance.view',
                    'finance.reports.view',
                    'finance.audit.*',
                    'audit.logs.view',
                    'deleted.records.view',
                ]
            ],
            [
                'name' => 'ICT Administrator',
                'slug' => 'ict_admin',
                'description' => 'ICT Administration',
                'permissions' => [
                    'users.*',
                    'settings.*',
                    'reports.*',
                    'analytics.*',
                ]
            ],
            [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Cash Office Operations',
                'permissions' => [
                    'finance.receipts.*',
                    'finance.payments.process',
                    'finance.invoices.view',
                ]
            ],
            [
                'name' => 'Accountant',
                'slug' => 'accountant',
                'description' => 'Financial Accounting',
                'permissions' => [
                    'finance.*',
                    'finance.ledgers.*',
                    'finance.budgets.*',
                    'finance.payroll.*',
                    'finance.reports.*',
                ]
            ],
        ];

        // Executive Roles
        $executiveRoles = [
            [
                'name' => 'Rector',
                'slug' => 'rector',
                'description' => 'Institution Rector - Executive Dashboard',
                'permissions' => [
                    'dashboard.executive',
                    'reports.executive',
                    'reports.financial',
                    'reports.students',
                    'reports.staff',
                    'notifications.view',
                ]
            ],
            [
                'name' => 'Head of Department',
                'slug' => 'hod',
                'description' => 'Head of Department',
                'permissions' => [
                    'courses.assign',
                    'courses.view',
                    'timetable.*',
                    'results.approve',
                    'lecturers.view',
                    'department.*',
                ]
            ],
        ];

        $allRoles = array_merge($hospitalRoles, $financeRoles, $executiveRoles);

        foreach ($allRoles as $role) {
            // Check if role already exists
            $existingRole = Role::where('slug', $role['slug'])->first();

            if (!$existingRole) {
                Role::create($role);
            }
        }
    }
}