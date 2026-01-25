# 🔐 دليل اختبار Broadcasting Authentication في Postman

## ⚡ الـ Endpoint:
```
POST https://sawalef.ahdafweb.com/broadcasting/auth
```
أو لو شغال محلي:
```
POST http://127.0.0.1:8000/broadcasting/auth
```

---

## 📋 **الإعدادات المطلوبة في Postman:**

### 1️⃣ **Headers:**
| Key | Value |
|-----|-------|
| `Authorization` | `Bearer YOUR_TOKEN_HERE` |
| `Content-Type` | `application/x-www-form-urlencoded` |
| `Accept` | `application/json` |

> ⚠️ **مهم**: استبدل `YOUR_TOKEN_HERE` بالـ token الفعلي من `/api/login` أو `/api/verify`

---

### 2️⃣ **Body (x-www-form-urlencoded):**
| Key | Value | Description |
|-----|-------|-------------|
| `socket_id` | `123456.789012` | معرف الـ socket من Pusher |
| `channel_name` | `private-conversation.1` | اسم القناة التي تريد الاشتراك فيها |

---

## 🎯 **مثال على القيم:**

### لو عندك `conversation_id = 5`:
```
channel_name: private-conversation.5
socket_id: 123456.789012
```

> 💡 **ملاحظة**: الـ `socket_id` في الواقع يأتي من Pusher تلقائياً عند الاتصال، لكن للاختبار يمكن استخدام أي قيمة.

---

## ✅ **الـ Response المتوقع:**

### **نجاح (200 OK):**
```json
{
  "auth": "06f8a13dbb87f5597a56:a7b8c9d1e2f3g4h5i6j7k8l9m0n1o2p3"
}
```

### **فشل (403 Forbidden):**
```json
{
  "message": "Unauthorized"
}
```

---

## 🔴 **الأخطاء الشائعة:**

### 1. **Response فارغ أو 500:**
- **السبب**: لم يتم إرسال `socket_id` و `channel_name` في الـ Body
- **الحل**: تأكد من إضافتهم في Body tab → x-www-form-urlencoded

### 2. **401 Unauthenticated:**
- **السبب**: الـ Bearer token غير صحيح أو منتهي
- **الحل**: احصل على token جديد من `/api/login` → `/api/verify`

### 3. **403 Forbidden:**
- **السبب**: المستخدم ليس جزءاً من المحادثة
- **الحل**: تأكد من أن المستخدم هو `user_one_id` أو `user_two_id` في الـ conversation

---

## 🧪 **خطوات الاختبار الكاملة:**

### **الخطوة 1: احصل على Token**
```http
POST /api/login
Body: { "phone_number": "+201234567890" }

ثم:
POST /api/verify
Body: { "phone_number": "+201234567890", "otp": "123456" }

Response → احفظ الـ "token"
```

### **الخطوة 2: أنشئ محادثة**
```http
POST /api/chat/conversations/start
Headers: Authorization: Bearer YOUR_TOKEN
Body: { "user_id": 2 }

Response → احفظ الـ "conversation_id"
```

### **الخطوة 3: اختبر Broadcasting Auth**
```http
POST /broadcasting/auth
Headers:
  Authorization: Bearer YOUR_TOKEN
  Content-Type: application/x-www-form-urlencoded
Body (x-www-form-urlencoded):
  socket_id: 123456.789012
  channel_name: private-conversation.1
```

---

## 🎯 **ملاحظات مهمة:**

1. ✅ هذا الـ endpoint يُستخدم تلقائياً من **Pusher Client** في Flutter/Frontend
2. ✅ لا يحتاج المطور للاستدعاء يدوياً إلا للاختبار
3. ✅ الـ `socket_id` يتم توليده تلقائياً من Pusher عند الاتصال
4. ✅ الـ `channel_name` يجب أن يكون بنفس الصيغة: `private-conversation.{id}`

---

## 📱 **في Flutter:**

عند استخدام Pusher في Flutter، يتم استدعاء هذا الـ endpoint **تلقائياً**:

```dart
// لا تحتاج لاستدعاء broadcasting/auth يدوياً!
// Pusher يستدعيه تلقائياً عند subscribe للـ private channel

await pusher.init(
  apiKey: 'YOUR_PUSHER_APP_KEY',
  cluster: 'eu',
  authEndpoint: 'https://sawalef.ahdafweb.com/broadcasting/auth', // ✅ تلقائي!
);

await pusher.subscribe(
  channelName: 'private-conversation.1',
  // سيتم استدعاء /broadcasting/auth تلقائياً هنا
);
```

---

## 🚀 **تم! الآن يمكنك اختبار الـ endpoint بنجاح!** ✅
