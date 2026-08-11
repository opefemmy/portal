<?php

namespace App\Services\Hospital;

use Illuminate\Support\Facades\Auth;

/**
 * Centralised role → permission map for the hospital module.
 *
 * Used by route middleware, controller guards (`EnforcesHospitalPermission` trait)
 * and Blade (`@permission(...)`) to enforce strict per-role jurisdiction.
 */
class HospitalPermissions
{
    /**
     * Permissions granted per role slug.
     *
     * A `'*'` value grants every permission to that role.
     */
    public const ROLE_PERMISSIONS = [
        'cmd' => ['*'],
        'super_admin' => ['*'],
        'admin' => ['*'],

        'doctor' => [
            'patients.view', 'patients.search',
            'appointments.view', 'appointments.create', 'appointments.update',
            'appointments.check-in', 'appointments.start',
            'consultations.view', 'consultations.create',
            'consultations.soap', 'consultations.sign',
            'lab.view', 'lab.create',
            'pharmacy.view',
            'prescriptions.view', 'prescriptions.create',
            'referrals.view', 'referrals.create', 'referrals.approve',
            'discharge.recommend',
            'vitals.view', 'vitals.chart',
            'timeline.view', 'timeline.export',
        ],

        'nurse' => [
            'patients.view',
            'appointments.view', 'appointments.check-in',
            'visits.vitals', 'vitals.create', 'vitals.view', 'vitals.chart',
            'pharmacy.view',
            'prescriptions.view',
            'wards.view', 'beds.assign', 'beds.manage',
            'timeline.view',
            'medications.administer',
            'monitoring.notes',
        ],

        'hospital_receptionist' => [
            'patients.view', 'patients.create', 'patients.edit',
            'external-patients.view', 'external-patients.create', 'external-patients.edit',
            'external-patients.appointment', 'external-patients.visit',
            'appointments.view', 'appointments.create',
            'appointments.queue', 'appointments.check-in',
            'billing.invoice', 'billing.payment', 'billing.refund',
            'search.advanced',
        ],

        'pharmacist' => [
            'pharmacy.view', 'pharmacy.dispense', 'pharmacy.drugs',
            'pharmacy.receive', 'pharmacy.adjust', 'pharmacy.expire',
            'pharmacy.interactions', 'pharmacy.low-stock', 'pharmacy.expiry',
            'prescriptions.view', 'prescriptions.dispense',
            'patients.view',
            'reports.dispensation',
        ],

        'lab_scientist' => [
            'lab.view', 'lab.collect', 'lab.process', 'lab.complete',
            'lab.approve', 'lab.print', 'lab.export',
            'patients.view',
            'reports.lab',
        ],

        'store_keeper' => [
            'pharmacy.view', 'pharmacy.drugs',
            'pharmacy.receive', 'pharmacy.adjust', 'pharmacy.expire',
            'inventory.purchase', 'inventory.suppliers',
        ],

        // Newly-recognised professional hospital roles. Each inherits
        // a focused permission set that maps onto real hospital workflows.
        'medical_director' => ['*'],

        'consultant' => [
            'patients.view', 'patients.search',
            'appointments.view', 'appointments.update',
            'consultations.view', 'consultations.create',
            'consultations.soap', 'consultations.sign',
            'lab.view', 'lab.create',
            'prescriptions.view', 'prescriptions.create',
            'referrals.view', 'referrals.create', 'referrals.approve',
            'discharge.recommend',
            'vitals.view', 'vitals.chart',
            'timeline.view', 'timeline.export',
        ],

        'matron' => [
            'patients.view',
            'appointments.view', 'appointments.check-in',
            'visits.vitals', 'vitals.view', 'vitals.chart',
            'wards.view', 'beds.assign', 'beds.manage',
            'monitoring.notes',
            'timeline.view',
            'reports.ward',
        ],

        'pharmacy_technician' => [
            'pharmacy.view', 'pharmacy.dispense',
            'prescriptions.view',
            'patients.view',
        ],

        'lab_technician' => [
            'lab.view', 'lab.collect',
            'patients.view',
        ],

        'radiographer' => [
            'radiology.view', 'radiology.image.upload',
            'patients.view',
        ],

        'radiologist' => [
            'radiology.view', 'radiology.report', 'radiology.approve',
            'patients.view',
        ],

        'cashier' => [
            'billing.invoice', 'billing.payment', 'billing.refund',
            'billing.receipt', 'billing.daily-revenue',
            'patients.view',
            'reports.revenue',
        ],

        'medical_records' => [
            'records.view', 'records.edit', 'records.merge',
            'records.folder', 'records.transfer',
            'patients.view',
            'timeline.view',
        ],

        'ward_manager' => [
            'wards.view', 'wards.manage', 'beds.assign', 'beds.manage',
            'patients.view',
            'reports.ward',
        ],

        'inventory_officer' => [
            'pharmacy.view', 'pharmacy.drugs',
            'pharmacy.receive', 'pharmacy.adjust', 'pharmacy.expire',
            'pharmacy.low-stock', 'pharmacy.expiry',
            'inventory.purchase', 'inventory.suppliers',
        ],

        // Cross-cutting hospital admin: read-mostly across every module,
        // plus the staff-availability toggle used by the on-call grid.
        'hospital_admin' => [
            'patients.view',
            'staff.view', 'staff.edit',
            'reports.daily-revenue', 'reports.ward', 'reports.lab',
            'reports.dispensation', 'reports.revenue',
            'billing.view', 'pharmacy.view', 'lab.view',
            'inventory.view',
            'audit.view',
            'attendance.view',
            'wards.view',
        ],

        // Records officer: confidentiality guardian. Read-only over charts,
        // plus archive / transfer / chart-request queue / full audit log.
        'medical_records_officer' => [
            'records.view', 'records.edit', 'records.archive',
            'records.transfer', 'records.request',
            'records.audit',
            'patients.view',
            'audit.view',
            'timeline.view', 'timeline.export',
        ],
    ];

