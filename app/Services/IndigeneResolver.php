<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\User;

/**
 * Single source of truth for "is this person an indigene of Ekiti?".
 *
 * The substring test was previously duplicated on User, Applicant, and
 * Application. Anything that needs to classify a student or applicant as
 * indigene vs non-indigene routes through here.
 */
class IndigeneResolver
{
    /** Substrings (lowercased) that map to "indigene". */
    private const INDIGENE_KEYWORDS = ['ekiti', 'ekiti state'];

    /**
     * Resolve the category for any object that exposes a state field.
     * Accepted shapes: User (uses `state`), Applicant/Application (use `state_of_origin`).
     */
    public static function categoryFor(object $entity): string
    {
        $state = strtolower((string) self::extractState($entity));
        if ($state === '') {
            return 'non_indigene';
        }

        foreach (self::INDIGENE_KEYWORDS as $keyword) {
            if (str_contains($state, $keyword)) {
                return 'indigene';
            }
        }

        return 'non_indigene';
    }

    public static function isIndigene(object $entity): bool
    {
        return self::categoryFor($entity) === 'indigene';
    }

    private static function extractState(object $entity): ?string
    {
        if ($entity instanceof User) {
            return $entity->state ?? null;
        }
        if ($entity instanceof Applicant || $entity instanceof Application) {
            return $entity->state_of_origin ?? null;
        }
        // Generic fallback for any other model exposing either column.
        return $entity->state_of_origin ?? $entity->state ?? null;
    }
}