<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\CancellationSlabInfo;
class CancellationSlab extends Model
{
    use HasFactory;
    protected $table = 'cancellationslabs';
    protected $fillable = [ 
        'api_id','rule_name','status'
    ];

    public function cancellationSlabInfo()
    {
        return $this->hasMany(CancellationSlabInfo::class);        
    }
    public function bus()
    {
    	return $this->hasMany(Bus::class);
    }
}
