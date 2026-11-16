<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('products',function(Blueprint $table){$table->id();$table->string('sku')->unique();$table->string('name');$table->unsignedInteger('price_cents');$table->boolean('active')->default(true);$table->timestamps();});
  Schema::create('orders',function(Blueprint $table){$table->id();$table->string('customer_name');$table->string('customer_email');$table->string('status')->default('pending');$table->unsignedInteger('total_cents');$table->timestamps();});
  Schema::create('order_items',function(Blueprint $table){$table->id();$table->foreignId('order_id')->constrained()->cascadeOnDelete();$table->foreignId('product_id')->constrained();$table->string('name_snapshot');$table->unsignedInteger('quantity');$table->unsignedInteger('price_cents');$table->timestamps();});
 }
 public function down():void {Schema::dropIfExists('order_items');Schema::dropIfExists('orders');Schema::dropIfExists('products');}
};
