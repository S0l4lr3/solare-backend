<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Pedido;

class ApiPayPalController extends Controller
{
    public function createPayment(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric',
            'id_pedido' => 'required'
        ]);

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $token = $provider->getAccessToken();

            // Error si el token no se genera (Credenciales incorrectas)
            if (isset($token['error'])) {
                return response()->json(['error' => 'Error de autenticación con PayPal: ' . ($token['error_description'] ?? 'Credenciales inválidas')], 500);
            }

            // Moneda configurada en config/paypal.php
            $currency = config('paypal.currency', 'MXN');

            $response = $provider->createOrder([
                "intent" => "CAPTURE",
                "application_context" => [
                    "return_url" => $request->return_url, // URL enviada por el cliente
                    "cancel_url" => $request->cancel_url, // URL enviada por el cliente
                ],
                "purchase_units" => [
                    [
                        "reference_id" => (string)$request->id_pedido,
                        "amount" => [
                            "currency_code" => $currency,
                            "value" => number_format($request->monto, 2, '.', '')
                        ],
                        "description" => "Compra en Solare Muebles - Pedido #" . $request->id_pedido
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

            Log::error('PAYPAL_CREATE_ORDER_FAILED:', ['response' => $response]);
            return response()->json(['error' => 'PayPal no pudo generar el enlace de pago.'], 500);

        } catch (\Exception $e) {
            Log::error('PAYPAL_CREATE_EXCEPTION:', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'Fallo interno al conectar con PayPal.'], 500);
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
                $id_pedido = $request->id_pedido ?? $response['purchase_units'][0]['reference_id'] ?? null;
                
                if ($id_pedido) {
                    DB::table('pedidos')
                        ->where('id', $id_pedido)
                        ->update([
                            'estado_pago' => 'pagado',
                            'estado_envio' => 'procesando',
                            'actualizado_en' => now()
                        ]);

                    $pedidoFull = Pedido::with(['detalles.variante.producto', 'detalles.variante.material'])
                        ->find($id_pedido);

                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'pedido' => $pedidoFull,
                            'paypal_transaction_id' => $response['id'] ?? null
                        ]
                    ]);
                }
            }

            Log::error('PAYPAL_CAPTURE_FAILED:', ['response' => $response]);
            return response()->json(['status' => 'error', 'mensaje' => 'No se pudo capturar el pago.'], 400);

        } catch (\Exception $e) {
            Log::error('PAYPAL_CAPTURE_EXCEPTION:', ['msg' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