    /**
     * Sidebar dashboard route name per role slug.
     */
    public const ROLE_DASHBOARDS = [
        'cmd'                 => 'hospital.dashboard',
        'super_admin'         => 'hospital.dashboard',
        'admin'               => 'hospital.dashboard',
        'medical_director'    => 'hospital.dashboard',
        'doctor'              => 'hospital.doctor.dashboard',
        'consultant'          => 'hospital.doctor.dashboard',
        'nurse'               => 'hospital.nurse.dashboard',
        'matron'              => 'hospital.matron.dashboard',
        'ward_manager'        => 'hospital.wards.occupancy',
        'hospital_receptionist'=> 'hospital.reception.dashboard',
        'cashier'             => 'hospital.reception.dashboard',
        'medical_records'     => 'hospital.reception.dashboard',
        'medical_records_officer' => 'hospital.records.index',
        'pharmacist'          => 'hospital.pharmacy.dashboard',
        'pharmacy_technician' => 'hospital.pharmacy.dashboard',
        'inventory_officer'   => 'hospital.pharmacy.dashboard',
        'lab_scientist'       => 'hospital.lab.dashboard',
        'lab_technician'      => 'hospital.lab.dashboard',
        'radiographer'        => 'hospital.lab.dashboard',
        'radiologist'         => 'hospital.lab.dashboard',
        'store_keeper'        => 'hospital.pharmacy.dashboard',
        'hospital_admin'      => 'hospital.admin.dashboard',
    ];

