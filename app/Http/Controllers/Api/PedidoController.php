    /**
     * Crear un nuevo pedido desde el Frontend
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio' => 'required|numeric'
        ]);

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                $usuario = $request->user();
                
                // 1. Buscamos o creamos el registro de cliente (Asegurando campos obligatorios)
                $cliente = \App\Models\Cliente::where('usuario_id', $usuario->id)->first();
                
                if (!$cliente) {
                    $cliente = \App\Models\Cliente::create([
                        'usuario_id' => $usuario->id,
                        'telefono' => '0000000000', // Valor por defecto para evitar error 500
                        'identificacion_fiscal' => null
                    ]);
                }

                // 2. Creamos el pedido principal
                $pedido = Pedido::create([
                    'cliente_id' => $cliente->id,
                    'fecha_pedido' => now(),
                    'estado_pago' => 'pendiente',
                    'estado_envio' => 'pendiente',
                    'notas' => 'Pedido realizado desde la web'
                ]);

                // 3. Registramos los detalles
                foreach ($request->items as $item) {
                    \App\Models\DetallePedido::create([
                        'pedido_id' => $pedido->id,
                        'variante_id' => $item['id'],
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio']
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'pedido_id' => $pedido->id
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Error técnico: ' . $e->getMessage()
            ], 500);
        }
    }