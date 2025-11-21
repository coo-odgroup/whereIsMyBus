<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TestimonialService;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
class TestimonialController extends Controller
{
    use ApiResponser;
    protected $testimonialService;
    public function __construct(TestimonialService $testimonialService)
    {
        $this->testimonialService = $testimonialService;
    }
    /**
     * @OA\Post(
     *     path="/api/GetTestimonial",
     *     tags={"Get all Testimonials"},
     *     description="Get all Testimonials",
     *     summary="Get all Testimonials",
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
     *  @OA\Response(response="200", description="Get all Testimonials"),
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
    public function getAlltestimonial(Request $request)
    {
        $testimonial = $this->testimonialService->getAll($request);
        return $this->successResponse($testimonial,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
    /**
     * @OA\Post(
     *     path="/api/GetFAQ",
     *     tags={"Get FAQ"},
     *     description="Get FAQ",
     *     summary="Get FAQ",
     *  @OA\Response(response="200", description="Get FAQ"),
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
    public function getFAQ()
    {
        $faq = $this->testimonialService->getFAQ();
        return $this->successResponse($faq,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
}