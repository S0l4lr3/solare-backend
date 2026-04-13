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
            'id_pedido' => 'required' // Obligatorio para trazar la venta
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
                        "reference_id" => (string)$request->id_pedido, // Guardamos el ID del pedido en la referencia de PayPal
                        "amount" => [
                            "currency_code" => "USD",
                            "value" => number_format($request->monto, 2, '.', '')
                        ],
                        "description" => "Pedido #" . $request->id_pedido
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

            return response()->json(['error' => 'No se pudo crear la orden'], 500);

        } catch (\Exception $e) {
            Log::error('PAYPAL_CREATE_EXCEPTION:', ['msg' => $e->getMessage()]);
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

            // Capturamos el pago en PayPal
            $response = $provider->capturePaymentOrder($request->token);
            
            Log::info('PAYPAL_CAPTURE_FULL_RESPONSE:', $response);

            if (isset($response['status']) && $response['status'] == 'COMPLETED') {
                
                // Obtenemos el ID del pedido (prioridad a la sesión/request, sino de la referencia de PayPal)
                $id_pedido = $request->id_pedido ?? $response['purchase_units'][0]['reference_id'] ?? null;
                
                if ($id_pedido) {
                    // Actualizamos la tabla 'pedidos' (Plural siempre)
                    DB::table('pedidos')
                        ->where('id', $id_pedido)
                        ->update([
                            'estado_pago' => 'completado',
                            'estado_envio' => 'procesando',
                            'actualizado_en' => now()
                        ]);

                    $pedidoFull = Pedido::with(['detalles.variante.producto', 'detalles.variante.material'])
                        ->find($id_pedido);

                    if ($pedidoFull) {
                        $resumen = [
                            'id' => $pedidoFull->id,
                            'total' => $pedidoFull->detalles->sum(function($d) { return $d->cantidad * $d->precio_unitario; }),
                            'items' => $pedidoFull->detalles->map(function($d) {
                                return [
                                    'producto' => $d->variante->producto->nombre ?? 'Producto',
                                    'color' => $d->variante->material->nombre ?? 'N/A',
                                    'cantidad' => $d->cantidad,
                                    'precio' => $d->precio_unitario
                                ];
                            })
                        ];

                        return response()->json([
                            'status' => 'success',
                            'data' => $resumen
                        ]);
                    }
                }
                
                // Si llegamos aquí, el pago se hizo pero no encontramos el pedido para el resumen
                return response()->json(['status' => 'success', 'mensaje' => 'Pago recibido pero el resumen no está disponible.']);
            }

            return response()->json(['status' => 'error', 'mensaje' => 'Pago no completado en PayPal'], 400);

        } catch (\Exception $e) {
            Log::error('PAYPAL_CAPTURE_EXCEPTION:', ['msg' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}