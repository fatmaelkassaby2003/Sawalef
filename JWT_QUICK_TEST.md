# 🚀 اختبار سريع لـ JWT Authentication

## ✅ تم التحويل من Sanctum إلى JWT بنجاح!

---

## 📝 **اختبار في Postman:**

### **1️⃣ التسجيل (Register)**

```http
POST http://127.0.0.1:8000/api/register
Content-Type: application/json

{
  "name": "أحمد محمد",
  "phone": "+201234567890",
  "age": 25,
  "gender": "male"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Account created successfully",
  "user": { ... },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

✅ **احفظ الـ token** من الـ response!

---

### **2️⃣ تسجيل الدخول (Login + Verify)**

#### **الخطوة 1: إرسال OTP**
```http
POST http://127.0.0.1:8000/api/login
Content-Type: application/json

{
  "phone": "+201234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "OTP sent successfully",
  "otp": "1234"  // للتطوير فقط
}
```

#### **الخطوة 2: التحقق من OTP**
```http
POST http://127.0.0.1:8000/api/verify
Content-Type: application/json

{
  "phone": "+201234567890",
  "otp": "1234"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user": { ... },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

---

### **3️⃣ الوصول للـ API المحمية**

```http
GET http://127.0.0.1:8000/api/profile
Authorization: Bearer YOUR_JWT_TOKEN_HERE
```

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "أحمد محمد",
    "phone": "+201234567890",
    ...
  }
}
```

---

### **4️⃣ تسجيل الخروج**

```http
POST http://127.0.0.1:8000/api/logout
Authorization: Bearer YOUR_JWT_TOKEN_HERE
```

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## 🔑 **مهم:**

### **الفرق عن Sanctum:**

| الموضوع | Sanctum | JWT |
|---------|---------|-----|
| **Token Format** | `1|a8b3c9...` | `eyJ0eXAi...` |
| **Authorization Header** | `Bearer 1\|a8b3c9...` | `Bearer eyJ0eXAi...` |
| **Storage** | قاعدة البيانات | بدون تخزين |
| **Expiration** | لا ينتهي (إلا يدوياً) | ينتهي بعد 60 دقيقة (default) |

---

## ⚡ **إعدادات JWT:**

في ملف `.env`، يمكنك تغيير مدة صلاحية الـ token:

```env
JWT_TTL=1440  # 24 ساعة (بالدقائق)
```

---

## 🐛 **الأخطاء الشائعة:**

### **خطأ 1: "Token not provided"**
```json
{
  "message": "Token not provided"
}
```
✅ **الحل**: تأكد من إضافة `Authorization: Bearer TOKEN` في الـ Headers

---

### **خطأ 2: "Token has expired"**
```json
{
  "message": "Token has expired"
}
```
✅ **الحل**: سجل دخول مرة أخرى للحصول على token جديد

---

### **خطأ 3: "Token is invalid"**
```json
{
  "message": "Token is invalid"
}
```
✅ **الحل**: Token غير صحيح - احصل على token جديد

---

## 📱 **في Flutter:**

```dart
// حفظ الـ token
SharedPreferences prefs = await SharedPreferences.getInstance();
await prefs.setString('auth_token', token);

// استخدام الـ token
String? token = prefs.getString('auth_token');

final response = await http.get(
  Uri.parse('$baseUrl/api/profile'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
  },
);
```

---

## ✅ **التحقق من نجاح التحويل:**

1. ✅ التسجيل يعطي JWT token (يبدأ بـ `eyJ`)
2. ✅ Login + Verify يعطي JWT token
3. ✅ الـ token يعمل مع جميع الـ protected endpoints
4. ✅ Logout ينفذ بدون أخطاء

---

## 🔄 **Refresh Token (اختياري):**

لتجديد token قبل انتهاء صلاحيته:

### **إضافة route:**
في `routes/api.php`:
```php
Route::middleware('auth:api')->post('/refresh', function () {
    return response()->json([
        'token' => auth('api')->refresh()
    ]);
});
```

### **استخدام:**
```http
POST http://127.0.0.1:8000/api/refresh
Authorization: Bearer OLD_TOKEN
```

**Response:**
```json
{
  "token": "NEW_JWT_TOKEN_HERE"
}
```

---

## 🎯 **اختبار سريع - خطوة بخطوة:**

1. افتح Postman
2. سجل مستخدم جديد (`/api/register`)
3. احفظ الـ token من الـ response
4. اختبر `/api/profile` مع الـ token
5. ✅ إذا نجح، JWT يعمل بنجاح!

---

## 📖 **المرجع الكامل:**

لمزيد من التفاصيل، راجع: `JWT_MIGRATION_GUIDE.md`

---

**🎉 تمام! النظام يعمل بـ JWT الآن. جرب الـ API!**
