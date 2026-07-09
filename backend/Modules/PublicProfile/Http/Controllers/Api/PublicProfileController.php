<?php

namespace Modules\PublicProfile\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PublicProfile\Http\Requests\Api\CommentRequest;
use Modules\PublicProfile\Http\Requests\Api\ContactRequest;
use Modules\PublicProfile\Http\Requests\Api\FollowRequest;
use Modules\PublicProfile\Http\Requests\Api\ProofRequestRequest;
use Modules\PublicProfile\Http\Requests\Api\RateRequest;
use Modules\PublicProfile\Http\Requests\Api\ScheduleRequest;
use Modules\PublicProfile\Http\Requests\Api\TestimonialRequest;
use Modules\PublicProfile\Http\Requests\Api\UpdatePublicProfileRequest;
use Modules\PublicProfile\Services\PublicProfileService;

class PublicProfileController extends Controller
{
    public function __construct(private readonly PublicProfileService $service) {}

    // ===== المالك (مصادقة) =====
    public function showMine(Request $request)
    {
        return $this->dataResponse($this->service->getMine($request->user()->id, $request->user()->name));
    }

    public function updateMine(UpdatePublicProfileRequest $request)
    {
        return $this->dataResponse(
            $this->service->update($request->user()->id, $request->user()->name, $request->validated())
        );
    }

    // ===== العام (بلا مصادقة) =====
    public function show(string $slug)
    {
        return $this->dataResponse($this->service->getBySlug($slug));
    }

    public function view(string $slug)
    {
        $this->service->registerView($slug);

        return response()->noContent();
    }

    public function follow(FollowRequest $request, string $slug)
    {
        $following = $request->validated()['following'] ?? null;

        return $this->dataResponse($this->service->toggleFollow($slug, $following));
    }

    public function rate(RateRequest $request, string $slug)
    {
        return $this->dataResponse($this->service->rate($slug, (int) $request->validated()['stars']));
    }

    public function comment(CommentRequest $request, string $slug)
    {
        return response()->json($this->dataResponse($this->service->addComment($slug, $request->validated())), 201);
    }

    public function contact(ContactRequest $request, string $slug)
    {
        $this->service->contact($slug, $request->validated());

        return response()->noContent();
    }

    public function schedule(ScheduleRequest $request, string $slug)
    {
        $this->service->schedule($slug, $request->validated());

        return response()->noContent();
    }

    public function testimonial(TestimonialRequest $request, string $slug)
    {
        return response()->json($this->dataResponse($this->service->addTestimonial($slug, $request->validated())), 201);
    }

    public function proofRequest(ProofRequestRequest $request, string $slug)
    {
        $this->service->requestProof($slug, $request->validated());

        return response()->noContent();
    }
}
