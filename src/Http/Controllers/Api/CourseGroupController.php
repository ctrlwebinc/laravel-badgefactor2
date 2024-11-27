<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Badges\BadgePageResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseGroupResource;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Illuminate\Http\Request;
use \Illuminate\Pagination\LengthAwarePaginator;
use Ctrlweb\BadgeFactor2\Services\Pathway\PathwayPaginator;
use Ctrlweb\BadgeFactor2\Models\PathwayPage;
use Illuminate\Support\Collection;


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
            'badge_category'        => 'string',
        ]);

        $badgeCategory = request()->input('badge_category');

        $items = [];
        $paginatedCollection = new Collection();
        $pathwayQuery = null;

        if (!empty($badgeCategory) && $badgeCategory !== 'certification') {
            $query = BadgePage::query()->isPublished()->where('is_hidden', false);
            $groups = $query->orderByDesc('id')->paginate(12);
            $paginatedCollection = BadgePageResource::collection($groups);

            $pathwayQuery = PathwayPaginator::queryPathWays('is_badgepage', $request->q);               
        } else {  

            $query = CourseGroup::query()->whereHas('courses', function($course_query){
                return $course_query->whereHas('badgePage', function($badge_page_query){
                    return $badge_page_query->isPublished();
                });
            })->where('is_hidden', false);
            
            $groups = $query->orderByDesc('updated_at')->paginate(12);
            $paginatedCollection = CourseGroupResource::collection($groups);

            $pathwayQuery = PathwayPaginator::queryPathWays('is_autoformation', $request->q);
        }

        $perPage = 12 - count($groups);
        $lastPage =  $paginatedCollection->resource?->lastPage() ?? 0;
        $currenPage = (($request->page ?? 1) - $lastPage);

        // items from pathways that inluded into the incompleted page
        $bridge = PathwayPaginator::getBridge($paginatedCollection) ;
        // Manually calculate the offset
        $offset = PathwayPaginator::getOffset($currenPage, $perPage, $bridge);   

        $pathways = $perPage > 0 ? $pathwayQuery->skip($offset)->take($perPage)->get() : new Collection();
        
        $items = $paginatedCollection->getCollection()->toArray();            
        $itemPathways = $pathways->toArray();
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
