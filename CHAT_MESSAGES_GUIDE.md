# 💬 إرسال الرسائل (نص + صور) - Endpoint واحد!

## ✅ Endpoint موحد للنص والصور!

تم دمج إرسال النص والصور في **endpoint واحد**! 🎯

---

## 📍 **Endpoint:**
```
POST /api/chat/conversations/{conversationId}/messages
```

---

## 📤 **1. إرسال رسالة نصية:**

### Request:
```http
POST /api/chat/conversations/1/messages
Content-Type: application/json
Authorization: Bearer {token}
```

### Body (JSON):
```json
{
  "message": "مرحباً! كيف حالك؟ 👋"
}
```

### Response:
```json
{
  "status": true,
  "message": "تم إرسال الرسالة بنجاح",
  "data": {
    "id": 5,
    "sender_id": 3,
    "message": "مرحباً! كيف حالك؟ 👋",
    "type": "text",
    "created_at": "2026-01-25T11:00:00.000000Z"
  }
}
```

---

## 📸 **2. إرسال صورة:**

### Request:
```http
POST /api/chat/conversations/1/messages
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

### Body (form-data):
```
image: [ملف الصورة]
message: "شوف الصورة دي! 😍" (اختياري)
```

### Response:
```json
{
  "status": true,
  "message": "تم إرسال الصورة بنجاح",
  "data": {
    "id": 6,
    "sender_id": 3,
    "message": "http://127.0.0.1:8000/chat/images/1706187234_abc123.jpg",
    "type": "image",
    "created_at": "2026-01-25T11:00:00.000000Z"
  }
}
```

---

## 🧪 **اختبار في Postman:**

### إرسال نص:
1. Method: **POST**
2. URL: `http://127.0.0.1:8000/api/chat/conversations/1/messages`
3. Headers: `Authorization: Bearer {token}`
4. Body → **raw** → **JSON**:
   ```json
   { "message": "مرحباً!" }
   ```
5. Send ✅

### إرسال صورة:
1. Method: **POST**
2. URL: `http://127.0.0.1:8000/api/chat/conversations/1/messages`
3. Headers: `Authorization: Bearer {token}`
4. Body → **form-data**:
   - Key: `image` (اختر File)
   - Value: اختر صورة
   - (اختياري) Key: `message`, Value: `شوف الصورة!`
5. Send ✅

---

## 🎯 **المميزات:**

✅ **Endpoint واحد** لكل أنواع الرسائل
✅ **ذكي**: يكتشف تلقائياً إذا كانت صورة أو نص
✅ **مرن**: يمكن إضافة caption للصورة
✅ **Real-time**: الرسائل تُبث عبر Pusher فوراً
✅ **آمن**: التحقق من الصور (نوع، حجم)

---

## 📱 **في Flutter:**

### إرسال نص:
```dart
final response = await http.post(
  Uri.parse('$baseUrl/api/chat/conversations/$conversationId/messages'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
  },
  body: jsonEncode({
    'message': 'مرحباً!',
  }),
);
```

### إرسال صورة:
```dart
var request = http.MultipartRequest(
  'POST',
  Uri.parse('$baseUrl/api/chat/conversations/$conversationId/messages'),
);

request.headers['Authorization'] = 'Bearer $token';
request.files.add(await http.MultipartFile.fromPath('image', imagePath));
// Optional caption:
// request.fields['message'] = 'شوف الصورة!';

var response = await request.send();
```

---

## 🔍 **كيف يعمل؟**

الـ Backend يتحقق:
1. **هل فيه ملف `image`؟**
   - ✅ نعم → يرفع الصورة ويحفظ الرابط
   - ❌ لا → يحفظ النص مباشرة

2. **النوع (`type`) يتحدد تلقائياً:**
   - صورة → `type: "image"`
   - نص → `type: "text"`

3. **Broadcast عبر Pusher** 🔴

---

## ⚙️ **القواعد:**

### للنص:
- ✅ إلزامي: `message`
- 📏 الحد الأقصى: 1000 حرف

### للصور:
- ✅ إلزامي: `image` (ملف)
- 📏 الحد الأقصى: 5 MB
- 🖼️ الأنواع: JPEG, PNG, GIF
- 📝 اختياري: `message` (caption) حتى 500 حرف

---

## ✅ **الخلاصة:**

**Endpoint واحد** يتعامل مع كل شيء! 🎉

- نص؟ → JSON
- صورة؟ → form-data

بسيط وواضح! 💪
