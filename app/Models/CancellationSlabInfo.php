<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CancellationSlab;

class CancellationSlabInfo extends Model
{
    use HasFactory;
    protected $table = 'cancellationslabs_info';
    protected $fillable = [ 
        'cancellation_slab_id ','duration','deduction','status','created_by'
    ];

    public function cancelationSlab()
    {        
        return $this->belongsTo(CancellationSlab::class);        
    }
}