    /**
     * Sidebar menu items per role slug.
     *
     * Each entry maps to a [route_name, icon, label, url_pattern?] tuple.
     */
    public const ROLE_MENUS = [
        'cmd' => [
            ['hospital.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.external-patients.index', 'fas fa-user-friends', 'External Patients'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
            ['hospital.consultations.index', 'fas fa-stethoscope', 'Consultations'],
            ['hospital.pharmacy.drugs', 'fas fa-pills', 'Pharmacy'],
            ['hospital.lab.index', 'fas fa-flask', 'Laboratory'],
            ['hospital.roster.index', 'fas fa-calendar-week', 'Duty Roster'],
        ],

        'doctor' => [
            ['hospital.doctor.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
            ['hospital.consultations.index', 'fas fa-stethoscope', 'Consultations'],
            ['hospital.roster.index', 'fas fa-calendar-week', 'Duty Roster'],
        ],

        'nurse' => [
            ['hospital.nurse.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
            ['hospital.roster.index', 'fas fa-calendar-week', 'Duty Roster'],
        ],

        'hospital_receptionist' => [
            ['hospital.reception.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.external-patients.index', 'fas fa-user-friends', 'External Patients'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
            ['hospital.roster.index', 'fas fa-calendar-week', 'Duty Roster'],
        ],

        'pharmacist' => [
            ['hospital.pharmacy.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.pharmacy.drugs', 'fas fa-pills', 'Pharmacy'],
            ['hospital.pharmacy.prescriptions', 'fas fa-prescription', 'Prescriptions'],
            ['hospital.pharmacy.low-stock', 'fas fa-exclamation-triangle', 'Low Stock'],
        ],

        'lab_scientist' => [
            ['hospital.lab.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.lab.index', 'fas fa-flask', 'Laboratory'],
            ['hospital.lab.requests', 'fas fa-vial', 'Lab Requests'],
        ],

        'store_keeper' => [
            ['hospital.pharmacy.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.pharmacy.drugs', 'fas fa-pills', 'Drugs'],
            ['hospital.pharmacy.receive', 'fas fa-truck-loading', 'Receive Stock'],
            ['hospital.pharmacy.low-stock', 'fas fa-exclamation-triangle', 'Low Stock'],
        ],

        'medical_director' => [
            ['hospital.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
            ['hospital.consultations.index', 'fas fa-stethoscope', 'Consultations'],
            ['hospital.pharmacy.drugs', 'fas fa-pills', 'Pharmacy'],
            ['hospital.lab.index', 'fas fa-flask', 'Laboratory'],
        ],

        'consultant' => [
            ['hospital.doctor.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
            ['hospital.consultations.index', 'fas fa-stethoscope', 'Consultations'],
        ],

        'matron' => [
            ['hospital.matron.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.matron.rounds', 'fas fa-stethoscope', 'Ward Rounds'],
            ['hospital.matron.staff', 'fas fa-user-clock', 'Staff Load'],
            ['hospital.wards.index', 'fas fa-procedures', 'Wards'],
            ['hospital.wards.occupancy', 'fas fa-chart-bar', 'Occupancy'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.roster.index', 'fas fa-calendar-week', 'Duty Roster'],
        ],

        'pharmacy_technician' => [
            ['hospital.pharmacy.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.pharmacy.prescriptions', 'fas fa-prescription', 'Prescriptions'],
            ['hospital.pharmacy.low-stock', 'fas fa-exclamation-triangle', 'Low Stock'],
        ],

        'lab_technician' => [
            ['hospital.lab.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.lab.requests', 'fas fa-vial', 'Lab Requests'],
        ],

        'radiographer' => [
            ['hospital.lab.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.lab.requests', 'fas fa-x-ray', 'Radiology'],
        ],

        'radiologist' => [
            ['hospital.lab.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.lab.requests', 'fas fa-x-ray', 'Radiology'],
        ],

        'cashier' => [
            ['hospital.reception.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
        ],

        'medical_records' => [
            ['hospital.reception.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
        ],

        'ward_manager' => [
            ['hospital.wards.occupancy', 'fas fa-tachometer-alt', 'Occupancy'],
            ['hospital.wards.index', 'fas fa-procedures', 'Wards'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.roster.index', 'fas fa-calendar-week', 'Duty Roster'],
        ],

        'hospital_admin' => [
            ['hospital.admin.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.admin.staff', 'fas fa-user-md', 'Staff'],
            ['hospital.admin.revenue', 'fas fa-coins', 'Revenue'],
            ['hospital.admin.inventory', 'fas fa-pills', 'Inventory'],
            ['hospital.admin.attendance', 'fas fa-calendar-check', 'Attendance'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
            ['hospital.pharmacy.drugs', 'fas fa-pills', 'Pharmacy'],
            ['hospital.lab.index', 'fas fa-flask', 'Laboratory'],
        ],

        'medical_records_officer' => [
            ['hospital.records.index', 'fas fa-tachometer-alt', 'Records'],
            ['hospital.records.search', 'fas fa-search', 'Search'],
            ['hospital.records.audit', 'fas fa-history', 'Audit Log'],
            ['hospital.records.requests', 'fas fa-inbox', 'Requests'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
        ],

        'inventory_officer' => [
            ['hospital.pharmacy.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.pharmacy.drugs', 'fas fa-pills', 'Drugs'],
            ['hospital.pharmacy.low-stock', 'fas fa-exclamation-triangle', 'Low Stock'],
        ],
    ];

    /**
     * Resolve the slug of the currently authenticated user, or null if guest.
     */
    public static function currentRole(): ?string
    {
        $user = Auth::user();
        return $user && $user->role ? ($user->role->slug ?? null) : null;
    }

    /**
     * Whether the currently authenticated user has the named permission.
     */
    public static function allows(string $permission): bool
    {
        $role = self::currentRole();
        if (!$role) {
            return false;
        }
        return self::roleAllows($role, $permission);
    }

    /**
     * Whether a given role has the named permission.
     */
    public static function roleAllows(string $role, string $permission): bool
    {
        $perms = self::ROLE_PERMISSIONS[$role] ?? [];

        if (in_array('*', $perms, true)) {
            return true;
        }

        return in_array($permission, $perms, true);
    }

    /**
     * Whether the current user has at least one of the listed permissions.
     *
     * @param  array<int,string>  $permissions
     */
    public static function allowsAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::allows($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * The dashboard route name appropriate for the current user.
     */
    public static function dashboardFor(): string
    {
        $role = self::currentRole();
        return self::ROLE_DASHBOARDS[$role] ?? 'hospital.dashboard';
    }

    /**
     * The sidebar menu items appropriate for the current user.
     */
    public static function menuFor(): array
    {
        $role = self::currentRole();
        $items = self::ROLE_MENUS[$role] ?? [];

        // Roles whose menu mirrors cmd (super_admin, admin) get the cmd list.
        if (empty($items) && in_array($role, ['super_admin', 'admin'], true)) {
            $items = self::ROLE_MENUS['cmd'];
        }

        return $items;
    }

    /**
     * Whether the current user is a hospital staff at all
     * (i.e. should see the hospital module in the sidebar).
     */
    public static function isHospitalStaff(): bool
    {
        return in_array(self::currentRole(), [
            'cmd', 'super_admin', 'admin', 'medical_director',
            'doctor', 'consultant', 'nurse', 'matron', 'ward_manager',
            'hospital_receptionist', 'cashier', 'medical_records',
            'pharmacist', 'pharmacy_technician', 'inventory_officer',
            'lab_scientist', 'lab_technician',
            'radiographer', 'radiologist',
            'store_keeper',
            'hospital_admin', 'medical_records_officer',
        ], true);
    }
}