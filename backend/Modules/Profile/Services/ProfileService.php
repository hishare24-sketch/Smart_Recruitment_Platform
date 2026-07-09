<?php

namespace Modules\Profile\Services;

use Illuminate\Support\Carbon;
use Modules\Profile\Entities\Profile;

class ProfileService
{
    public function forUser(int $userId): Profile
    {
        return Profile::firstOrCreate(['user_id' => $userId]);
    }

    public function update(int $userId, array $data): Profile
    {
        $profile = $this->forUser($userId);

        foreach (['headline', 'summary', 'skills', 'experiences', 'certificates', 'prefs'] as $key) {
            if (array_key_exists($key, $data)) {
                $profile->{$key} = $data[$key];
            }
        }

        $profile->save();

        return $profile;
    }

    public function addSkill(int $userId, array $data): array
    {
        $profile = $this->forUser($userId);
        $skills = $profile->skills ?? [];

        $skill = [
            'id' => $this->nextId($skills),
            'name' => $data['name'],
            'selfLevel' => (int) $data['selfLevel'],
            'category' => $data['category'] ?? null,
            'proofs' => [[
                'id' => 1,
                'type' => 'self',
                'label' => __('Self assessment'),
                'date' => Carbon::now()->toDateString(),
            ]],
        ];

        $skills[] = $skill;
        $profile->skills = $skills;
        $profile->save();

        return $skill;
    }

    public function removeSkill(int $userId, int $skillId): void
    {
        $profile = $this->forUser($userId);
        $profile->skills = array_values(array_filter(
            $profile->skills ?? [],
            fn ($s) => (int) ($s['id'] ?? 0) !== $skillId,
        ));
        $profile->save();
    }

    public function addProof(int $userId, int $skillId, array $data): ?array
    {
        $profile = $this->forUser($userId);
        $skills = $profile->skills ?? [];
        $updated = null;

        foreach ($skills as &$skill) {
            if ((int) ($skill['id'] ?? 0) === $skillId) {
                $proofs = $skill['proofs'] ?? [];
                $proofs[] = [
                    'id' => $this->nextId($proofs),
                    'type' => $data['type'] ?? 'project',
                    'label' => $data['label'] ?? '',
                    'date' => $data['date'] ?? Carbon::now()->toDateString(),
                ];
                $skill['proofs'] = $proofs;
                $updated = $skill;
            }
        }
        unset($skill);

        if ($updated === null) {
            return null;
        }

        $profile->skills = $skills;
        $profile->save();

        return $updated;
    }

    public function resolveProofRequest(int $userId, int $requestId, bool $accept): void
    {
        $profile = $this->forUser($userId);
        $requests = $profile->proof_requests ?? [];

        $request = collect($requests)->firstWhere('id', $requestId);
        $profile->proof_requests = array_values(array_filter(
            $requests,
            fn ($r) => (int) ($r['id'] ?? 0) !== $requestId,
        ));

        if ($accept && $request) {
            $skills = $profile->skills ?? [];
            foreach ($skills as &$skill) {
                if (($skill['name'] ?? null) === ($request['skill'] ?? null)) {
                    $proofs = $skill['proofs'] ?? [];
                    $proofs[] = [
                        'id' => $this->nextId($proofs),
                        'type' => 'endorsement',
                        'label' => trim(($request['from'] ?? '').' — '.($request['relation'] ?? ''), ' —'),
                        'date' => Carbon::now()->toDateString(),
                    ];
                    $skill['proofs'] = $proofs;
                }
            }
            unset($skill);
            $profile->skills = $skills;
        }

        $profile->save();
    }

    /** طلب إثبات وارد من زائر (عبر الصفحة العامة) → يصل ملف المالك الخاص. */
    public function pushProofRequest(int $userId, array $data): array
    {
        $profile = $this->forUser($userId);
        $requests = $profile->proof_requests ?? [];
        $request = [
            'id' => $this->nextId($requests),
            'from' => $data['from'] ?? '',
            'relation' => $data['relation'] ?? '',
            'skill' => $data['skill'] ?? '',
            'date' => Carbon::now()->toDateString(),
        ];
        $requests[] = $request;
        $profile->proof_requests = $requests;
        $profile->save();

        return $request;
    }

    private function nextId(array $items): int
    {
        return (int) (collect($items)->max('id') ?? 0) + 1;
    }
}
