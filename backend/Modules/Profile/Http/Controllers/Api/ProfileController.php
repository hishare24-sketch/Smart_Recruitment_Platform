<?php

namespace Modules\Profile\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Profile\Http\Requests\Api\AddProofRequest;
use Modules\Profile\Http\Requests\Api\AddSkillRequest;
use Modules\Profile\Http\Requests\Api\ResolveProofRequestRequest;
use Modules\Profile\Http\Requests\Api\UpdateProfileRequest;
use Modules\Profile\Http\Resources\Api\ProfileResource;
use Modules\Profile\Services\ProfileService;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $service) {}

    public function show(Request $request)
    {
        return $this->dataResponse(ProfileResource::make($this->service->forUser($request->user()->id)));
    }

    public function update(UpdateProfileRequest $request)
    {
        $profile = $this->service->update($request->user()->id, $request->validated());

        return $this->dataResponse(ProfileResource::make($profile));
    }

    public function addSkill(AddSkillRequest $request)
    {
        $skill = $this->service->addSkill($request->user()->id, $request->validated());

        return response()->json($this->dataResponse($skill), 201);
    }

    public function removeSkill(Request $request, int $skillId)
    {
        $this->service->removeSkill($request->user()->id, $skillId);

        return response()->noContent();
    }

    public function addProof(AddProofRequest $request, int $skillId)
    {
        $skill = $this->service->addProof($request->user()->id, $skillId, $request->validated());

        if ($skill === null) {
            return $this->errorResponse(__('Skill not found'), 404);
        }

        return response()->json($this->dataResponse($skill), 201);
    }

    public function proofRequests(Request $request)
    {
        return $this->dataResponse($this->service->forUser($request->user()->id)->proof_requests ?? []);
    }

    public function resolveProofRequest(ResolveProofRequestRequest $request, int $id)
    {
        $this->service->resolveProofRequest($request->user()->id, $id, (bool) $request->validated()['accept']);

        return response()->noContent();
    }
}
