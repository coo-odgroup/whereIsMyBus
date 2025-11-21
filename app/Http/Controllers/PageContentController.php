<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\PageContentService;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PageContentController extends Controller
{
    use ApiResponser;
    protected $pagecontentService;
    public function __construct(PageContentService $pagecontentService)
    {
        $this->pagecontentService = $pagecontentService;
    }
    /**
     * @OA\Post(
     *     path="/api/GetPageData",
     *     tags={"Get Page descriptions on about-us,terms-conditions,privacy-policy"},
     *     description="Get Page descriptions on about-us,terms-conditions,privacy-policy",
     *     summary="Get Page descriptions on about-us,terms-conditions,privacy-policy",
     *     @OA\Parameter(
     *          name="user_id",
     *          description="user Id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              default=1,
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="page_url",
     *          description="page url",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              default="about-us",
     *          )
     *      ),
     *  @OA\Response(response="200", description="Get all Page descriptions"),
     *  @OA\Response(response=206, description="validation error"),
     *  @OA\Response(response=400, description="Bad request"),
     *  @OA\Response(response=401, description="Unauthorized access"),
     *  @OA\Response(response=404, description="No record found"),
     *  @OA\Response(response=500, description="Internal server error"),
     *  @OA\Response(response=502, description="Bad gateway"),
     *  @OA\Response(response=503, description="Service unavailable"),
     *  @OA\Response(response=504, description="Gateway timeout"),
     *     security={
     *       {"apiAuth": {}}
     *     }
     * )
     * 
     */
    public function getAllpagecontent(Request $request)
    {
        $pagecontent = $this->pagecontentService->getAll($request);
        return $this->successResponse($pagecontent,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
}