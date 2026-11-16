<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Order;
class OrderWorkflowTest extends TestCase {
 use RefreshDatabase;
 public function test_order_pricing_and_status_rules(): void {
  $this->post('/products',['sku'=>'SHOP-1','name'=>'Desk lamp','price_cents'=>2500])->assertRedirect();
  $p=Product::where('sku','SHOP-1')->firstOrFail();
  $this->post('/orders',['customer_name'=>'Taylor','customer_email'=>'taylor@example.com','product_id'=>$p->id,'quantity'=>2])->assertRedirect();
  $order=Order::firstOrFail();
  $this->assertSame(5000,(int)$order->total_cents);
  $this->post('/orders/'.$order->id.'/status',['status'=>'delivered'])->assertSessionHasErrors('status');
  $this->post('/orders/'.$order->id.'/status',['status'=>'processing'])->assertRedirect();
  $this->assertSame('processing',$order->fresh()->status);
 }
}
