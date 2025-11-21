<?php

namespace App\Repositories;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;


class ReviewRepository
{
    /**
     * @var Review
     */
    protected $review;
    protected $user;

    /**
     * ReviewRepository constructor.
     *
     * @param Review $review
     */
    public function __construct(Review $review, User $user)
    {
        $this->review = $review;
        $this->user = $user;
    }

    /**
     * Get all review.
     *
     * @return Review $review
     */
    public function getAllReview()
    {
        return $this->review->where('status', 1)
                    ->with(['bus' =>  function ($u){
                        $u->select('id','name');
                    }])
                    ->with(['users' =>  function ($u){
                        $u->select('id','name','district','profile_image');
                    }])
                    ->get();
    }

    /**
     * Get review by id
     *
     * @param $id
     * @return mixed
     */
    public function getReview($id)
    {
        return $this->review
            ->where('id', $id)
            ->get();
    }
    public function getReviewByBid($bid)
    {

        $result= $this->review::addSelect(['cname' => $this->user::select('first_name')
        ->whereColumn('Review.users_id', 'id')])
        ->whereNotIn('status', [2])
        ->where('bus_id', $bid)
        ->orderBy('id', 'desc')
        ->get();

        return $result;

        // return $this->review->avg('rating_overall');
    }
    /**
     * Save review
     *
     * @param $data
     * @return Review
     */
    public function createReview($data)
    {
      
     
      
        $post = new $this->review;

        $post->pnr = $data['pnr'];
        $post->bus_id  = $data['bus_id'];
        $post->users_id = $data['users_id'];
        $post->reference_key = $data['reference_key'];
        $post->rating_overall = $data['rating_overall'];
        $post->rating_comfort = $data['rating_comfort'];
        $post->rating_clean = $data['rating_clean'];
        $post->rating_behavior = $data['rating_behavior'];
        $post->rating_timing = $data['rating_timing'];
        $post->comments = $data['comments'];
        $post->title = $data['title'];
        $post->user_id = $data['user_id'];
        $post->status = 0;
        $post->created_by = $data['created_by'];
      
        $post->save();

        return $post;
    }

    /**
     * Update review
     *
     * @param $data
     * @return Review
     */
    public function updateReview($data, $id)
    {

       
        
        $rev_data = $this->review->where('id',$id)->get();

        if($rev_data && isset($rev_data[0])){

            if($rev_data[0]->users_id==$data['users_id']){


                $post = $this->review->find($id);

                $post->pnr = $data['pnr'];
                $post->bus_id  = $data['bus_id'];
                $post->users_id = $data['users_id'];
                $post->reference_key = $data['reference_key'];
                $post->rating_overall = $data['rating_overall'];
                $post->rating_comfort = $data['rating_comfort'];
                $post->rating_clean = $data['rating_clean'];
                $post->rating_behavior = $data['rating_behavior'];
                $post->rating_timing = $data['rating_timing'];
                $post->title = $data['title'];
                $post->comments = $data['comments'];
                $post->update();
        
                return $post;


            }else{
                return 'NOT-MATCH';
            }
        }else{
            return 'NOT-EXIST';
        }

       
    }

    /**
     * Update review
     *
     * @param $data
     * @return Review
     */
    public function deleteReview($id,$users_id)
    {


        $rev_data = $this->review->where('id',$id)->get();

        if($rev_data && isset($rev_data[0])){

            if($rev_data[0]->users_id==$users_id){
        
            $post = $this->review->find($id);
            $post->status = 2;
            $post->update();

            return $post;

            }else{
                return 'NOT-MATCH';
            }
        }else{
            return 'NOT-EXIST';
        }


    }

}