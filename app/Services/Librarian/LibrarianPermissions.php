<?php

namespace App\Services\Librarian;

/**
 * Centralised role → permission map for the library module.
 *
 * Covers books cataloguing, borrowing workflow, member management,
 * and dashboard configuration. Mirrors the shape of
 * `HospitalPermissions::ROLE_PERMISSIONS`.
 */
class LibrarianPermissions
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin'       => ['*'],
        'cmd'         => ['*'],

        'librarian' => [
            'librarian.books.view',
            'librarian.books.create',
            'librarian.books.edit',
            'librarian.books.delete',
            'librarian.books.receive',
            'librarian.books.adjust',
            'librarian.borrowing.view',
            'librarian.borrowing.issue',
            'librarian.borrowing.return',
            'librarian.borrowing.renew',
            'librarian.borrowing.export',
            'librarian.members.view',
            'librarian.members.create',
            'librarian.members.edit',
            'librarian.dashboard.view',
            'librarian.dashboard.configure',
        ],

        'library_officer' => [
            'librarian.books.view',
            'librarian.books.create',
            'librarian.books.edit',
            'librarian.borrowing.view',
            'librarian.borrowing.issue',
            'librarian.borrowing.return',
            'librarian.borrowing.renew',
            'librarian.members.view',
            'librarian.members.create',
            'librarian.dashboard.view',
        ],

        'library_assistant' => [
            'librarian.books.view',
            'librarian.borrowing.view',
            'librarian.borrowing.issue',
            'librarian.borrowing.return',
            'librarian.members.view',
            'librarian.dashboard.view',
        ],
    ];
}