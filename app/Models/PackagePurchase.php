<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'wallet_transaction_id',
        'price_paid',
        'gems_received',
        'status',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'gems_received' => 'integer',
    ];

    /**
     * Get the user that made the purchase
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package that was purchased
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the wallet transaction for this purchase
     */
    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
