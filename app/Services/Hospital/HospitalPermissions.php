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
            'lab.view', 'lab.create',
            'pharmacy.view',
            'prescriptions.view', 'prescriptions.create',
        ],

        'nurse' => [
            'patients.view',
            'appointments.view', 'appointments.check-in',
            'visits.vitals',
            'pharmacy.view',
            'prescriptions.view',
        ],

        'hospital_receptionist' => [
            'patients.view', 'patients.create', 'patients.edit',
            'external-patients.view', 'external-patients.create', 'external-patients.edit',
            'external-patients.appointment', 'external-patients.visit',
            'appointments.view', 'appointments.create',
        ],

        'pharmacist' => [
            'pharmacy.view', 'pharmacy.dispense', 'pharmacy.drugs',
            'pharmacy.receive', 'pharmacy.adjust', 'pharmacy.expire',
            'prescriptions.view', 'prescriptions.dispense',
            'patients.view',
        ],

        'lab_scientist' => [
            'lab.view', 'lab.collect', 'lab.process', 'lab.complete',
            'patients.view',
        ],

        'store_keeper' => [
            'pharmacy.view', 'pharmacy.drugs',
            'pharmacy.receive', 'pharmacy.adjust', 'pharmacy.expire',
        ],
    ];

    /**
     * Sidebar dashboard route name per role slug.
     */
    public const ROLE_DASHBOARDS = [
        'cmd'                 => 'hospital.dashboard',
        'super_admin'         => 'hospital.dashboard',
        'admin'               => 'hospital.dashboard',
        'doctor'              => 'hospital.doctor.dashboard',
        'nurse'               => 'hospital.nurse.dashboard',
        'hospital_receptionist'=> 'hospital.reception.dashboard',
        'pharmacist'          => 'hospital.pharmacy.dashboard',
        'lab_scientist'       => 'hospital.lab.dashboard',
        'store_keeper'        => 'hospital.pharmacy.dashboard',
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
        ],

        'doctor' => [
            ['hospital.doctor.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
            ['hospital.consultations.index', 'fas fa-stethoscope', 'Consultations'],
        ],

        'nurse' => [
            ['hospital.nurse.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
        ],

        'hospital_receptionist' => [
            ['hospital.reception.dashboard', 'fas fa-tachometer-alt', 'Dashboard'],
            ['hospital.external-patients.index', 'fas fa-user-friends', 'External Patients'],
            ['hospital.patients.index', 'fas fa-users', 'Patients'],
            ['hospital.appointments.index', 'fas fa-calendar-check', 'Appointments'],
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
            'cmd', 'super_admin', 'admin',
            'doctor', 'nurse', 'hospital_receptionist',
            'pharmacist', 'lab_scientist', 'store_keeper',
        ], true);
    }
}