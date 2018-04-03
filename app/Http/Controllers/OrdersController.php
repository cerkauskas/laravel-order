<?php
	
	namespace App\Http\Controllers;
	
	use App\Chat;
	use App\Http\Requests\ChangeOrderStatusRequest;
	use App\Mail\OrderConfirmed;
	use App\Mail\OrderRejected;
	use App\Order;
	use App\OrderProduct;
	use App\User;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Mail;
	use App\Services\InvoiceService;
	use Illuminate\Support\Facades\Storage;
	
	
	class OrdersController extends Controller {
		/**
		 * Display a listing of the resource.
		 *
		 * @return \Illuminate\Http\Response
		 */
		private $checkInvoice;
		
		public function __construct( InvoiceService $invoiceService ) {
			$this -> checkInvoice = $invoiceService;
		}
		
		public function index( Request $request ) {
			$user           = Auth ::user();
			$orders         = new Order;
			$selectedUser   = - 1;
			$selectedType   = - 1;
			$selectedStatus = - 1;
			
			
			if ( $user -> role == 'admin' ) {
				$user = Auth ::user();
				if ( $user -> role == 'admin' ) {
					$user = User::whereNotIn('role', ['admin'])->get();
					if ( $request -> has( 'user_id' ) && $request -> get( 'user_id' ) > 0 ) {
						$orders       = $orders -> where( 'user_id', $request -> get( 'user_id' ) );
						$selectedUser = $request -> get( 'user_id' );
					}
				}
			} else {
				$orders = $orders -> where( 'user_id', $user -> id );
			}
			if ( $request -> has( 'type' ) && $request -> get( 'type' ) >= 0 ) {
				$orders       = $orders -> where( 'type', $request -> get( 'type' ) );
				$selectedType = $request -> get( 'type' );
			}
			if ( $request -> has( 'status' ) && $request -> get( 'status' ) >= 0 ) {
				$orders         = $orders -> where( 'status', $request -> get( 'status' ) );
				$selectedStatus = $request -> get( 'status' );
			}
			$orders = $orders -> paginate(  );
			
			return view( 'orders.orders', [
				'orders'         => $orders,
				'users'          => $user,
				'selectedUser'   => $selectedUser,
				'selectedType'   => $selectedType,
				'selectedStatus' => $selectedStatus
			] );
		}

    public function show($id)
    {
        $order = Order::findOrFail($id);
        $chat = Chat::where('order_id', $id)->first();
        $products = $order->orderProducts;

        return view('orders.single_order', ['products' => $products, 'order' => $order, 'chat' => $chat]);
    }

    public function action(ChangeOrderStatusRequest $request, $id)
    {
        $userEmail = Order::findOrFail($id)->user->client->email;

        $order = Order::findOrFail($id);
        if ($request->action === 'Confirm') {

            $status = Order::CONFIRMED;
            Mail::to($userEmail)->send(new OrderConfirmed($order));

        } elseif ($request->action === 'Reject') {
            $status = Order::REJECTED;
            Mail::to($userEmail)->send(new OrderRejected($order));
        }

        $file = $request->file('invoice');
        if (isset($file)) {
            $filenameWithExt = $this->checkInvoice->uploadInvoice($file);
            if (empty($order->invoice)) {
                $order->invoice()->create($request->except('_token') + [
                        'filename' => $filenameWithExt,
                    ]);
            } else {
                Storage::delete('public/invoices/' . $order->invoice->filename);
                $order->invoice->update($request->except('_token') + [
                        'filename' => $filenameWithExt,
                    ]);
            }
        }
        $order->update(['status' => $status]);

        return redirect()->route('order.orders');
    }

    public function download($id)
    {
        $order = Order::findOrFail($id);
        if (!empty($order->invoice->filename)) {
            $path = storage_path('app/public/invoices/' . $order->invoice->filename);

            return response()->download($path);
        }
        return redirect()->back();
    }
}

