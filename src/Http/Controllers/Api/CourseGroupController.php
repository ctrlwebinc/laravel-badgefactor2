<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\PathwayPage;
use \Illuminate\Pagination\LengthAwarePaginator;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Services\Pathway\PathwayPaginator;
use Ctrlweb\BadgeFactor2\Http\Resources\Badges\BadgePageResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseGroupResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseGroupSearchEngineResource;
use Ctrlweb\BadgeFactor2\Http\Resources\PathwayPageResource;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgeCategory;
use App\Helpers\CacheHelper;
use Ctrlweb\BadgeFactor2\Http\Resources\Badges\BadgePageSearchEngineResource;
use Illuminate\Database\Eloquent\Builder;

/**
 * @tags Groupes de cours (formations)
 */
class CourseGroupController extends Controller
{
    /**
     * Liste des groupes de cours.
     *
     * Il est possible de filtrer les formations par catégorie de cours et par mot-clé.
     *
     * @param Request $request
     *
     * @return void
     */
    public function index(string $locale, Request $request)
    {
        $request->validate([
            'course_group_category' => 'nullable|integer',
            'q'                     => 'nullable|string',
            'issuer'                => 'nullable|string',
            'badge_category'        => 'string',
        ]);

        $badgeCategory = request()->input('badge_category');

        if (!empty($badgeCategory) && $badgeCategory !== 'certification') {
            
            $query = BadgePage::query()->isPublished()->where('is_hidden', false);
            $groups = $query->orderByDesc('id')->paginate(12);

            return BadgePageResource::collection($groups);
        } else {  

            $query = CourseGroup::query()->whereHas('courses', function($course_query){
                return $course_query->whereHas('badgePage', function($badge_page_query){
                    return $badge_page_query->isPublished();
                });
            })->where('is_hidden', false);
            
            $groups = $query->orderByDesc('id')->paginate(12);

            return CourseGroupResource::collection($groups);
        }
    }

