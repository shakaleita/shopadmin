<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
class OrderAuditTest extends TestCase {
 use RefreshDatabase;
 public function test_valid_transitions_are_audited_without_duplicates():void {
  $order=Order::create([
   'customer_name'=>'Example','customer_email'=>'example@example.com',
   'status'=>'pending','total_cents'=>1500,
  ]);
  $this->post('/orders/'.$order->id.'/status',['status'=>'processing'])->assertRedirect();
  $this->post('/orders/'.$order->id.'/status',['status'=>'processing'])->assertRedirect();
  $this->assertDatabaseCount('order_status_events',1);
  $this->get('/api/orders/'.$order->id.'/history')->assertOk()
   ->assertJsonCount(1)->assertJsonPath('0.from_status','pending')
   ->assertJsonPath('0.to_status','processing');
  $this->post('/orders/'.$order->id.'/status',['status'=>'delivered'])->assertSessionHasErrors('status');
  $this->assertDatabaseCount('order_status_events',1);
 }
}
