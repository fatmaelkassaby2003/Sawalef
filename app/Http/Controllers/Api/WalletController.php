<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Package;
use App\Models\PackagePurchase;
use App\Services\FawaterakService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected $fawaterakService;

    public function __construct(FawaterakService $fawaterakService)
    {
        $this->fawaterakService = $fawaterakService;
    }

    /**
     * Get user wallet balance and gems
     */
    public function getBalance(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'status' => true,
                'message' => 'تم جلب بيانات المحفظة بنجاح',
                'data' => [
                    'wallet_balance' => (float) $user->wallet_balance,
                    'gems' => (int) $user->gems,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المحفظة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet transactions history
     */
    public function getTransactions(Request $request)
    {
        try {
            $user = $request->user();
            
            $transactions = WalletTransaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => true,
                'message' => 'تم جلب سجل المعاملات بنجاح',
                'data' => $transactions
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب سجل المعاملات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available payment methods from Fawaterak
     */
    public function paymentMethods()
    {
        try {
            $result = $this->fawaterakService->getPaymentMethods();

            // If Fawaterak API fails or returns empty data, return static payment methods
            if (!$result['success'] || empty($result['data'])) {
                return response()->json([
                    'status' => true,
                    'message' => 'تم جلب طرق الدفع بنجاح',
                    'data' => $this->getStaticPaymentMethods(),
                    'note' => 'قيد التفعيل - سيتم تفعيل بوابة الدفع قريباً'
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'تم جلب طرق الدفع بنجاح',
                'data' => $result['data']
            ], 200);

        } catch (\Exception $e) {
            // Return static methods as fallback
            return response()->json([
                'status' => true,
                'message' => 'تم جلب طرق الدفع بنجاح',
                'data' => $this->getStaticPaymentMethods(),
                'note' => 'قيد التفعيل - سيتم تفعيل بوابة الدفع قريباً'
            ], 200);
        }
    }

    /**
     * Initiate deposit (charge wallet)
     */
    public function initiateDeposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10',
            'payment_method_id' => 'nullable|integer', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $amount = $request->amount;

            DB::beginTransaction();

            // Create pending transaction
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $user->wallet_balance,
                'balance_after' => $user->wallet_balance, // Will be updated after payment
                'status' => 'pending',
                'payment_method' => $this->getPaymentMethodName($request->payment_method_id),
                'reference_number' => WalletTransaction::generateReferenceNumber(),
            ]);

            // 🧪 TEST MODE: Skip Fawaterak API and create mock payment URL
            $testMode = true; // Set to false when Fawaterak is ready
            
            if ($testMode) {
                // Mock payment URL for testing
                $paymentUrl = url('/payment/test?transaction_id=' . $transaction->id . '&amount=' . $amount);
                $invoiceId = 'TEST_' . $transaction->reference_number;
                
                $transaction->update([
                    'fawaterak_invoice_id' => $invoiceId,
                    'notes' => 'وضع الاختبار - TEST MODE'
                ]);
                
                DB::commit();
                
                return response()->json([
                    'status' => true,
                    'message' => 'تم إنشاء عملية الدفع بنجاح (وضع الاختبار)',
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'reference_number' => $transaction->reference_number,
                        'payment_url' => $paymentUrl,
                        'invoice_id' => $invoiceId,
                        'test_mode' => true,
                        'note' => 'هذا رابط تجريبي - سيتم تفعيل بوابة الدفع الحقيقية قريباً'
                    ]
                ], 200);
            }

            // PRODUCTION MODE: Use actual Fawaterak API
            $invoiceResult = $this->fawaterakService->createInvoice([
                'payment_method_id' => $request->payment_method_id,
                'amount' => $amount,
                'customer_name' => $user->name,
                'customer_email' => $user->email ?? $user->phone . '@sawalef.com',
                'customer_phone' => $user->phone,
                'item_name' => 'شحن محفظة - ' . $amount . ' جنيه',
            ]);

            if (!$invoiceResult['success']) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => $invoiceResult['message'] ?? 'فشل في إنشاء عملية الدفع'
                ], 500);
            }

            // Update transaction with invoice ID
            $transaction->update([
                'fawaterak_invoice_id' => $invoiceResult['data']['invoice_id'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء عملية الدفع بنجاح',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'reference_number' => $transaction->reference_number,
                    'payment_url' => $invoiceResult['data']['payment_data']['redirectTo'] ?? $invoiceResult['data']['url'] ?? null,
                    'invoice_id' => $invoiceResult['data']['invoice_id'] ?? null,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء عملية الدفع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate withdrawal
     */
    public function initiateWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:50',
            'bank_account' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $amount = $request->amount;

            // Check if user has enough balance
            if ($user->wallet_balance < $amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'رصيد المحفظة غير كافٍ'
                ], 400);
            }

            DB::beginTransaction();

            // Deduct from wallet
            $newBalance = $user->wallet_balance - $amount;

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'balance_before' => $user->wallet_balance,
                'balance_after' => $newBalance,
                'status' => 'pending', // Admin needs to approve
                'reference_number' => WalletTransaction::generateReferenceNumber(),
                'notes' => 'رقم الحساب البنكي: ' . $request->bank_account,
            ]);

            $user->update([
                'wallet_balance' => $newBalance
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء طلب السحب بنجاح. سيتم مراجعته من قبل الإدارة',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'reference_number' => $transaction->reference_number,
                    'amount' => $amount,
                    'new_balance' => $newBalance,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء طلب السحب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase package with wallet balance
     */
    public function purchasePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $package = Package::findOrFail($request->package_id);

            // Check if package is active
            if (!$package->is_active) {
                return response()->json([
                    'status' => false,
                    'message' => 'هذه الباقة غير متاحة حالياً'
                ], 400);
            }

            // Check if user has enough balance
            if ($user->wallet_balance < $package->price) {
                return response()->json([
                    'status' => false,
                    'message' => 'رصيد المحفظة غير كافٍ لشراء هذه الباقة',
                    'data' => [
                        'required' => (float) $package->price,
                        'current_balance' => (float) $user->wallet_balance,
                        'shortage' => (float) ($package->price - $user->wallet_balance),
                    ]
                ], 400);
            }

            DB::beginTransaction();

            // Deduct from wallet
            $newBalance = $user->wallet_balance - $package->price;
            $newGems = $user->gems + $package->gems;

            // Create wallet transaction
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'package_purchase',
                'amount' => $package->price,
                'balance_before' => $user->wallet_balance,
                'balance_after' => $newBalance,
                'status' => 'completed',
                'reference_number' => WalletTransaction::generateReferenceNumber(),
                'notes' => 'شراء باقة: ' . $package->name,
            ]);

            // Create package purchase record
            $purchase = PackagePurchase::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'wallet_transaction_id' => $transaction->id,
                'price_paid' => $package->price,
                'gems_received' => $package->gems,
                'status' => 'completed',
            ]);

            // Update user balance and gems
            $user->update([
                'wallet_balance' => $newBalance,
                'gems' => $newGems,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم شراء الباقة بنجاح! 🎉',
                'data' => [
                    'package' => $package->only(['name', 'gems', 'price']),
                    'purchase_id' => $purchase->id,
                    'transaction_id' => $transaction->id,
                    'new_balance' => (float) $newBalance,
                    'new_gems' => (int) $newGems,
                    'gems_added' => (int) $package->gems,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء شراء الباقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get static payment methods (fallback when API is unavailable)
     */
    private function getStaticPaymentMethods()
    {
        return [
            [
                'paymentId' => 1,
                'name_ar' => 'بطاقة ائتمان',
                'name_en' => 'Credit/Debit Card',
                'icon' => '💳',
                'is_active' => true
            ],
            [
                'paymentId' => 2,
                'name_ar' => 'فودافون كاش',
                'name_en' => 'Vodafone Cash',
                'icon' => '📱',
                'is_active' => true
            ],
            [
                'paymentId' => 4,
                'name_ar' => 'ميزة',
                'name_en' => 'Meeza',
                'icon' => '🏦',
                'is_active' => true
            ],
            [
                'paymentId' => 5,
                'name_ar' => 'فوري',
                'name_en' => 'Fawry',
                'icon' => '🏪',
                'is_active' => true
            ],
        ];
    }

    /**
     * Get payment method name
     */
    private function getPaymentMethodName($id)
    {
        $methods = [
            1 => 'Credit/Debit Card',
            2 => 'Vodafone Cash',
            4 => 'Meeza',
            5 => 'Fawry',
        ];

        return $methods[$id] ?? 'Unknown';
    }
}
