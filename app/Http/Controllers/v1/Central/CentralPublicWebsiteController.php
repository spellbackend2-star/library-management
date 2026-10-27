<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreCentralContactMessageRequest;
use App\Models\CentralBlog;
use App\Models\CentralBlogCategory;
use App\Models\CentralContactMessage;
use App\Models\CentralPage;
use App\Models\CentralTestimonial;
use App\Models\SubscriptionPlan;
use App\Services\CentralSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralPublicWebsiteController extends Controller
{
    public function __construct(
        protected CentralSettingService $settings
    ) {}

    public function home(): JsonResponse
    {
        $published = fn ($query) => $query->where('is_published', true)
            ->where(fn ($dateQuery) => $dateQuery
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $this->publicWebsiteSettings(),
                'page' => $published(CentralPage::query())
                    ->where('slug', 'home')
                    ->first(),
                'plans' => SubscriptionPlan::query()
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->get(['id', 'name', 'description', 'price', 'duration', 'duration_unit']),
                // Testimonials use is_published (there is no published_at column).
                'testimonials' => CentralTestimonial::query()
                    ->where('is_published', true)
                    ->orderBy('sort_order')
                    ->latest('id')
                    ->limit(6)
                    ->get(['id', 'name', 'role_or_company', 'quote', 'photo', 'rating']),
                'blogs' => $published(CentralBlog::query()->with('category:id,name,slug'))
                    ->latest('published_at')
                    ->limit(3)
                    ->get(['id', 'category_id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at']),
            ],
        ]);
    }

    public function settings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->publicWebsiteSettings(),
        ]);
    }

    public function page(string $slug): JsonResponse
    {
        $page = CentralPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where(fn ($query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()))
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $page]);
    }

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'description', 'price', 'duration', 'duration_unit']);

        return response()->json(['success' => true, 'data' => $plans]);
    }

    public function categories(): JsonResponse
    {
        $categories = CentralBlogCategory::query()
            ->where('is_active', true)
            ->whereHas('blogs', fn ($query) => $this->publishedBlogQuery($query))
            ->withCount(['blogs as published_blogs_count' => fn ($query) => $this->publishedBlogQuery($query)])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function blogs(Request $request): JsonResponse
    {
        $request->validate([
            'category' => ['nullable', 'string', 'exists:central_blog_categories,slug'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = CentralBlog::query()
            ->with('category:id,name,slug')
            ->where('is_published', true)
            ->where(fn ($builder) => $builder
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($category) => $category
                ->where('slug', $request->string('category')->toString())
                ->where('is_active', true));
        }

        $blogs = $query->latest('published_at')->paginate($request->integer('per_page', 9));

        return response()->json([
            'success' => true,
            'data' => $blogs->items(),
            'meta' => [
                'current_page' => $blogs->currentPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
                'last_page' => $blogs->lastPage(),
            ],
        ]);
    }

    public function blog(string $slug): JsonResponse
    {
        $blog = CentralBlog::query()
            ->with('category:id,name,slug')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where(fn ($query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()))
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $blog]);
    }

    public function testimonials(): JsonResponse
    {
        $testimonials = CentralTestimonial::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->latest('id')
            ->get(['id', 'name', 'role_or_company', 'quote', 'photo', 'rating']);

        return response()->json(['success' => true, 'data' => $testimonials]);
    }

    public function contact(StoreCentralContactMessageRequest $request): JsonResponse
    {
        CentralContactMessage::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Your message has been received.',
        ], 201);
    }

    private function publicWebsiteSettings(): array
    {
        $settings = $this->settings->getGroup(CentralSettingService::WEBSITE_GROUP);
        $allowed = [
            'site_name', 'site_title', 'site_tagline', 'site_description',
            'logo', 'favicon', 'contact_email', 'contact_phone', 'contact_address',
            'website_url', 'support_email', 'support_phone', 'facebook_url',
            'instagram_url', 'twitter_url', 'linkedin_url', 'youtube_url',
            'terms_url', 'privacy_url', 'refund_policy_url', 'footer_text', 'copyright_text',
        ];

        return array_intersect_key($settings, array_flip($allowed));
    }

    private function publishedBlogQuery($query)
    {
        return $query->where('is_published', true)
            ->where(fn ($publishedQuery) => $publishedQuery
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }
}
