<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class ERPRolesSeeder extends Seeder
{
    public function run(): void
    {
        // ===========================================
        // HOSPITAL STAFF ROLES
        // ===========================================
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
                'name' => 'Hospital Administrator',
                'slug' => 'hospital_admin',
                'description' => 'Hospital Administrator - Day-to-day Management',
                'permissions' => [
                    'hospital.*',
                    'hospital.staff.manage',
                    'hospital.patients.*',
                    'hospital.appointments.*',
                    'hospital.reports.*',
                    'hospital.billing.*',
                    'hospital.settings.*',
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
            [
                'name' => 'Medical Records Officer',
                'slug' => 'medical_records_officer',
                'description' => 'Medical Records Management',
                'permissions' => [
                    'hospital.records.*',
                    'hospital.patients.view',
                    'hospital.reports.*',
                    'hospital.documents.*',
                ]
            ],
            [
                'name' => 'Hospital Accountant',
                'slug' => 'hospital_accountant',
                'description' => 'Hospital Finance Management',
                'permissions' => [
                    'hospital.billing.*',
                    'hospital.invoices.*',
                    'hospital.payments.*',
                    'hospital.reports.financial',
                ]
            ],
            [
                'name' => 'Matron',
                'slug' => 'matron',
                'description' => 'Head of Nursing - Hospital Ward Management',
                'permissions' => [
                    'hospital.patients.view',
                    'hospital.vitals.*',
                    'hospital.appointments.assist',
                    'hospital.records.view',
                    'hospital.duty.*',
                    'hospital.wards.*',
                ]
            ],
            [
                'name' => 'Ward Manager',
                'slug' => 'ward_manager',
                'description' => 'Hospital Ward Operations',
                'permissions' => [
                    'hospital.patients.view',
                    'hospital.vitals.*',
                    'hospital.records.view',
                    'hospital.duty.*',
                    'hospital.wards.*',
                ]
            ],
        ];

        // ===========================================
        // BURSARY STAFF ROLES
        // ===========================================
        $bursaryRoles = [
            [
                'name' => 'Bursar',
                'slug' => 'bursar',
                'description' => 'Head of Bursary Department',
                'permissions' => [
                    'finance.*',
                    'bursary.*',
                    'fees.*',
                    'payments.*',
                    'finance.ledgers.*',
                    'finance.reports.*',
                    'finance.budgets.*',
                    'finance.payroll.*',
                    'finance.audit.*',
                ]
            ],
            [
                'name' => 'Bursary Officer',
                'slug' => 'bursary_officer',
                'description' => 'Bursary Operations Staff',
                'permissions' => [
                    'bursary.*',
                    'fees.*',
                    'payments.*',
                    'payments.process',
                    'payments.verify',
                    'finance.receipts.*',
                    'finance.invoices.*',
                ]
            ],
            [
                'name' => 'Fees Officer',
                'slug' => 'fees_officer',
                'description' => 'Fees Management Staff',
                'permissions' => [
                    'fees.*',
                    'fees.create',
                    'fees.edit',
                    'fees.view',
                    'fees.reports.*',
                ]
            ],
            [
                'name' => 'Payment Officer',
                'slug' => 'payment_officer',
                'description' => 'Payment Processing Staff',
                'permissions' => [
                    'payments.*',
                    'payments.process',
                    'payments.verify',
                    'payments.upload',
                    'payments.sync.*',
                    'finance.receipts.*',
                ]
            ],
            [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Cash Office Operations — receives and records payments',
                'permissions' => [
                    'finance.receipts.*',
                    'finance.payments.process',
                    'finance.invoices.view',
                    'bursary.view',
                    'payments.view',
                ]
            ],
            [
                'name' => 'Finance Officer',
                'slug' => 'finance_officer',
                'description' => 'Finance Module Operations Staff',
                'permissions' => [
                    'finance.*',
                    'finance.ledgers.*',
                    'finance.reports.*',
                    'finance.budgets.view',
                    'finance.invoices.*',
                ]
            ],
            [
                'name' => 'Account Officer',
                'slug' => 'account_officer',
                'description' => 'Accounts / Receivables Officer',
                'permissions' => [
                    'finance.invoices.*',
                    'finance.receipts.*',
                    'finance.payments.process',
                    'finance.reports.view',
                ]
            ],
        ];

        // ===========================================
        // AUDIT STAFF ROLES
        // ===========================================
        $auditRoles = [
            [
                'name' => 'Internal Auditor',
                'slug' => 'internal_auditor',
                'description' => 'Internal Audit - Financial Review',
                'permissions' => [
                    'audit.*',
                    'audit.logs.view',
                    'audit.reports.*',
                    'finance.view',
                    'finance.reports.view',
                    'deleted.records.view',
                ]
            ],
            [
                'name' => 'External Auditor',
                'slug' => 'external_auditor',
                'description' => 'External Audit - Independent Review',
                'permissions' => [
                    'audit.*',
                    'audit.logs.view',
                    'audit.reports.*',
                    'audit.full_access',
                    'finance.view',
                    'finance.reports.view',
                    'deleted.records.view',
                ]
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Financial Auditor',
                'permissions' => [
                    'finance.view',
                    'finance.reports.view',
                    'finance.audit.*',
                    'audit.logs.view',
                    'deleted.records.view',
                ]
            ],
        ];

        // ===========================================
        // LIBRARY STAFF ROLES
        // ===========================================
        $libraryRoles = [
            [
                'name' => 'Librarian',
                'slug' => 'librarian',
                'description' => 'Head Librarian - Library Administration',
                'permissions' => [
                    'library.*',
                    'library.books.*',
                    'library.loans.*',
                    'library.reports.*',
                    'library.settings.*',
                    'library.users.*',
                ]
            ],
            [
                'name' => 'Library Officer',
                'slug' => 'library_officer',
                'description' => 'Library Operations Staff',
                'permissions' => [
                    'library.*',
                    'library.books.*',
                    'library.loans.*',
                    'library.members.*',
                ]
            ],
            [
                'name' => 'Library Assistant',
                'slug' => 'library_assistant',
                'description' => 'Library Support Staff',
                'permissions' => [
                    'library.books.view',
                    'library.loans.*',
                    'library.members.register',
                    'library.search.*',
                ]
            ],
        ];

        // ===========================================
        // FINANCE ROLES (Existing)
        // ===========================================
        $financeRoles = [
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

        // ===========================================
        // EXECUTIVE ROLES (Existing)
        // ===========================================
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
            [
                'name' => 'Dean',
                'slug' => 'dean',
                'description' => 'Dean of Faculty',
                'permissions' => [
                    'courses.view',
                    'timetable.*',
                    'results.approve',
                    'department.view',
                    'reports.students',
                ]
            ],
            [
                'name' => 'Business Committee',
                'slug' => 'business_committee',
                'description' => 'Business Committee Member - Financial Approval',
                'permissions' => [
                    'results.approve',
                    'reports.financial',
                    'fees.view',
                    'payments.approve',
                ]
            ],
            [
                'name' => 'Academic Board',
                'slug' => 'academic_board',
                'description' => 'Academic Board Member - Final Result Approval',
                'permissions' => [
                    'results.approve.final',
                    'reports.academic',
                    'students.view',
                    'courses.approve',
                ]
            ],
        ];

        // Merge all roles
        $allRoles = array_merge(
            $hospitalRoles,
            $bursaryRoles,
            $auditRoles,
            $libraryRoles,
            $financeRoles,
            $executiveRoles
        );

        foreach ($allRoles as $role) {
            // Check if role already exists
            $existingRole = Role::where('slug', $role['slug'])->first();

            if (!$existingRole) {
                Role::create($role);
            }
        }
    }
}