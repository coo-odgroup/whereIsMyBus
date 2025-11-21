<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CustomerQueryCategory;


class CustomerQueryCategoryIssues extends Model
{
    use HasFactory;
    protected $table = 'customer_query_category_issues';
    protected $fillable = ['name'];
    public function customerQueryCategory()
    {
    	return $this->belongsTo(CustomerQueryCategory::class);
    }
}
