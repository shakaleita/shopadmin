<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Order;
class FulfilmentReportTest extends TestCase {
 use RefreshDatabase;
 public function test_aggregates_cancelled_separately_from_live_orders():void {
  Product::create(['sku'=>'RPT-001','name'=>'Reports item','price_cents'=>500]);
  $product=Product::firstOrFail();
  foreach(['pending','cancelled','delivered'] as $status) {
   Order::create(['customer_name'=>'Synthetic QA','customer_email'=>'qa@example.test',
     'status'=>$status,'total_cents'=>1500]);
  }
  $this->get('/api/fulfilment/report')->assertOk()
   ->assertJsonPath('active_order_value_cents',1500)
   ->assertJsonPath('delivered_order_value_cents',1500)
   ->assertJsonPath('cancelled_order_value_cents',1500);
  $this->post('/products',['sku'=>'BAD','name'=>'Too expensive','price_cents'=>100000001])
    ->assertSessionHasErrors('price_cents');
 }
}
