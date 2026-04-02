<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiPayPalController extends Controller
{
    public function createPayment(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric',
            'id_pedido' => 'nullable'
        ]);

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $response = $provider->createOrder([
                "intent" => "CAPTURE",
                "application_context" => [
                    "return_url" => "http://localhost:8001/payment/paypal/success",
                    "cancel_url" => "http://localhost:8001/payment/paypal/cancel",
                ],
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => "USD",
                            "value" => number_format($request->monto, 2, '.', '')
                        ],
                        "description" => "Pago de pedido en Solare"
                    ]
                ]
            ]);

            if (isset($response['id']) && $response['id'] != null) {
                foreach ($response['links'] as $links) {
                    if ($links['rel'] == 'approve') {
                        return response()->json([
                            'id' => $response['id'],
                            'status' => $response['status'],
                            'paypal_link' => $links['href']
                        ]);
                    }
                }
            }

            Log::error('PAYPAL ERROR CREATE:', $response);
            return response()->json(['error' => 'No se pudo crear la orden de PayPal'], 500);

        } catch (\Exception $e) {
            Log::error('PAYPAL EXCEPTION CREATE:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function capturePayment(Request $request)
    {
        $request->validate(['token' => 'required']);

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $response = $provider->capturePaymentOrder($request->token);

            if (isset($response['status']) && $response['status'] == 'COMPLETED') {
                if ($request->id_pedido) {
                    // INTENTO 1: Usar tabla 'pedidos' (Plural - Estándar Laravel y Railway)
                    try {
                        DB::table('pedidos')
                            ->where('id', $request->id_pedido)
                            ->update([
                                'estado_pago' => 'Pagado',
                                'estado_envio' => 'Confirmado',
                                'actualizado_en' => now()
                            ]);
                    } catch (\Exception $e) {
                        // INTENTO 2: Usar tabla 'pedido' (Singular - Local)
                        DB::table('pedido')
                            ->where('id', $request->id_pedido)
                            ->update([
                                'estado_pago' => 'Pagado',
                                'estado_envio' => 'Confirmado',
                                'actualizado_en' => now()
                            ]);
                    }
                }
                return response()->json(['status' => 'success']);
            }

            Log::error('PAYPAL ERROR CAPTURE:', $response);
            return response()->json(['status' => 'error'], 400);

        } catch (\Exception $e) {
            Log::error('PAYPAL EXCEPTION CAPTURE:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
