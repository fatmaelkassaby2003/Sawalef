# 📦 Sawalef Wallet & Packages API Documentation

## نظام المحفظة والباقات - دليل المطور

---

## 🔑 Authentication

جميع الـ endpoints المحمية تتطلب token من Sanctum:

```
Authorization: Bearer {your_token}
```

---

## 📦 Packages Endpoints

### 1️⃣ Get All Active Packages
**احصل على جميع الباقات النشطة**

```http
GET /api/packages
```

**Response:**
```json
{
    "status": true,
    "message": "تم جلب الباقات بنجاح",
    "data": [
        {
            "id": 1,
            "name": "باقة المبتدئين 🌟",
            "description": "باقة رائعة للبدء مع عدد جيد من الجواهر",
            "gems": 100,
            "price": "50.00",
            "is_active": true,
            "order": 1,
            "created_at": "2026-01-25T00:00:00.000000Z",
            "updated_at": "2026-01-25T00:00:00.000000Z"
        }
    ]
}
```

---

### 2️⃣ Get Single Package
**احصل على تفاصيل باقة واحدة**

```http
GET /api/packages/{id}
```

**Response:**
```json
{
    "status": true,
    "message": "تم جلب الباقة بنجاح",
    "data": {
        "id": 1,
        "name": "باقة المبتدئين 🌟",
        "gems": 100,
        "price": "50.00"
    }
}
```

---

## 💰 Wallet Endpoints

### 3️⃣ Get Wallet Balance
**احصل على رصيد المحفظة والجواهر**

```http
GET /api/wallet/balance
```

**Response:**
```json
{
    "status": true,
    "message": "تم جلب بيانات المحفظة بنجاح",
    "data": {
        "wallet_balance": 500.50,
        "gems": 1250
    }
}
```

---

### 4️⃣ Get Transaction History
**احصل على سجل معاملات المحفظة**

```http
GET /api/wallet/transactions
```

**Query Parameters:**
- `page` (optional): رقم الصفحة (default: 1)

**Response:**
```json
{
    "status": true,
    "message": "تم جلب سجل المعاملات بنجاح",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "type": "deposit",
                "amount": "100.00",
                "balance_before": "0.00",
                "balance_after": "100.00",
                "status": "completed",
                "payment_method": "Credit/Debit Card",
                "reference_number": "WTX-65B4A2F1",
                "created_at": "2026-01-25T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 5
    }
}
```

---

### 5️⃣ Initiate Deposit (Charge Wallet)
**بدء عملية شحن المحفظة**

```http
POST /api/wallet/deposit
```

**Request Body:**
```json
{
    "amount": 100,
    "payment_method_id": 1
}
```

**Payment Methods:**
- `1` - Credit/Debit Card (Visa/MasterCard)
- `2` - Vodafone Cash
- `4` - Meeza
- `5` - Fawry

**Response:**
```json
{
    "status": true,
    "message": "تم إنشاء عملية الدفع بنجاح",
    "data": {
        "transaction_id": 123,
        "reference_number": "WTX-65B4A2F1",
        "payment_url": "https://app.fawaterk.com/pay/xxxxx",
        "invoice_id": "12345"
    }
}
```

**ملاحظة:** يجب على المستخدم فتح `payment_url` لإكمال الدفع

---

### 6️⃣ Initiate Withdrawal
**طلب سحب من المحفظة**

```http
POST /api/wallet/withdrawal
```

**Request Body:**
```json
{
    "amount": 200,
    "bank_account": "1234567890"
}
```

**Response:**
```json
{
    "status": true,
    "message": "تم إنشاء طلب السحب بنجاح. سيتم مراجعته من قبل الإدارة",
    "data": {
        "transaction_id": 124,
        "reference_number": "WTX-65B4A301",
        "amount": 200,
        "new_balance": 300.50
    }
}
```

---

### 7️⃣ Purchase Package
**شراء باقة برصيد المحفظة**

```http
POST /api/wallet/purchase-package
```

**Request Body:**
```json
{
    "package_id": 1
}
```

