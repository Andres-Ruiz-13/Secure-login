<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CashSessionController extends Controller
{
    /**
     * Apertura de Turno / Sesión de Caja
     */
    public function open(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isVendedor() && ! $user->isAdmin()) {
            return back()->with('error', 'Acceso denegado (403): Solo el personal de caja puede abrir sesiones de venta.');
        }

        // Verificar si el usuario ya tiene un turno abierto
        if ($user->currentCashSession) {
            return back()->with('error', 'Ya tienes un turno de caja activo en ' . $user->currentCashSession->cashRegister->nombre . '. Debes cerrarlo antes de abrir uno nuevo.');
        }

        $validated = $request->validate([
            'cash_register_id'       => ['required', 'exists:cash_registers,id'],
            'saldo_inicial'          => ['required', 'numeric', 'min:0'],
            'observaciones'          => ['nullable', 'string', 'max:500'],
            'observaciones_apertura' => ['nullable', 'string', 'max:500'],
        ]);

        // Verificar si la caja ya está ocupada por otro usuario
        $cajaOcupada = CashSession::where('cash_register_id', $validated['cash_register_id'])
            ->where('estado', 'abierta')
            ->first();

        if ($cajaOcupada) {
            return back()->with('error', 'La caja seleccionada ya está siendo utilizada por ' . $cajaOcupada->user->name . '.');
        }

        $session = CashSession::create([
            'cash_register_id'       => $validated['cash_register_id'],
            'user_id'                => $user->id,
            'saldo_inicial'          => (float) $validated['saldo_inicial'],
            'estado'                 => 'abierta',
            'opened_at'              => now(),
            'observaciones_apertura' => $validated['observaciones'] ?? $validated['observaciones_apertura'] ?? null,
        ]);

        return redirect()->route('ventas.lista')
            ->with('success', 'Turno iniciado en ' . $session->cashRegister->nombre . ' con base inicial de $' . number_format($session->saldo_inicial, 2));
    }

    /**
     * Arqueo y Cierre de Turno de Caja
     */
    public function close(Request $request, CashSession $session): RedirectResponse
    {
        $user = $request->user();
        if ($session->user_id !== $user->id && ! $user->isAdmin()) {
            return back()->with('error', 'Solo el cajero titular o el Administrador pueden cerrar este turno.');
        }

        if ($session->estado !== 'abierta') {
            return back()->with('error', 'Esta sesión de caja ya se encuentra cerrada.');
        }

        $validated = $request->validate([
            'saldo_final_contado'  => ['nullable', 'numeric', 'min:0'],
            'saldo_final_real'     => ['nullable', 'numeric', 'min:0'],
            'observaciones'        => ['nullable', 'string', 'max:500'],
            'observaciones_cierre' => ['nullable', 'string', 'max:500'],
        ]);

        $saldoContado = isset($validated['saldo_final_contado'])
            ? (float) $validated['saldo_final_contado']
            : (float) ($validated['saldo_final_real'] ?? 0);

        DB::transaction(function () use ($session, $saldoContado, $validated) {
            // 1. Total ventas en efectivo durante el turno
            $ventasEfectivo = (float) $session->ventas()->where('metodo_pago', 'efectivo')->sum('total');

            // 2. Movimientos directos (ingresos y egresos/retiros)
            $ingresos = (float) $session->movements()->where('tipo', 'ingreso')->sum('monto');
            $retiros = (float) $session->movements()->whereIn('tipo', ['egreso', 'retiro'])->sum('monto');

            // 3. Saldo esperado en caja
            $saldoEsperado = round($session->saldo_inicial + $ventasEfectivo + $ingresos - $retiros, 2);
            $diferencia = round($saldoContado - $saldoEsperado, 2);

            $session->update([
                'saldo_final_esperado' => $saldoEsperado,
                'saldo_final_contado'  => $saldoContado,
                'diferencia'           => $diferencia,
                'estado'               => 'cerrada',
                'closed_at'            => now(),
                'observaciones_cierre' => $validated['observaciones'] ?? $validated['observaciones_cierre'] ?? null,
            ]);
        });

        $session->refresh();

        $msgDiferencia = $session->diferencia == 0
            ? 'Arqueo perfecto (Sin diferencias).'
            : ($session->diferencia > 0
                ? 'Sobrante detectado de $' . number_format($session->diferencia, 2)
                : 'Faltante detectado de $' . number_format(abs($session->diferencia), 2));

        return redirect()->route('ventas.lista')
            ->with('success', 'Turno de caja cerrado exitosamente. ' . $msgDiferencia);
    }

    /**
     * Registrar Movimiento de Efectivo durante el turno (Ingreso o Egreso/Retiro)
     */
    public function movement(Request $request, CashSession $session): RedirectResponse
    {
        if ($session->estado !== 'abierta') {
            return back()->with('error', 'No se pueden registrar movimientos en una sesión cerrada.');
        }

        $validated = $request->validate([
            'tipo'   => ['required', 'in:ingreso,egreso,retiro'],
            'monto'  => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        CashMovement::create([
            'cash_session_id' => $session->id,
            'tipo'            => $validated['tipo'],
            'monto'           => (float) $validated['monto'],
            'motivo'          => $validated['motivo'],
            'user_id'         => $request->user()->id,
        ]);

        $tipoTexto = $validated['tipo'] === 'ingreso' ? 'Ingreso de efectivo' : 'Retiro / Egreso de efectivo';

        return redirect()->route('ventas.lista')
            ->with('success', $tipoTexto . ' por $' . number_format($validated['monto'], 2) . ' registrado.');
    }
}
