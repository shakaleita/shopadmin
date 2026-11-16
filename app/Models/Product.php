<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model {
 protected $fillable=['name','sku','price_cents','active'];
 protected $casts=['active'=>'boolean'];
}
