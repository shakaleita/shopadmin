<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
 protected $fillable=['customer_name','customer_email','status','total_cents'];
 public function items(){return $this->hasMany(OrderItem::class);}
}
