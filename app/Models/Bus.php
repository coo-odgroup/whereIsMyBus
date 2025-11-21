<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusSeats;
use App\Models\BusAmenities;
use App\Models\CityClosing;
use App\Models\BusContacts;
use App\Models\TicketPrice;
use App\Models\Review;
use App\Models\BusSchedule;
use App\Models\BusOperator;
use App\Models\BusCancelled;
use App\Models\BusGallery;
use App\Models\CancellationSlabInfo;
use App\Models\CancellationSlab;
use App\Models\BusLocationSequence;

class Bus extends Model
{
    use HasFactory; 
    protected $table = 'bus';
    protected $fillable = [ 
        'bus_operator_id','user_id', 'name','via','bus_number','bus_description','bus_type_id','bus_sitting_id','amenities_id','cancellationslabs_id','bus_seat_layout_id','running_cycle','popularity','admin_notes','has_return_bus', 'return_bus_id','cancelation_points','created_by',
    ];
    public function busAmenities()
    {
        return $this->hasMany(BusAmenities::class);        
    } 
    public function busSafety()
    {
        return $this->hasMany(BusSafety::class);        
    }
    public function review()
    {        
        return $this->hasMany(Review::class);        
    } 
    public function busSchedule()
    {        
        return $this->hasOne(busSchedule::class);        
    } 
    public function busCancelled()
    {        
        return $this->hasOne(BusCancelled::class);        
    } 
    public function busContacts()
    {        
        return $this->hasOne(BusContacts::class);        
    } 
    public function ticketPrice()
    {        
        return $this->hasMany(TicketPrice::class);        
    } 
    public function busStoppageTiming()
    {        
        return $this->hasMany(BusStoppageTiming::class);        
    }      
    public function busOperator()
    {
    	return $this->belongsTo(BusOperator::class);
    }
    public function specialFare()
    {
    	return $this->belongsToMany(SpecialFare::class);       
    }
    public function festiveFare()
    {
    	return $this->belongsToMany(FestivalFare::class);       
    }
    public function BusSitting()
    {
    	return $this->belongsTo(BusSitting::class);
    }
    public function BusType()
    {
    	return $this->belongsTo(BusType::class);
    }
    public function busSeatLayout()
    {
    	return $this->belongsTo(BusSeatLayout::class);
    }
    public function ownerfare()
    {
    	return $this->belongsToMany(OwnerFare::class);
    } 
    public function booking()
    {        
        return $this->hasOne(Booking::class);        
    }
    public function busSeats()
    {        
        return $this->hasMany(BusSeats::class)->where('status','!=',2);  ////need to remove      
    }
    public function busGallery()
    {        
        return $this->hasMany(BusGallery::class);        
    }
    public function cancellationslabs()
    {        
        return $this->belongsTo(CancellationSlab::class);        
    }
    public function seatOpen()
    {        
        return $this->hasOne(SeatOpen::class);        
    } 
    public function busLocationSequence()
    {        
        return $this->hasMany(BusLocationSequence::class);        
    }
    public function couponAssignedBus()
    {        
        return $this->hasMany(CouponAssignedBus::class);        
    }
    public function bookingseized()
    {
        return $this->hasMany(BookingSeized::class);        
    }   
}
