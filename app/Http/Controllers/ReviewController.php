<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ReviewService;
use App\AppValidator\ReviewValidator;
use Illuminate\Support\Facades\Log;


class ReviewController extends Controller
{

    use ApiResponser;
     /**
     * @var reviewService
     */
    protected $reviewService;
    protected $reviewValidator;

    /**
     * PostController Constructor
     *
     * @param ReviewService $reviewService
     *
     */
    public function __construct(ReviewService $reviewService,ReviewValidator $reviewValidator)
    {
        $this->reviewService = $reviewService;
        $this->reviewValidator = $reviewValidator;      
    }
    /**
     * @OA\Get(
     *     path="/api/allReviews",
     *     tags={"All Reviews"},
     *     description="get all Reviews of a customer",
     *     summary="get all Reviews of a customer",
     *  @OA\Response(response="200", description="Get all Reviews of a customer"),
     *  @OA\Response(response=206, description="validation error"),
     *  @OA\Response(response=400, description="Bad request"),
     *  @OA\Response(response=401, description="Unauthorized access"),
     *  @OA\Response(response=404, description="No record found"),
     *  @OA\Response(response=500, description="Internal server error"),
     *  @OA\Response(response=502, description="Bad gateway"),
     *  @OA\Response(response=503, description="Service unavailable"),
     *  @OA\Response(response=504, description="Gateway timeout"),
     *     security={{ "apiAuth": {} }}
     * )
     * 
     */
    public function getAllReview() {
      $result = $this->reviewService->getAllReview();
      return $this->successResponse($result,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);    
    }
        /**
         * @OA\Post(
         *     path="/api/AddReview",
         *     tags={"Add Review by a customer"},
         *     description="Add Review of a customer",
         *     summary="Add Review of a customer",
         *     @OA\Parameter(
         *          name="pnr",
         *          description="pnr number",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              example="21212121"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="bus_id",
         *          description="bus Id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              example="7"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="users_id",
         *          description="users id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              example="2"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="reference_key",
         *          description="link for email",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              example="abcd@gmail.com"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="rating_overall",
         *          description="rating overall",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="number",
         *              example="4"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="rating_comfort",
         *          description="rating comfort",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="number",
         *               example="4"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="rating_clean",
         *          description="rating_clean",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="number",
         *              example="4"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="rating_behavior",
         *          description="rating_behavior",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="number",
         *               example="5"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="rating_timing",
         *          description="rating_timing",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="number",
         *               example="4"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="comments",
         *          description="comments",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              example="testing"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="title",
         *          description="title",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *               example="testing"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="created_by",
         *          description="created_by",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              example="Sam"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="user_id",
         *          description="User Id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="number",
         *              example="1"
         *          )
         *      ),
         *  @OA\Response(response="200", description="Add reviews by a customer"), 
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
    public function createReview(Request $request) {
      $data = $request->all();
      
      $reviewValidator = $this->reviewValidator->validate($data);

      if ($reviewValidator->fails()) {
      $errors = $reviewValidator->errors();
      return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
      } 
        try {
          $response =  $this->reviewService->createReview($request); 
          return $this->successResponse($response,Config::get('constants.REVIEW_ADDED'),Response::HTTP_CREATED);
        }
        catch (Exception $e) { 
          
            return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
        }	
    } 
  /**
   * @OA\Put(
   *     path="/api/UpdateReview/{id}",
   *     tags={"Update existing Review"},
   *     summary="Update Review",
   *     @OA\Parameter(
   *         description="Update Review of a customer",
   *         in="path",
   *         name="id",
   *         required=true,
   *          @OA\Schema(
   *              type="integer",
   *              example=1
   *          )
   *     ),
   *     @OA\Parameter(
   *          name="pnr",
   *          description="pnr number",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="21212121"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="bus_id",
   *          description="bus Id",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="integer",
   *              example="1"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="users_id",
   *          description="users id",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="integer",
   *              example="2"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="reference_key",
   *          description="reference key",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="abcd@gmail.com"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="rating_overall",
   *          description="rating overall",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="number",
   *              example=4
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="rating_comfort",
   *          description="rating comfort",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="number",
   *              example=4
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="rating_clean",
   *          description="rating_clean",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="number",
   *              example=5
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="rating_behavior",
   *          description="rating_behavior",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="number",
   *              example=4
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="rating_timing",
   *          description="rating_timing",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="number",
   *              example=5
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="comments",
   *          description="comments",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="testing"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="title",
   *          description="title",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="testing"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="created_by",
   *          description="created_by",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="Sam"
   *          )
   *      ),
    *     @OA\Parameter(
   *          name="user_id",
   *          description="User Id",
   *          required=true,
   *          in="query",
   *          @OA\Schema(
   *              type="number",
   *              example=1
   *          )
   *      ),
   *  @OA\Response(response="201", description="Updated reviews by a customer"),
   *  @OA\Response(response="206", description="Review of a customer not exist"),
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
   *     )
   * )
   */  
    public function updateReview(Request $request, $id) {
      $data = $request->all();
      $reviewValidator = $this->reviewValidator->validate($data);
      try {
        $response = $this->reviewService->updateReview($data, $id);
        if($response =='NOT-MATCH'){
          return $this->errorResponse('User does not match to the review',Response::HTTP_PARTIAL_CONTENT);
        }
        elseif($response =='NOT-EXIST'){
          return $this->errorResponse('Review does not exist',Response::HTTP_PARTIAL_CONTENT);
          
        }else{
          return $this->successResponse($response, Config::get('constants.RECORD_UPDATED'), Response::HTTP_CREATED);
        }
       
    } catch (Exception $e) {

        return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
    }
    }
  /**
   * @OA\Delete(
   *     path="/api/DeleteReview/{id}/{userId}",
   *     tags={"Delete Review added by a customer"},
   *     summary="Delete Review",
   *     @OA\Parameter(
   *         description="review Id",
   *         in="path",
   *         name="id",
   *         required=true,
   *          @OA\Schema(
   *              type="integer",
   *              example=1
   *          )
   *     ), 
   *     @OA\Parameter(
   *         description="User Id",
   *         in="path",
   *         name="userId",
   *         required=true,
   *          @OA\Schema(
   *              type="integer",
   *              example=1
   *          )
   *     ),
   *  @OA\Response(response="202", description="Delete Review"),
   *  @OA\Response(response="206", description="User does not match to the review"),
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
   *     )
   * )
   */  
    public function deleteReview($id,$users_id) {

      try{
        $response = $this->reviewService->deleteReview($id,$users_id);

        if($response =='NOT-MATCH'){
          return $this->errorResponse('User does not match to the review',Response::HTTP_PARTIAL_CONTENT);
        }
        elseif($response =='NOT-EXIST'){
          return $this->errorResponse('Review does not exist',Response::HTTP_PARTIAL_CONTENT);
          
        }else{
        return $this->successResponse($response, Config::get('constants.RECORD_REMOVED'), Response::HTTP_ACCEPTED);
        }
      }
      catch (Exception $e){
          return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
      }   
    }
  
    public function getReview($id) {

      try{
        $result= $this->reviewService->getReview($id);
      }
      catch (Exception $e){
          return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
      }
      return $this->successResponse($result, Config::get('constants.RECORD_FETCHED'), Response::HTTP_ACCEPTED);
    }
   
    public function getReviewByBid($bid) {

      try{
        $result= $this->reviewService->getReviewByBid($bid);
      }
      catch (Exception $e){
        
          return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
      }
      return $this->successResponse($result, Config::get('constants.RECORD_FETCHED'), Response::HTTP_ACCEPTED);   
    }    
}
