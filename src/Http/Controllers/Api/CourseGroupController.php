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
use Ctrlweb\BadgeFactor2\Http\Resources\PathwayPageResource;


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
            
            $groups = $query->orderByDesc('updated_at')->paginate(12);

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
            'is_pathway'           => 'nullable|boolean',
            'badge_categories'       => 'nullable|array',
            'tags'                  => 'nullable|array'
        ]);
      
        $badgeCategory = request()->input('badge_category');
        $badgeCategories = request()->input('badge_categories');

        $items = [];
        $paginatedCollection = new Collection();
        $pathwayQuery = null;
        
        if ((!empty($badgeCategory) && $badgeCategory !== 'certification') || !empty($badgeCategories)) {
            $query = BadgePage::query()
                    ->when(request()->input('is_pathway'),function($q){
                        return $q->whereRaw('1 = 0');
                    })
                    ->when(request()->input('is_brandnew'),function($q){
                        return $q->IsBrandnew();
                    })
                    ->when(request()->input('is_featured'),function($q){
                        return $q->where('is_featured', request()->input('is_featured'));
                    })
                    ->when(!empty($badgeCategories), function($q) use ($badgeCategories){
                        $locale = app()->getLocale();

                        return $q->whereHas('badgeCategory', function ( $query) use ($locale, $badgeCategories) {
                            $query->whereIn("slug->{$locale}", $badgeCategories);
                        });
                    })
                    ->isPublished()->where('is_hidden', false);

            $groups = $query->orderBy('id', request()->input('order_by') ?? 'desc')->paginate(12);
            
            $paginatedCollection = BadgePageResource::collection($groups);

            $pathwayQuery = PathwayPaginator::queryPathWays('is_badgepage', request()->input('q'));               
        } else {  

            $query = CourseGroup::query()
                    ->when(request()->input('is_pathway'),function($q){
                        return $q->whereRaw('1 = 0');
                    })
                    ->when(request()->input('is_brandnew'),function($q){
                        return $q->IsBrandnew();
                    })
                    ->when(request()->input('is_featured'),function($q){
                        return $q->where('is_featured', request()->input('is_featured'));
                    })
                    ->whereHas('courses', function($course_query){
                        return $course_query->whereHas('badgePage', function($badge_page_query){
                            return $badge_page_query->isPublished();
                        });
                    })
                    ->when($request->tags && !empty($request->tags), function($q) use ($request) {
                        return $q->whereHas("tags", function($tagQuery) use ($request) {
                            return $tagQuery->whereIn("tags.id", $request->tags);
                        });
                    })           
                    ->where('is_hidden', false);
            
            $groups = $query->orderBy('updated_at', request()->input('order_by') ?? 'desc')->paginate(12);
            $paginatedCollection = CourseGroupResource::collection($groups);

            $pathwayQuery = PathwayPaginator::queryPathWays('is_autoformation', request()->input('q'));
        }
        
        $perPage = 12 - count($groups);
        $lastPage =  $paginatedCollection->resource?->lastPage() ?? 0;
        $currenPage = (($request->page ?? 1) - $lastPage);

        $pathways = new Collection();

        if($perPage > 0 && !request()->input('issuer') && !request()->input('course_group_category') && empty($badgeCategories)
            &&  ( !$request->tags || empty($request->tags)) && !request()->input('is_featured') && !request()->input('is_brandnew') ) {


            // items from pathways that inluded into the incompleted page
            $bridge = PathwayPaginator::getBridge($paginatedCollection) ;
            // Manually calculate the offset
            $offset = PathwayPaginator::getOffset($currenPage, $perPage, $bridge);   

            $pathways = $pathwayQuery->orderBy('created_at', request()->input('order_by') ?? 'desc')
                        ->skip($offset)->take($perPage)->get();
        }else {
            //return void
            $pathwayQuery = $pathwayQuery->whereRaw('1 = 0');
        }
        $pathways = PathwayPageResource::collection($pathways);
        
        $items = $paginatedCollection->getCollection()->toArray();            
        $itemPathways = $pathways->collection->toArray();
        // Append the external element
        if(!empty($itemPathways)){
            $items = array_merge($items, $itemPathways);
        } 

        // Create a new paginator with the updated items
        $newPaginatedCollection = new LengthAwarePaginator(
            collect($items), // The updated items collection
            $paginatedCollection->total() + $pathwayQuery->count(), // Adjust total count
            $paginatedCollection->perPage(), // Items per page
            $paginatedCollection->currentPage() // Current page
            
        );

        return $newPaginatedCollection;
    }

    public function show(string $locale, $slug)
    {
        return CourseGroupResource::make($slug);
    }
}
