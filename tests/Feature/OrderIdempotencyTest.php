<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Order;

class OrderIdempotencyTest extends TestCase {
 use RefreshDatabase;
 public function test_duplicate_order_retry_creates_exactly_one_order():void {
  Product::create(['sku'=>'RETRY-ITEM','name'=>'Synthetic equipment','price_cents'=>1599]);
  $product=Product::firstOrFail();
  $body=['customer_name'=>'Synthetic Buyer','customer_email'=>'buyer@example.test',
         'product_id'=>$product->id,'quantity'=>2,'idempotency_key'=>'retry-2026-unique-key'];
  $this->post('/orders',$body)->assertRedirect();
  $this->post('/orders',$body)->assertRedirect();
  $this->assertSame(1,Order::count());
  $this->assertSame(1,Order::first()->items()->count());
  $this->assertSame(3198,(int)Order::first()->total_cents);
  $this->post('/orders',[...$body,'quantity'=>3])->assertSessionHasErrors('idempotency_key');
  $this->assertSame(1,Order::count());
 }
 public function test_distinct_keys_and_legacy_forms_create_separate_orders():void {
  Product::create(['sku'=>'MULTI-ITEM','name'=>'Synthetic kit','price_cents'=>200]);
  $product=Product::firstOrFail();
  $body=['customer_name'=>'Synthetic Buyer','customer_email'=>'buyer@example.test',
         'product_id'=>$product->id,'quantity'=>1];
  $this->post('/orders',[...$body,'idempotency_key'=>'key-000001'])->assertRedirect();
  $this->post('/orders',[...$body,'idempotency_key'=>'key-000002'])->assertRedirect();
  $this->post('/orders',$body)->assertRedirect();
  $this->assertSame(3,Order::count());
 }
}
