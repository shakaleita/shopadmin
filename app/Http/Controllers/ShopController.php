<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ShopController {
 public function index(){return view('shop.index',['products'=>Product::orderBy('name')->get(),'orders'=>Order::with('items')->latest()->get()]);}
 public function product(Request $request){
  $data=$request->validate(['sku'=>'required|string|max:100|unique:products,sku','name'=>'required|string|max:255','price_cents'=>'required|integer|min:0']);
  Product::create($data);return back()->with('success','Product added');
 }
 public function order(Request $request){
  $data=$request->validate(['customer_name'=>'required|string|max:255','customer_email'=>'required|email|max:255','product_id'=>'required|exists:products,id','quantity'=>'required|integer|min:1|max:10000']);
  DB::transaction(function()use($data){
   $product=Product::findOrFail($data['product_id']);
   if(!$product->active)throw ValidationException::withMessages(['product_id'=>'Product unavailable']);
   $order=Order::create(['customer_name'=>$data['customer_name'],'customer_email'=>$data['customer_email'],'status'=>'pending','total_cents'=>$product->price_cents*$data['quantity']]);
   $order->items()->create(['product_id'=>$product->id,'name_snapshot'=>$product->name,'quantity'=>$data['quantity'],'price_cents'=>$product->price_cents]);
  });return back()->with('success','Order created');
 }
 public function status(Request $request,Order $order){
  $data=$request->validate(['status'=>'required|in:pending,processing,shipped,delivered,cancelled']);
  $allowed=['pending'=>['processing','cancelled'],'processing'=>['shipped','cancelled'],'shipped'=>['delivered'],'delivered'=>[],'cancelled'=>[]];
  if($data['status']!==$order->status && !in_array($data['status'],$allowed[$order->status]??[],true))throw ValidationException::withMessages(['status'=>'Transition is not allowed']);
  $order->update(['status'=>$data['status']]);return back()->with('success','Order updated');
 }
 public function insights(){return response()->json(['products'=>Product::count(),'active_products'=>Product::where('active',true)->count(),'orders_by_status'=>Order::selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total','status'),'total_order_value_cents'=>Order::sum('total_cents')]);}

 /**
  * Stream a deterministic read-only CSV snapshot of the local demo dataset.
  * Spreadsheet formula prefixes are escaped to prevent formula interpretation.
  */
 public function export(){
  return response()->streamDownload(function(){
   $handle=fopen('php://output','w');
   fputcsv($handle, ['id','status','total_cents','created_at'], ',', '"', '');
   foreach(Order::orderBy('id')->cursor() as $record){
    $row=[];
    foreach(['id','status','total_cents','created_at'] as $column){
     $value=(string)($record->{$column}??'');
     if(preg_match('/^\s*[=+\-@\t\r]/u',$value))$value="'".$value;
     $row[]=$value;
    }
    fputcsv($handle,$row,',','"','');
   }
   fclose($handle);
  }, 'shop-orders.csv', ['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'no-store']);
 }
}
