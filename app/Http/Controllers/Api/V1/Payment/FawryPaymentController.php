<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Services\FawryService;
use App\Traits\Processor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\Validator;

class FawryPaymentController extends Controller
{
    use Processor;
    private PaymentRequest $payment;
    protected FawryService $fawry;

    public function __construct(PaymentRequest $payment , FawryService $fawry)
    {
        $this->payment = $payment;
        $this->fawry = $fawry;
    }


    public function payment(Request $request)
    {
        $validator = FacadesValidator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(
                $this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)),
                400
            );
        }

        $data = $this->payment::where('id', $request->payment_id)
                              ->where('is_paid', 0)
                              ->first();

        if (! $data) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $amount = number_format($data->payment_amount, 2, '.', '');
        $merchantRefNum = $data->id;

        $payer = json_decode($data->payer_information);

        $customerName  = $payer->name  ?? "Customer";
        $customerEmail = $payer->email ?? "noemail@example.com";
        $customerPhone = $payer->phone ?? "01000000000";

        $chargeItems = [
            [
                "itemId"      => "PAYMENT-" . $data->id,
                "description" => "Order Payment",
                "price"       => floatval($amount),
                "quantity"    => 1
            ]
        ];

        $payload = [
            "merchantRefNum"   => $merchantRefNum,
            "customerName"     => $customerName,
            "customerMobile"   => $customerPhone,
            "customerEmail"    => $customerEmail,
            "amount"           => floatval($amount),
            "chargeItems"      => $chargeItems,
            "paymentMethod"    => "PAYATFAWRY",
            "orderWebHookUrl"  => route('fawry.webhook'),
        ];

        $response = $this->fawry->createCharge($payload);

        return $response;

    }
}
