# 📸 إرسال الصور في الشات

## ✅ تم إضافة دعم إرسال الصور!

---

## 📋 **كيفية إرسال صورة:**

### Endpoint:
```
POST /api/chat/conversations/{conversationId}/send-image
```

### Headers:
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

### Body (form-data):
```
image: [ملف الصورة]
message: نص اختياري (caption)
```

---

## 🧪 **اختبار في Postman:**

### الخطوات:

1. **افتح Postman**

2. **أنشئ طلب جديد:**
   - Method: **POST**
   - URL: `http://127.0.0.1:8000/api/chat/conversations/1/send-image`

3. **Headers:**
   ```
   Authorization: Bearer YOUR_TOKEN
   Accept: application/json
   ```

4. **Body:**
   - اختر **form-data** (ليس raw!)
   - أضف حقل:
     - Key: `image` (غيّر النوع إلى **File**)
     - Value: اختر صورة من جهازك
   - (اختياري) أضف حقل:
     - Key: `message`
     - Value: `شوف الصورة دي! 😍`

5. **اضغط Send**

---

## 📤 **Response المتوقع:**

```json
{
  "status": true,
  "message": "تم إرسال الصورة بنجاح",
  "data": {
    "id": 5,
    "sender_id": 3,
    "message": "http://127.0.0.1:8000/chat/images/1706187234_abc123.jpg",
    "type": "image",
    "created_at": "2026-01-25T11:00:00.000000Z"
  }
}
```

---

## 🔍 **ملاحظات مهمة:**

### 1. **حجم الصورة:**
- الحد الأقصى: **5 MB**
- إذا كانت الصورة أكبر، ستحصل على خطأ

### 2. **أنواع الصور المدعومة:**
- ✅ JPEG (.jpg, .jpeg)
- ✅ PNG (.png)
- ✅ GIF (.gif)

### 3. **مكان التخزين:**
- يتم حفظ الصور في: `public/chat/images/`
- الرابط الكامل يُحفظ في قاعدة البيانات

### 4. **Real-time Broadcasting:**
- عند إرسال صورة، يتم بثها عبر Pusher تماماً مثل الرسائل النصية!
- المستخدم الآخر يستلمها فوراً

---

## 📱 **في Flutter:**

### إرسال صورة:

```dart
import 'package:http/http.dart' as http;
import 'dart:io';

Future<void> sendImage(File imageFile, int conversationId) async {
  var request = http.MultipartRequest(
    'POST',
    Uri.parse('$baseUrl/api/chat/conversations/$conversationId/send-image'),
  );
  
  // Add headers
  request.headers['Authorization'] = 'Bearer $token';
  request.headers['Accept'] = 'application/json';
  
  // Add image file
  request.files.add(
    await http.MultipartFile.fromPath(
      'image',
      imageFile.path,
    ),
  );
  
  // Optional: Add caption
  // request.fields['message'] = 'شوف الصورة!';
  
  var response = await request.send();
  var responseData = await response.stream.bytesToString();
  
  print('Response: $responseData');
}
```

### عرض الصورة:

```dart
// في قائمة الرسائل
if (message.type == 'image') {
  return Image.network(
    message.message, // الرابط الكامل للصورة
    fit: BoxFit.cover,
    width: 200,
    height: 200,
    loadingBuilder: (context, child, loadingProgress) {
      if (loadingProgress == null) return child;
      return CircularProgressIndicator();
    },
    errorBuilder: (context, error, stackTrace) {
      return Icon(Icons.broken_image);
    },
  );
} else {
  return Text(message.message); // رسالة نصية
}
```

---

## 🔄 **استقبال الصور Real-time (Pusher):**

عندما يرسل شخص صورة، ستصل في نفس Format:

```json
{
  "id": 5,
  "conversation_id": 1,
  "sender_id": 3,
  "message": "http://127.0.0.1:8000/chat/images/1706187234_abc123.jpg",
  "type": "image",
  "created_at": "2026-01-25T11:00:00.000000Z",
  "sender": {
    "id": 3,
    "name": "محمد",
    "avatar": "..."
  }
}
```

في Flutter، تحقق من `type == 'image'` وعرض الصورة!

---

## ⚠️ **Troubleshooting:**

### المشكلة: "The image failed to upload"
**الحل:** تأكد أنك اخترت **form-data** وليس **raw** في Postman

### المشكلة: "File too large"
**الحل:** ضغط الصورة لتكون أقل من 5 MB

### المشكلة: "الصورة لا تظهر في المتصفح"
**الحل:** تأكد أن مجلد `public/chat/images` موجود وله صلاحيات الكتابة

---

## 📦 **تحديث Postman Collection:**

سأضيف endpoint جديد للصور في الـ Collection...

---

## ✅ **الآن يمكنك إرسال الصور!** 📸💬

جرب في Postman وأرسل أول صورة! 🚀
