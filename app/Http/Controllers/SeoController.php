<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\AppValidator\SeoValidator;
use App\Services\SeoService;


class SeoController extends Controller
{
    use ApiResponser;
    protected $seoValidator;
    protected $seoService;

    public function __construct(SeoValidator $seoValidator, SeoService $seoService)
    {
        $this->seoValidator = $seoValidator;
        $this->seoService = $seoService;
    }
    /**
     * @OA\Get(
     *     path="/api/seolist",
     *     tags={"Get all SEO lists"},
     *     description="Get all SEO lists",
     *     summary="Get all SEO lists",
     *     @OA\Parameter(
     *          name="user_id",
     *          description="User Id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              default=1,
     *          )
     *      ),
     *  @OA\Response(response="200", description="Get all SEO lists"),
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
    public function seolist(Request $request){

        $data = $request->all();
        $seoValidation = $this->seoValidator->validate($data);

        if($seoValidation->fails()){
            $error = $seoValidation->errors();
            return $this->errorResponse($error->toJson(),Response::HTTP_PARTIAL_CONTENT);
        }else{
            try {
                $response = $this->seoService->getAll($request); 
                 return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
            }
            catch (Exception $e) {
                return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
            }
        }
    }
}
