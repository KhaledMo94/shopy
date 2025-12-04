<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FawryService;
use Illuminate\Http\Request;

class FawryPaymentController extends Controller
{
    protected FawryService $fawry;

    public function __construct(FawryService $fawry)
    {
        $this->fawry = $fawry;
    }

    public function intiatePayment(Request $request)
    {
        $request->validate([
            'order_id'              =>'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        $merchantRefNum = 'ORD-' . $order->id . '-' . time();
        $order->payment_ref = $merchantRefNum;
        $order->payment_method = 'fawry';
        $order->payment_status = 'pending';
        $order->save();

        $data = [
            'merchantRefNum'   => $merchantRefNum,
            'customerName'     => $order->customer->name ?? 'Guest',
            'customerMobile'   => $order->customer->phone ?? '',
            'customerEmail'    => $order->customer->email ?? '',
            'paymentMethod'    => $request->payment_method,
            'amount'           => (float) $order->order_amount,
            'currencyCode'     => 'EGP',
            'language'         => 'en-gb',
            'chargeItems'      => $this->buildChargeItemsFromOrder($order),
            'orderWebHookUrl'  => route('fawry.webhook'),
        ];

        $response = $this->fawry->createCharge($data);

        $order->save();

        return response()->json([
            'success'  => true,
            'message'  => 'Fawry payment initiated',
            'data'     => $response, // may contain redirect URL / reference
        ]);
    }

    protected function buildChargeItemsFromOrder(Order $order): array
    {
        return $order->details->map(function ($item) {
            return [
                'itemId'      => $item->id,
                'description' => $item->item_name,
                'price'       => (float) $item->price,
                'quantity'    => (float) $item->quantity,
            ];
        })->values()->toArray();
    }

    public function webhook(Request $request)
    {
        $merchantRefNum     = $request->input('merchantRefNumber');
        $fawrySignature     = $request->input('signature');
        $orderStatus        = $request->input('orderStatus'); 
        $paymentAmount      = $request->input('orderAmount');

        if (! $this->fawry->verifyNotificationSignature($merchantRefNum, $fawrySignature)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        $order = Order::where('payment_ref', $merchantRefNum)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        if ($orderStatus === 'PAID') {
            $order->payment_status = 'paid';
            $order->order_status   = 'confirmed'; // adapt to your flow
        } else {
            $order->payment_status = 'failed';
        }

        $order->save();

        return response()->json(['success' => true]);
    }
}