**Success Response:**
```json
{
    "status": true,
    "message": "تم شراء الباقة بنجاح! 🎉",
    "data": {
        "package": {
            "name": "باقة المبتدئين 🌟",
            "gems": 100,
            "price": "50.00"
        },
        "purchase_id": 45,
        "transaction_id": 125,
        "new_balance": 450.50,
        "new_gems": 1350,
        "gems_added": 100
    }
}
```

**Error Response (Insufficient Balance):**
```json
{
    "status": false,
    "message": "رصيد المحفظة غير كافٍ لشراء هذه الباقة",
    "data": {
        "required": 50.00,
        "current_balance": 20.00,
        "shortage": 30.00
    }
}
```

---

## 🔔 Payment Webhooks (For Fawaterak Integration)

### Webhook Endpoint
```http
POST /api/fawaterak/webhook
```

يتم استدعاء هذا الـ endpoint تلقائياً من Fawaterak عند نجاح/فشل الدفع.

### Callback Endpoint
```http
GET/POST /api/fawaterak/callback
```

يتم توجيه المستخدم لهذا الـ URL بعد إتمام الدفع.

---

## 🎯 User Flow Examples

### سيناريو 1: شحن المحفظة
1. المستخدم يطلب شحن 100 جنيه: `POST /api/wallet/deposit`
2. النظام يُنشئ فاتورة في Fawaterak ويعيد `payment_url`
3. المستخدم يفتح `payment_url` ويُكمل الدفع
4. Fawaterak يُرسل webhook للنظام
5. النظام يُحدث رصيد المحفظة تلقائياً
6. المستخدم يُوجه لصفحة النجاح

### سيناريو 2: شراء باقة
1. المستخدم يعرض الباقات: `GET /api/packages`
2. المستخدم يختار باقة معينة
3. المستخدم يشتري الباقة: `POST /api/wallet/purchase-package`
4. النظام يخصم من المحفظة ويُضيف الجواهر فوراً

### سيناريو 3: السحب من المحفظة
1. المستخدم يطلب سحب: `POST /api/wallet/withdrawal`
2. النظام يخصم المبلغ ويُنشئ طلب بحالة "pending"
3. الأدمن يراجع ويوافق على الطلب يدوياً
4. يتم تحويل المبلغ للحساب البنكي

---

## ⚙️ Configuration (.env)

```env
# Fawaterak API Configuration
FAWATERAK_API_KEY=your_api_key_here
FAWATERAK_BASE_URL=https://app.fawaterk.com/api/v2

# URLs
FAWATERAK_WEBHOOK_URL="${APP_URL}/api/fawaterak/webhook"
FAWATERAK_CALLBACK_URL="${APP_URL}/api/fawaterak/callback"
FAWATERAK_SUCCESS_URL="${APP_URL}/payment/success"
FAWATERAK_FAILURE_URL="${APP_URL}/payment/failed"
```

---

## 🚀 Testing

### Test Package Creation (Admin)
```bash
php artisan db:seed --class=PackageSeeder
```

### Test API Endpoints
استخدم Postman أو أي HTTP client مع الـ endpoints أعلاه

---

## 📊 Database Schema

### packages
- `id`, `name`, `description`, `gems`, `price`, `is_active`, `order`

### wallet_transactions
- `id`, `user_id`, `type`, `amount`, `balance_before`, `balance_after`, `status`, `payment_method`, `fawaterak_invoice_id`, `reference_number`

### package_purchases
- `id`, `user_id`, `package_id`, `wallet_transaction_id`, `price_paid`, `gems_received`, `status`

### users (updated)
- Added: `wallet_balance`, `gems`

---

## 🎨 Admin Dashboard (Filament)

الأدمن يستطيع:
- ✅ إضافة/تعديل/حذف الباقات
- ✅ عرض جميع المعاملات المالية
- ✅ مراجعة طلبات السحب

للوصول: `/admin/packages`

---

## 🔒 Security Notes

- ✅ جميع endpoints محمية بـ Sanctum (ماعدا webhooks)
- ✅ يتم التحقق من البيانات باستخدام Validators
- ✅ استخدام Transactions للعمليات المالية
- ✅ تسجيل جميع العمليات في Logs

---

**Made with ❤️ for Sawalef**
