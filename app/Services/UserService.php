<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;



class UserService
{
    /**
     * @var $postRepository
     */
    protected $userRepository;

    /**
     * PostService constructor.
     *
     * @param PostRepository $postRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Delete post by id.
     *
     * @param $id
     * @return String
     */
    public function deleteById($id)
    {
        DB::beginTransaction();

        try {
            $post = $this->userRepository->delete($id);

        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());

            throw new InvalidArgumentException('Unable to delete post data');
        }

        DB::commit();

        return $post;

    }

    public function getallAgent()
    {
       return $this->userRepository->getallAgent();  
    }

    /**
     * Get all post.
     *
     * @return String
     */
    public function getAll()
    {
        return $this->userRepository->getAll();
    }

    /**
     * Get post by id.
     *
     * @param $id
     * @return String
     */
    public function getById($id)
    {
        return $this->userRepository->getById($id);
    }

    /**
     * Update post data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function updateUser($data, $id)
    {
       
        DB::beginTransaction();

        try {
            $user = $this->userRepository->update($data, $id);

        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());

            throw new InvalidArgumentException('Unable to update post data');
        }

        DB::commit();

        return $user;

    }

    /**
     * Validate post data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function savePostData($data)
    {   
        $result = $this->userRepository->save($data);
        return $result;
    }
   
    public function getCustomerInformation($id)
    {
        return $this->userRepository->get($id);
    }

    public function userDetail()
    {
        return $this->userRepository->userDetail();
    }

    /////////////////////Agent registration//////////////////////////////////

    public function Register($data)
    {   
        $agent = $this->userRepository->Register($data);
        return $agent;
    }
    public function verifyOtp($data)
    {   
        $agent = $this->userRepository->verifyOtp($data);
        return $agent;
    }
    public function login($data)
    {
        return $this->userRepository->login($data);
    }
    public function getRoles()
    {
        return $this->userRepository->getRoles();
    }
    public function agentRegister($data)
    {   
        $agent = $this->userRepository->agentRegister($data);
        return $agent;
    }
    
}