    /**
     * Liste des groupes de cours.
     *
     * Il est possible de filtrer les formations par catégorie de cours et par mot-clé.
     *
     * @param Request $request
     *
     * @return void
     */
    public function new_index(string $locale, Request $request)
    {
        $request->validate([
            'course_group_category' => 'nullable|integer',
            'q'                     => 'nullable|string',
            'issuer'                => 'nullable|string',
            'badge_category'        => 'nullable|string',
            'order_by'              => 'nullable|string',
            'is_brandnew'           => 'nullable|boolean',
            'is_featured'           => 'nullable|boolean',
            'is_pathway'            => 'nullable|boolean',
            'badge_categories'      => 'nullable|array',
            'tags'                  => 'nullable|array',
            'increment_per_page'    => 'nullable|integer'
        ]);

        $cacheKeyFinal = 'search_engine_response_' . md5(json_encode($request->all()));

        return CacheHelper::rememberWithGroup('search_engine_response', $cacheKeyFinal, (24 * 60), function () use ($locale, $request) {
            $badgeCategory   = $request->input('badge_category');
            $badgeCategories = $request->input('badge_categories');

            $items            = [];
            $paginatedCollection = new \Illuminate\Support\Collection();
            $pathwayQuery     = null;

            $itemParPage = $request->increment_per_page ? (intval($request->increment_per_page) * 12) : 12;

            $tags = $request->tags && !empty($request->tags)
                ? array_filter($request->tags, function ($tag) {
                    return $tag != null;
                })
                : false;

            if ((!empty($badgeCategory) && $badgeCategory !== 'certification') || !empty($badgeCategories)) {
                $brandnewIds = BadgePage::takeOnlyBrandnew()->pluck('id')->toArray();

                $groups = BadgePage::withoutGlobalScopes(['badgeCategory'])
                    ->whereDoesntHave('badgeCategory', function (Builder $q) {
                        $localeInner = app()->getLocale();
                        $q->where("slug->{$localeInner}", '=', 'certification');
                    })
                    ->when($request->input('is_pathway'), fn($q) => $q->whereRaw('1 = 0'))
                    ->when($request->input('is_brandnew'), fn($q) => $q->IsBrandnew())
                    ->when($request->input('is_featured'), fn($q) => $q->where('is_featured', $request->input('is_featured')))
                    ->when(!empty($badgeCategories), function ($q) use ($badgeCategories) {
                        $badgeCategoryIds = collect($badgeCategories)
                            ->map(fn($category) => BadgeCategory::findBySlug($category)->first()?->id)
                            ->filter()
                            ->toArray();
                        return $q->whereIn("badge_category_id", $badgeCategoryIds);
                    })
                    ->isPublished()
                    ->where('is_hidden', false)
                    ->when(!empty( $brandnewIds ) , function ($q) use ($brandnewIds) {
                        $idsString = implode(',', $brandnewIds);
                        $q->orderByRaw("
                            CASE 
                                WHEN is_featured = 1 THEN 0
                                WHEN id IN ($idsString) THEN 1
                                ELSE 2
                            END ASC
                        ");
                    })
                    ->when(
                        $request->input('order_by'),
                        function ($q) use ($request, $locale) {
                            return $q->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.\"{$locale}\"')) COLLATE utf8mb4_unicode_ci " . $request->input('order_by'));
                        },
                        function ($q) {
                            return $q->orderBy('created_at', 'desc');
                        }
                    )
                    ->paginate($itemParPage);

                $paginatedCollection = BadgePageSearchEngineResource::collection($groups);

                $pathwayQuery = (
                    !request()->input('is_pathway') ||
                    request()->input('issuer') ||
                    request()->input('is_brandnew') ||
                    request()->input('is_featured') ||
                    $tags ||
                    request()->input('badge_categories')
                )
                    ? PathwayPage::whereRaw('1 = 0')
                    : PathwayPaginator::queryPathWays('is_badgepage', request()->input('q'));
            } else {
                $cacheKey = 'course_groups_' . md5(json_encode($request->all()));
                $brandnewIds = CourseGroup::takeOnlyBrandnew()->pluck('id')->toArray();

                $groups = CourseGroup::query()
                    ->when($request->input('is_pathway'), fn($q) => $q->whereRaw('1 = 0'))
                    ->when($request->input('is_brandnew'), fn($q) => $q->IsBrandnew())
                    ->when($request->input('is_featured'), fn($q) => $q->where('is_featured', $request->input('is_featured')))
                    //->whereHas('courses', fn($q) => $q->whereHas('badgePage', fn($bq) => $bq->isPublished()))
                    ->when($tags, fn($q) => $q->whereHas("tags", fn($tagQuery) => $tagQuery->whereIn("tags.id", $tags)))
                    ->withoutGlobalScopes(['issuer'])->whereHas('courses', function($course_query){
                        return $course_query->whereHas('badgePage', function($badge_page_query){
                            return $badge_page_query->withoutGlobalScopes(['issuer'])->isPublished();
                        });
                    })
                    ->where('is_hidden', false)
                    ->when(!empty( $brandnewIds ), function ($q) use ($brandnewIds) {
                        $idsString = implode(',', $brandnewIds);
                        $q->orderByRaw("
                            CASE 
                                WHEN is_featured = 1 THEN 0
                                WHEN id IN ($idsString) THEN 1
                                ELSE 2
                            END ASC
                        ");
                    })
                    ->when(
                        $request->input('order_by'),
                        function ($q) use ($request, $locale) {
                            return $q->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.\"{$locale}\"')) COLLATE utf8mb4_unicode_ci " . $request->input('order_by'));
                        },
                        function ($q) {
                            return $q->orderBy('created_at', 'desc');
                        }
                    )
                    ->paginate($itemParPage);

                $paginatedCollection = CourseGroupSearchEngineResource::collection($groups);
                $pathwayQuery = (
                    !request()->input('is_pathway') ||
                    request()->input('issuer') ||
                    request()->input('is_brandnew') ||
                    request()->input('is_featured') ||
                    $tags ||
                    request()->input('badge_categories')
                )
                    ? PathwayPage::whereRaw('1 = 0')
                    : PathwayPaginator::queryPathWays('is_autoformation', request()->input('q'));
            }

            $items = $paginatedCollection->getCollection()->toArray();
            $itemPathways = PathwayPageResource::collection($pathwayQuery->get())->collection->toArray();

            if (!empty($itemPathways)) {
                $items = array_merge($items, $itemPathways);
            }

            return new LengthAwarePaginator(
                collect($items),
                $paginatedCollection->total() + $pathwayQuery->count(),
                $paginatedCollection->perPage(),
                $paginatedCollection->currentPage()
            );
        });
    }




    public function show(string $locale, $slug)
    {
        return CourseGroupResource::make($slug);
    }
}
