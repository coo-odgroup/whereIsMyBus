<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Article;


class MyComment extends Model
{
    use HasFactory;
    protected $table = 'my_comments';
    protected $fillable = ['text','star'];

    public function article(){
        return $this->belongsTo(Article::class);
      // return $this->belongsTo('Article');
    }
    
}
