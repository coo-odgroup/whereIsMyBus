<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    use HasFactory;
    protected $table = 'appversion';
    protected $fillable = ['info','name','mandatory','version','new_version_names','new_version_codes',
    'allowed_days','has_issues','created_by'];
}
