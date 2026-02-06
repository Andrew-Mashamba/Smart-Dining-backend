<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Tip;

class TipService
{
    /**
     * Process a tip for a waiter
     */
    public function processTip(
        Order $order,
        float $amount,
        Staff $waiter,
        string $method = 'cash',
        ?Payment $payment = null
    ): Tip {
        if (! $waiter->isWaiter()) {
            throw new \Exception('Tips can only be given to waiters');
        }

        if ($amount <= 0) {
            throw new \Exception('Tip amount must be greater than zero');
        }

        $tip = Tip::create([
            'order_id' => $order->id,
            'payment_id' => $payment?->id,
            'waiter_id' => $waiter->id,
            'amount' => $amount,
            'tip_method' => $method,
        ]);

        \Log::info('Tip processed', [
            'tip_id' => $tip->id,
            'order_id' => $order->id,
            'waiter_id' => $waiter->id,
            'amount' => $amount,
        ]);

        return $tip;
    }

    /**
     * Calculate suggested tip amounts
     */
    public function suggestTipAmounts(Order $order): array
    {
        $baseAmount = $order->total_amount;

        return [
            'no_tip' => 0,
            'standard' => round($baseAmount * 0.10, 2), // 10%
            'good' => round($baseAmount * 0.15, 2),     // 15%
            'excellent' => round($baseAmount * 0.20, 2), // 20%
            'custom' => null,
        ];
    }

    /**
     * Get tips for a specific waiter
     */
    public function getWaiterTips(Staff $waiter, ?string $startDate = null, ?string $endDate = null): array
    {
        if (! $waiter->isWaiter()) {
            throw new \Exception('Staff member must be a waiter');
        }

        $query = Tip::where('waiter_id', $waiter->id);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $tips = $query->with('order')->get();

        return [
            'waiter' => [
                'id' => $waiter->id,
                'name' => $waiter->name,
            ],
            'period' => [
                'start' => $startDate ?? 'all time',
                'end' => $endDate ?? 'present',
            ],
            'summary' => [
                'total_tips' => $tips->sum('amount'),
                'tip_count' => $tips->count(),
                'average_tip' => $tips->count() > 0 ? round($tips->avg('amount'), 2) : 0,
            ],
            'by_method' => $tips->groupBy('tip_method')->map(function ($methodTips) {
                return [
                    'count' => $methodTips->count(),
                    'total' => $methodTips->sum('amount'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get daily tip summary for all waiters
     */
    public function getDailyTipSummary(string $date): array
    {
        $startOfDay = \Carbon\Carbon::parse($date)->startOfDay();
        $endOfDay = \Carbon\Carbon::parse($date)->endOfDay();

        $tips = Tip::whereBetween('created_at', [$startOfDay, $endOfDay])
            ->with('waiter')
            ->get();

        $byWaiter = $tips->groupBy('waiter_id')->map(function ($waiterTips) {
            $waiter = $waiterTips->first()->waiter;

            return [
                'waiter_name' => $waiter->name,
                'total_tips' => $waiterTips->sum('amount'),
                'tip_count' => $waiterTips->count(),
                'average_tip' => round($waiterTips->avg('amount'), 2),
            ];
        });

        return [
            'date' => $date,
            'overall' => [
                'total_tips' => $tips->sum('amount'),
                'tip_count' => $tips->count(),
                'average_tip' => $tips->count() > 0 ? round($tips->avg('amount'), 2) : 0,
            ],
            'by_waiter' => $byWaiter->values()->toArray(),
        ];
    }

    /**
     * Process tip from payment
     */
    public function processTipFromPayment(Payment $payment, float $tipAmount): ?Tip
    {
        if ($tipAmount <= 0) {
            return null;
        }

        $order = $payment->order;

        return $this->processTip(
            $order,
            $tipAmount,
            $order->waiter,
            $payment->payment_method,
            $payment
        );
    }

    /**
     * Get tip statistics
     */
    public function getTipStatistics(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Tip::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $tips = $query->with(['order', 'waiter'])->get();

        return [
            'period' => [
                'start' => $startDate ?? 'all time',
                'end' => $endDate ?? 'present',
            ],
            'totals' => [
                'total_tips' => $tips->sum('amount'),
                'tip_count' => $tips->count(),
                'average_tip' => $tips->count() > 0 ? round($tips->avg('amount'), 2) : 0,
            ],
            'by_method' => $tips->groupBy('tip_method')->map(function ($methodTips, $method) {
                return [
                    'method' => $method,
                    'count' => $methodTips->count(),
                    'total' => $methodTips->sum('amount'),
                    'average' => round($methodTips->avg('amount'), 2),
                ];
            })->values()->toArray(),
            'top_waiters' => $tips->groupBy('waiter_id')
                ->map(function ($waiterTips) {
                    $waiter = $waiterTips->first()->waiter;

                    return [
                        'waiter_name' => $waiter->name,
                        'total_tips' => $waiterTips->sum('amount'),
                        'tip_count' => $waiterTips->count(),
                    ];
                })
                ->sortByDesc('total_tips')
                ->take(5)
                ->values()
                ->toArray(),
        ];
    }
}
