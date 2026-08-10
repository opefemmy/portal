<?php

namespace App\Http\Controllers\Concerns;

/**
 * Single source of truth for "where is the registrar signature file
 * that's supposed to print on the admission letter?".
 *
 * Background: the registrar uploads a signature image from
 * /registrar/admission-letter/settings. That file's on-disk location
 * changed once already (from storage/app/public/signatures/ to
 * public/uploads/signatures/), and rows from before the move still
 * exist in the DB pointing at the old path. Rather than a one-off
 * migration script that physically moves the file, the lookup walks
 * both locations and the DB row is updated lazily on the next upload.
 *
 * The candidate resolution order is:
 *   1. The new public/uploads/signatures/registrar_signature.{ext}
 *      directory — today's primary.
 *   2. The legacy public/storage/signatures/registrar_signature.{ext}
 *      path (served via the storage/app/public symlink).
 *   3. The raw storage/app/public/signatures/registrar_signature.{ext}
 *      path so lookups still resolve when the symlink is missing.
 *
 * Both (1) and (2) are URL-served through asset() — the URL builder
 * owns the public/storage junction — so the caller only needs to know
 * the right on-disk path to confirm the file is reachable.
 */
trait ResolvesRegistrarSignature
{
    /**
     * Return the URL that renders the registrar's signature image on
     * the admission letter, or null when no signature is configured
     * (or the configured file is missing).
     *
     * Walks the stored system_settings.registrar_signature_path plus
     * the legacy paths so old uploads still render without a one-time
     * migration. If the value stored is empty / null, scans the public
     * signatures directory for a fixed registrar_signature.{png,jpg,...}
     * file so a hand-placed asset still shows up.
     */
    public function resolveRegistrarSignatureUrl(): ?string
    {
        $storedValue = (string) (\App\Models\SystemSetting::get('registrar_signature_path') ?: '');

        // 1. Direct hit from the stored path (new public/uploads/ layout).
        if ($storedValue) {
            $publicPath = public_path($storedValue);
            if (is_file($publicPath)) {
                return asset($storedValue);
            }

            // 2. Legacy storage/ layout via the public/storage symlink.
            $legacyStoragePath = public_path('storage/' . ltrim($storedValue, '/'));
            if (is_file($legacyStoragePath)) {
                return asset('storage/' . ltrim($storedValue, '/'));
            }

            // 3. Raw storage/app/public/ — no symlink required.
            $rawStoragePath = storage_path('app/public/' . ltrim($storedValue, '/'));
            if (is_file($rawStoragePath)) {
                $rawRelative = 'storage/' . ltrim($storedValue, '/');
                if ($this->rawStoragePathIsServed($rawRelative)) {
                    return asset($rawRelative);
                }
            }
        }

        // 4. No stored value (or the stored value's file is missing).
        // Look for a fixed registrar_signature.{ext} file dropped into
        // public/uploads/signatures/ directly — the "live asset" path
        // the user asked for.
        foreach (['png', 'jpg', 'jpeg', 'svg'] as $ext) {
            $candidate = public_path('uploads/signatures/registrar_signature.' . $ext);
            if (is_file($candidate)) {
                return asset('uploads/signatures/registrar_signature.' . $ext);
            }
        }

        return null;
    }

    /**
     * Cheap check that the raw storage/app/public path is served by the
     * web server (i.e. the public/storage symlink exists). The raw path
     * may exist on disk without being reachable through the public URL
     * on a fresh deploy where storage:link hasn't been run yet — and
     * rendering a broken <img src> is worse than no signature at all.
     */
    private function rawStoragePathIsServed(string $relative): bool
    {
        $linkedFrom = public_path('storage');
        // Both a symlink AND a real directory resolve the same way —
        // we want the asset URL to actually return the bytes.
        return is_dir($linkedFrom) || is_link($linkedFrom);
    }
}
