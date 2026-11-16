<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderItem extends Model {
 protected $fillable=['order_id','product_id','name_snapshot','quantity','price_cents'];
 public function order(){return $this->belongsTo(Order::class);}
}
