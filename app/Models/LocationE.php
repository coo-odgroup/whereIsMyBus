<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use IlluminateDatabaseEloquentModel;
use Kyslik\ColumnSortable\Sortable;





class LocationE extends Model
{
    use Sortable;
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'location';
    protected $fillable = ['name','synonym','created_by','status'];

    public $sortable = ['name',
                        'synonym',
                        'created_by',
                        'status'];

    /*public function category()
    {
        return $this->belongsTo('AppCategory');
    }*/
}