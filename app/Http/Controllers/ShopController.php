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
  $data=$request->validate(['sku'=>'required|string|max:100|unique:products,sku','name'=>'required|string|max:255','price_cents'=>'required|integer|min:0|max:100000000']);
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
  DB::transaction(function()use($order,$data){
   $locked=Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
   $allowed=['pending'=>['processing','cancelled'],'processing'=>['shipped','cancelled'],'shipped'=>['delivered'],'delivered'=>[],'cancelled'=>[]];
   if($data['status']===$locked->status)return;
   if(!in_array($data['status'],$allowed[$locked->status]??[],true))
    throw ValidationException::withMessages(['status'=>'Transition is not allowed']);
   $previous=$locked->status;
   $locked->update(['status'=>$data['status']]);
   DB::table('order_status_events')->insert(['order_id'=>$locked->id,'from_status'=>$previous,'to_status'=>$data['status'],'changed_at'=>now()]);
  });
  return back()->with('success','Order updated');
 }
 public function history(Order $order){
  return response()->json(DB::table('order_status_events')
    ->where('order_id',$order->id)->orderBy('id')->get());
 }
 /** Distinguish fulfilled, active, and cancelled orders in cents. */
 public function fulfilmentReport(){
  $stages=Order::selectRaw('status,COUNT(*) as orders,COALESCE(SUM(total_cents),0) as value_cents')
   ->groupBy('status')->orderBy('status')->get()->map(fn($row)=>[
     'status'=>$row->status,'orders'=>(int)$row->orders,'value_cents'=>(int)$row->value_cents,
   ]);
  return response()->json([
   'stages'=>$stages,
   'active_order_value_cents'=>(int)Order::whereIn('status',['pending','processing','shipped'])->sum('total_cents'),
   'delivered_order_value_cents'=>(int)Order::where('status','delivered')->sum('total_cents'),
   'cancelled_order_value_cents'=>(int)Order::where('status','cancelled')->sum('total_cents'),
  ]);
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
