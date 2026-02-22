<?php

namespace App\Services\WhatsApp;

use App\Events\WaiterRequested;
use App\Models\Guest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Table;
use App\Services\Payment\MpesaService;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentBot
{
    protected WhatsAppService $whatsappService;

    protected ConversationManager $conversationManager;

    protected PaymentService $paymentService;

    protected MpesaService $mpesaService;

    public function __construct(
        WhatsAppService $whatsappService,
        ConversationManager $conversationManager,
        PaymentService $paymentService,
        MpesaService $mpesaService
    ) {
        $this->whatsappService = $whatsappService;
        $this->conversationManager = $conversationManager;
        $this->paymentService = $paymentService;
        $this->mpesaService = $mpesaService;
    }

    /**
     * Start the payment flow — show bill and payment options.
     */
    public function start(Guest $guest): void
    {
        $order = $this->findUnpaidOrder($guest);

        if (! $order) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "You don't have any unpaid orders. Type 'menu' to browse our menu!"
            );
            $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

            return;
        }

        // Store order ID in session
        $this->conversationManager->setCurrentOrder($guest->phone_number, $order->id);

        // Generate and send bill
        $bill = $this->paymentService->generateBill($order);

        $billText = "Your Bill - Order #{$order->order_number}\n\n";
        foreach ($bill['items'] as $item) {
            $billText .= "{$item['name']} x{$item['quantity']} — TZS ".number_format($item['subtotal'], 0)."\n";
        }
        $billText .= "\nSubtotal: TZS ".number_format($bill['breakdown']['subtotal'], 0);
        $billText .= "\nTax: TZS ".number_format($bill['breakdown']['tax'], 0);
        $billText .= "\nTotal: TZS ".number_format($bill['breakdown']['total'], 0);

        if ($bill['payment_info']['total_paid'] > 0) {
            $billText .= "\nPaid: TZS ".number_format($bill['payment_info']['total_paid'], 0);
            $billText .= "\nBalance Due: TZS ".number_format($bill['payment_info']['balance_due'], 0);
        }

        $this->whatsappService->sendTextMessage($guest->phone_number, $billText);

        // Build payment method options as a list
        $rows = [];

        if ($this->mpesaService->isEnabled()) {
            $rows[] = ['id' => 'pay_mpesa', 'title' => 'M-Pesa', 'description' => 'Pay via M-Pesa STK Push'];
        }

        $rows[] = ['id' => 'pay_card', 'title' => 'Pay by Card', 'description' => 'Pay online via Stripe'];
        $rows[] = ['id' => 'pay_qr', 'title' => 'QR Code Payment', 'description' => 'Scan QR code to pay'];
        $rows[] = ['id' => 'pay_cash', 'title' => 'Cash at Table', 'description' => 'Pay cash or card at table'];

        $this->whatsappService->sendListMessage(
            $guest->phone_number,
            "How would you like to pay?",
            'Payment Methods',
            [['title' => 'Choose Payment Method', 'rows' => $rows]]
        );

        $this->conversationManager->setState($guest->phone_number, 'PAYMENT_METHOD');
    }

    /**
     * Route to appropriate handler based on state.
     */
    public function handleState(Guest $guest, string $state, array $messageData): void
    {
        match ($state) {
            'PAYMENT_METHOD' => $this->handleMethodSelection($guest, $messageData),
            'PAYMENT_MPESA' => $this->handleMpesaPhone($guest, $messageData),
            'PAYMENT_PROCESSING' => $this->handlePaymentProcessing($guest, $messageData),
            default => $this->start($guest),
        };
    }

    /**
     * Handle payment method selection.
     */
    protected function handleMethodSelection(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;
        $listId = $messageData['list_id'] ?? null;
        $text = strtolower(trim($messageData['text'] ?? ''));

        $selection = $listId ?? $buttonId ?? $text;

        match ($selection) {
            'pay_mpesa', 'mpesa', 'm-pesa', 'lipa' => $this->startMpesaPayment($guest),
            'pay_card', 'card', 'stripe' => $this->sendStripePaymentLink($guest),
            'pay_qr', 'qr', 'qr code' => $this->sendQRCodePayment($guest),
            'pay_link', 'link' => $this->sendStripePaymentLink($guest),
            'pay_cash', 'cash' => $this->handleCashPayment($guest),
            default => $this->start($guest),
        };
    }

    /**
     * Start M-Pesa payment — ask for phone number.
     */
    protected function startMpesaPayment(Guest $guest): void
    {
        if (! $this->mpesaService->isEnabled()) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "M-Pesa payments are currently unavailable. Please choose another method."
            );
            $this->start($guest);

            return;
        }

        $phone = $guest->phone_number;

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            "We'll send an M-Pesa payment request to your phone.\n\nUse this number: {$phone}?",
            [
                ['id' => 'use_my_number', 'title' => substr('Yes '.$this->maskPhone($phone), 0, 20)],
                ['id' => 'enter_number', 'title' => 'Different number'],
            ]
        );

        $this->conversationManager->setState($guest->phone_number, 'PAYMENT_MPESA');
    }

    /**
     * Handle M-Pesa phone number input or confirmation.
     */
    protected function handleMpesaPhone(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;
        $text = trim($messageData['text'] ?? '');

        $phone = null;

        if ($buttonId === 'use_my_number') {
            $phone = $guest->phone_number;
        } elseif ($buttonId === 'enter_number') {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please enter the M-Pesa phone number (e.g., 0712345678):"
            );

            return;
        } elseif (preg_match('/\d{9,15}/', $cleanedText = preg_replace('/[^0-9]/', '', $text))) {
            $phone = $cleanedText;
        } else {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please enter a valid phone number (e.g., 0712345678)."
            );

            return;
        }

        $order = $this->findUnpaidOrder($guest);
        if (! $order) {
            $this->whatsappService->sendTextMessage($guest->phone_number, "No unpaid order found.");
            $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

            return;
        }

        try {
            $balanceDue = $this->getBalanceDue($order);

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $balanceDue,
                'payment_method' => 'mobile',
                'status' => 'pending',
                'transaction_id' => null,
                'gateway_response' => ['phone' => $phone, 'provider' => 'mpesa'],
            ]);

            // Initiate STK Push
            $result = $this->mpesaService->initiateSTKPush(
                $phone,
                $balanceDue,
                $order->order_number
            );

            if (isset($result['CheckoutRequestID'])) {
                $payment->update(['transaction_id' => $result['CheckoutRequestID']]);

                $this->whatsappService->sendTextMessage(
                    $guest->phone_number,
                    "An M-Pesa payment request has been sent to your phone.\n\nPlease enter your M-Pesa PIN to complete the payment of TZS ".number_format($balanceDue, 0).".\n\nWe'll confirm once the payment is received."
                );

                $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');
            } else {
                $payment->update(['status' => 'failed']);

                $this->whatsappService->sendTextMessage(
                    $guest->phone_number,
                    "Sorry, we couldn't initiate the M-Pesa payment. Please try again or choose another payment method."
                );
                $this->conversationManager->setState($guest->phone_number, 'PAYMENT_METHOD');
            }
        } catch (\Exception $e) {
            Log::error('M-Pesa STK push failed', [
                'guest' => $guest->phone_number,
                'error' => $e->getMessage(),
            ]);

            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Sorry, M-Pesa payment failed. Please try again or choose another method. Type 'pay' to retry."
            );
            $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');
        }
    }

    /**
     * Generate and send a Stripe/card payment link.
     */
    protected function sendStripePaymentLink(Guest $guest): void
    {
        $order = $this->findUnpaidOrder($guest);
        if (! $order) {
            $this->whatsappService->sendTextMessage($guest->phone_number, "No unpaid order found.");
            $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

            return;
        }

        $balanceDue = $this->getBalanceDue($order);
        $token = Str::random(32);

        Payment::create([
            'order_id' => $order->id,
            'amount' => $balanceDue,
            'payment_method' => 'card',
            'status' => 'pending',
            'transaction_id' => 'LINK-'.strtoupper(Str::random(8)),
            'token' => $token,
            'token_expires_at' => now()->addHours(24),
        ]);

        $paymentUrl = url("/pay/{$token}");

        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "Here's your secure payment link:\n\n{$paymentUrl}\n\nAmount: TZS ".number_format($balanceDue, 0)."\n\nPay with Visa, Mastercard, or other cards. This link expires in 24 hours."
        );

        $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

        Log::info('Stripe payment link sent via WhatsApp', [
            'guest' => $guest->phone_number,
            'order' => $order->order_number,
            'token' => $token,
        ]);
    }

    /**
     * Generate a QR code for payment and send as image.
     */
    protected function sendQRCodePayment(Guest $guest): void
    {
        $order = $this->findUnpaidOrder($guest);
        if (! $order) {
            $this->whatsappService->sendTextMessage($guest->phone_number, "No unpaid order found.");
            $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

            return;
        }

        $balanceDue = $this->getBalanceDue($order);
        $token = Str::random(32);

        Payment::create([
            'order_id' => $order->id,
            'amount' => $balanceDue,
            'payment_method' => 'card',
            'status' => 'pending',
            'transaction_id' => 'QR-'.strtoupper(Str::random(8)),
            'token' => $token,
            'token_expires_at' => now()->addHours(24),
        ]);

        $paymentUrl = url("/pay/{$token}");

        // Generate QR code locally and store it
        $qrCodeImage = QrCode::format('png')->size(400)->margin(1)->generate($paymentUrl);
        $qrFileName = "payment-qr/{$token}.png";
        Storage::disk('public')->put($qrFileName, $qrCodeImage);
        $qrCodeUrl = Storage::disk('public')->url($qrFileName);

        $this->whatsappService->sendImageMessage(
            $guest->phone_number,
            $qrCodeUrl,
            "Scan this QR code to pay TZS ".number_format($balanceDue, 0)." for Order #{$order->order_number}"
        );

        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "Or use this link: {$paymentUrl}\n\nExpires in 24 hours."
        );

        $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

        Log::info('QR code payment sent via WhatsApp', [
            'guest' => $guest->phone_number,
            'order' => $order->order_number,
            'token' => $token,
        ]);
    }

    /**
     * Handle cash/card at table payment — notify waiter.
     */
    protected function handleCashPayment(Guest $guest): void
    {
        $session = $this->conversationManager->getSession($guest->phone_number);

        if ($session->current_table_id) {
            $table = Table::find($session->current_table_id);
            if ($table) {
                event(new WaiterRequested($table, $guest));

                $this->whatsappService->sendTextMessage(
                    $guest->phone_number,
                    "Your waiter has been notified to come collect your payment at {$table->name}.\n\nYou can pay by cash or card at your table. Thank you!"
                );
            } else {
                $this->whatsappService->sendTextMessage(
                    $guest->phone_number,
                    "Please ask any available waiter to collect your payment. You can pay by cash or card."
                );
            }
        } else {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please ask any available waiter to collect your payment, or let us know your table number.\n\nYou can pay by cash or card."
            );
        }

        $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');
    }

    /**
     * Handle waiting for payment processing.
     */
    protected function handlePaymentProcessing(Guest $guest, array $messageData): void
    {
        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "Your payment is being processed. We'll notify you once it's confirmed.\n\nType 'menu' for the main menu."
        );
        $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');
    }

    /**
     * Find the guest's unpaid order.
     */
    protected function findUnpaidOrder(Guest $guest): ?Order
    {
        $session = $this->conversationManager->getSession($guest->phone_number);

        // Check session's current order
        if ($session->current_order_id) {
            $order = Order::find($session->current_order_id);
            if ($order && ! in_array($order->status, ['paid', 'cancelled', 'completed'])) {
                return $order;
            }
        }

        // Find the most recent unpaid order
        return Order::where('guest_id', $guest->id)
            ->whereNotIn('status', ['paid', 'cancelled', 'completed'])
            ->latest()
            ->first();
    }

    /**
     * Get the remaining balance due for an order.
     */
    protected function getBalanceDue(Order $order): float
    {
        $totalPaid = $order->payments()
            ->where('status', 'completed')
            ->sum('amount');

        return max(0, $order->total - $totalPaid);
    }

    /**
     * Mask a phone number for display.
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) < 6) {
            return $phone;
        }

        return substr($phone, 0, 3).'****'.substr($phone, -3);
    }
}
