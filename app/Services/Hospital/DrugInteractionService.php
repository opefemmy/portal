<?php

namespace App\Services\Hospital;

use App\Models\Hospital\HospitalDrug;
use Illuminate\Support\Facades\DB;

/**
 * Lightweight drug interaction checker.
 *
 * Existing pharmacists select drugs one at a time. When a doctor
 * prescribes multiple items in a single consultation, this service
 * summarises possible interactions to make the pharmacist's life
 * easier — without re-architecting the dispensing flow.
 *
 * Source is the local `hospital_drugs.interactions` text field (or a
 * future table if one is added). No external API call.
 */
class DrugInteractionService
{
    /**
     * Look up possible interactions for a set of drug IDs.
     *
     * @param  array<int>  $drugIds
     * @return array<int,array<string,mixed>>
     */
    public function check(array $drugIds): array
    {
        if (count($drugIds) < 2) {
            return [];
        }

        $drugs = HospitalDrug::whereIn('id', $drugIds)->get();
        $interactions = [];

        foreach ($drugs as $a) {
            foreach ($drugs as $b) {
                if ($a->id >= $b->id) {
                    continue;
                }
                $matches = $this->match($a, $b);
                if ($matches) {
                    $interactions[] = [
                        'drug_a'   => $a->name,
                        'drug_b'   => $b->name,
                        'severity' => $matches['severity'],
                        'note'     => $matches['note'],
                    ];
                }
            }
        }

        return $interactions;
    }

    /**
     * Match two drugs for interaction. Returns severity + note or null.
     *
     * @return array{severity:string,note:string}|null
     */
    private function match(HospitalDrug $a, HospitalDrug $b): ?array
    {
        $haystacks = [
            strtolower((string) $a->interactions),
            strtolower((string) $b->interactions),
        ];

        $aName = strtolower($a->name);
        $bName = strtolower($b->name);

        foreach ($haystacks as $hay) {
            if ($hay !== '' && (str_contains($hay, $aName) || str_contains($hay, $bName))) {
                return [
                    'severity' => 'caution',
                    'note'     => 'Possible interaction noted on drug record. Please review.',
                ];
            }
        }

        // Simple heuristics by drug class
        $classes = [
            'nsaid'   => ['ibuprofen', 'diclofenac', 'aspirin', 'naproxen'],
            'warf'    => ['warfarin'],
            'steroid' => ['prednisolone', 'dexamethasone', 'hydrocortisone'],
        ];

        foreach ($classes as $class => $members) {
            $aIn = collect($members)->contains(fn ($m) => str_contains($aName, $m));
            $bIn = collect($members)->contains(fn ($m) => str_contains($bName, $m));
            if ($aIn && $bIn && $class === 'nsaid') {
                return ['severity' => 'moderate', 'note' => 'Two NSAIDs combined — increased GI/bleed risk.'];
            }
        }

        return null;
    }
}
