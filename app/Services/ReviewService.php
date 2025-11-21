<?php

namespace App\Services;

use App\Models\Review;
use App\Repositories\ReviewRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Illuminate\Support\Facades\Config;

class ReviewService
{
    /**
     * @var $reviewRepository
     */
    protected $reviewRepository;

    /**
     * ReviewService constructor.
     *
     * @param ReviewRepository $reviewRepository
     */
    public function __construct(ReviewRepository $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    /**
     * Delete  by id.
     *
     * @param $id
     * @return String
     */
    public function deleteReview($id,$users_id)
    {

        try {
            $review = $this->reviewRepository->deleteReview($id,$users_id);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.RECORD_NOT_FOUND'));
        }
        return $review;
    }

    /**
     * Get all Data.
     *
     * @return String
     */
    public function getAllReview()
    {
        return $this->reviewRepository->getAllReview();
    }

    /**
     * Get  by id.
     *
     * @param $id
     * @return String
     */
    public function getReview($id)
    {
        return $this->reviewRepository->getReview($id);
    }

    public function getReviewByBid($bid)
    {
        return $this->reviewRepository->getReviewByBid($bid);
    }
    /**
     * Update  data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function updateReview($data, $id)
    {


        try {
            $review = $this->reviewRepository->updateReview($data, $id);
        } catch (Exception $e) {           
            throw new InvalidArgumentException(Config::get('constants.RECORD_NOT_FOUND'));
        }
        return $review;
        

    }

    /**
     * Validate  data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function createReview($data)
    {
      
        try { 
            $review = $this->reviewRepository->createReview($data);

            return $review;

        } catch (Exception $e) { 
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }
       
        
    }

}