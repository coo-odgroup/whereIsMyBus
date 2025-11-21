<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CustomerQueryCategoryIssues;


class CustomerQueryCategory extends Model
{
    use HasFactory;
    protected $table = 'customer_query_category';
    protected $fillable = [
        'name',
    ];
    public function customerQueryCategoryIssues()
    {
        return $this->hasMany(CustomerQueryCategoryIssues::class);
    } 
}
