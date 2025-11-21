<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\MyComment;

class Article extends Model
{
    use HasFactory;
    protected $table = 'articles';
    protected $fillable = ['title','slug','body'];

    public function MyComment(){
       // return $this->hasMany(Comment::class);
       return $this->hasMany(MyComment::class);
    }
    
}
