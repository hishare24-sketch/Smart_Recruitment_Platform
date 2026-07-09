<?php

namespace Modules\PublicProfile\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Profile\Services\ProfileService;
use Modules\PublicProfile\Entities\PublicProfile;

class PublicProfileService
{
    public function __construct(private readonly ProfileService $profileService) {}

    /** العرض العام: doc مسطّحًا مع slug والعدّادات الحيّة والتفاعلات تعلوه. */
    public function present(PublicProfile $page): array
    {
        return array_merge($page->doc ?? [], [
            'slug' => $page->slug,
            'stats' => $page->stats,
            'testimonials' => $page->testimonials ?? [],
            'comments' => $page->comments ?? [],
        ]);
    }

    public function getBySlug(string $slug): array
    {
        return $this->present($this->bySlugOrFail($slug));
    }

    /** صفحة المالك (get-or-create) — تعيد {slug, stats, doc} كما يتوقّع المخزن. */
    public function getMine(int $userId, string $name): array
    {
        $page = $this->getOrCreateForUser($userId, $name);

        return ['slug' => $page->slug, 'stats' => $page->stats, 'doc' => $page->doc ?? (object) []];
    }

    public function update(int $userId, string $name, array $data): array
    {
        $page = $this->getOrCreateForUser($userId, $name);

        if (array_key_exists('doc', $data)) {
            $page->doc = $data['doc'];
        }
        $page->save();

        return $this->present($page);
    }

    public function registerView(string $slug): void
    {
        $page = $this->bySlugOrFail($slug);
        $stats = $page->stats;
        $stats['views'] = ($stats['views'] ?? 0) + 1;
        $page->stats = $stats;
        $page->save();
    }

    public function toggleFollow(string $slug, ?bool $following): array
    {
        $page = $this->bySlugOrFail($slug);
        $next = $following ?? true;
        $stats = $page->stats;
        $stats['followersCount'] = max(0, ($stats['followersCount'] ?? 0) + ($next ? 1 : -1));
        $page->stats = $stats;
        $page->save();

        return ['following' => $next, 'followersCount' => $stats['followersCount']];
    }

    public function rate(string $slug, int $stars): array
    {
        $page = $this->bySlugOrFail($slug);
        $stats = $page->stats;
        $count = ($stats['ratingCount'] ?? 0) + 1;
        $avg = round((($stats['avgRating'] ?? 0) * ($stats['ratingCount'] ?? 0) + $stars) / $count, 2);
        $stats['avgRating'] = $avg;
        $stats['ratingCount'] = $count;
        $page->stats = $stats;
        $page->save();

        return ['avgRating' => $avg, 'ratingCount' => $count];
    }

    public function addComment(string $slug, array $data): array
    {
        $page = $this->bySlugOrFail($slug);
        $comments = $page->comments ?? [];
        $comment = [
            'id' => $this->nextId($comments),
            'author' => $data['author'],
            'text' => $data['text'],
            'date' => Carbon::now()->toDateString(),
            'hidden' => false,
        ];
        $comments[] = $comment;
        $page->comments = $comments;
        $page->save();

        return $comment;
    }

    public function contact(string $slug, array $data): void
    {
        $page = $this->bySlugOrFail($slug);
        $page->inbox = array_merge($page->inbox ?? [], [array_merge(['kind' => 'contact'], $data, ['at' => Carbon::now()->toISOString()])]);
        $stats = $page->stats;
        $stats['contacts'] = ($stats['contacts'] ?? 0) + 1;
        $page->stats = $stats;
        $page->save();
    }

    public function schedule(string $slug, array $data): void
    {
        $page = $this->bySlugOrFail($slug);
        $page->inbox = array_merge($page->inbox ?? [], [array_merge(['kind' => 'schedule'], $data, ['at' => Carbon::now()->toISOString()])]);
        $stats = $page->stats;
        $stats['meetings'] = ($stats['meetings'] ?? 0) + 1;
        $page->stats = $stats;
        $page->save();
    }

    public function addTestimonial(string $slug, array $data): array
    {
        $page = $this->bySlugOrFail($slug);
        $testimonials = $page->testimonials ?? [];
        $testimonial = [
            'id' => $this->nextId($testimonials),
            'author' => $data['author'],
            'authorRole' => $data['authorRole'] ?? null,
            'excerpt' => $data['excerpt'],
            'visible' => false,
            'likes' => 0,
        ];
        $testimonials[] = $testimonial;
        $page->testimonials = $testimonials;
        $page->save();

        return $testimonial;
    }

    /** زائر يطلب إثبات مهارة → يصل مالك الصفحة في ملفه الخاص (موديول Profile). */
    public function requestProof(string $slug, array $data): void
    {
        $page = $this->bySlugOrFail($slug);
        $this->profileService->pushProofRequest($page->user_id, $data);
    }

    private function bySlugOrFail(string $slug): PublicProfile
    {
        return PublicProfile::where('slug', $slug)->firstOr(fn () => abort(404, __('Profile not found')));
    }

    private function getOrCreateForUser(int $userId, string $name): PublicProfile
    {
        $page = PublicProfile::where('user_id', $userId)->first();
        if ($page) {
            return $page;
        }

        $slug = $this->slugify($name, $userId);
        if (PublicProfile::where('slug', $slug)->exists()) {
            $slug = "{$slug}-{$userId}";
        }

        return PublicProfile::create([
            'user_id' => $userId,
            'slug' => $slug,
            'doc' => ['displayName' => $name],
            'stats' => $this->defaultStats(),
            'testimonials' => [],
            'comments' => [],
            'inbox' => [],
        ]);
    }

    private function slugify(string $name, int $fallback): string
    {
        $base = Str::slug($name);

        return $base !== '' ? $base : "user-{$fallback}";
    }

    private function defaultStats(): array
    {
        return [
            'views' => 0, 'shares' => 0, 'contacts' => 0, 'meetings' => 0,
            'followersCount' => 0, 'avgRating' => 0, 'ratingCount' => 0,
        ];
    }

    private function nextId(array $items): int
    {
        return (int) (collect($items)->max('id') ?? 0) + 1;
    }
}
