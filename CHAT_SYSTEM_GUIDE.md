# 💬 نظام الشات Real-Time باستخدام Pusher

## ✅ تم التثبيت والإعداد!

تم إنشاء نظام شات كامل بين المستخدمين مع دعم real-time عبر Pusher.

---

## 📋 **ما تم إنشاؤه:**

### 1. **قاعدة البيانات**
- ✅ `conversations` - جدول المحادثات بين المستخدمين
- ✅ `messages` - جدول الرسائل

### 2. **Models**
- ✅ `Conversation` - مع علاقات وطرق مساعدة
- ✅ `Message` - مع إمكانية تتبع الرسائل المقروءة

### 3. **Events**
- ✅ `MessageSent` - يبث الرسائل الجديدة عبر Pusher

### 4. **Controller**
- ✅ `ChatController` - يحتوي على جميع endpoints اللازمة

### 5. **API Endpoints**
- ✅ `GET /api/chat/conversations` - جلب جميع المحادثات
- ✅ `POST /api/chat/conversations/start` - بدء محادثة جديدة
- ✅ `GET /api/chat/conversations/{id}/messages` - جلب الرسائل
- ✅ `POST /api/chat/conversations/{id}/messages` - إرسال رسالة

---

## 🔧 **الخطوات المتبقية لتفعيل Pusher:**

### 1️⃣ **الحصول على بيانات Pusher من Dashboard**

من الصورة التي أرسلتها، اتبع الخطوات التالية:

1. افتح **Pusher Dashboard**: https://dashboard.pusher.com
2. اختر **Channels** (ليس Beams)
3. اضغط **Get Started**
4. أنشئ App جديد أو استخدم App موجود
5. ستحصل على:
   - `PUSHER_APP_ID` 
   - `PUSHER_APP_KEY`
   - `PUSHER_APP_SECRET`
   - `PUSHER_APP_CLUSTER` (مثل: `eu`, `us2`, `ap1`)

### 2️⃣ **تحديث ملف `.env`**

افتح ملف `.env` وعدل القيم التالية:

```env
# غيّر من log إلى pusher
BROADCAST_DRIVER=pusher

# ضع البيانات من Pusher Dashboard
PUSHER_APP_ID=your_app_id_here
PUSHER_APP_KEY=your_app_key_here
PUSHER_APP_SECRET=your_app_secret_here
PUSHER_APP_CLUSTER=eu  # أو us2 أو ap1 حسب اختيارك
```

### 3️⃣ **تشغيل الأوامر**

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 📱 **كيفية الاستخدام في التطبيق:**

### 1. **بدء محادثة مع مستخدم**

```http
POST /api/chat/conversations/start
Headers:
  Authorization: Bearer {token}
  Content-Type: application/json
Body:
{
  "user_id": 5
}

Response:
{
  "status": true,
  "message": "تم إنشاء المحادثة بنجاح",
  "data": {
    "conversation_id": 1
  }
}
```

### 2. **جلب قائمة المحادثات**

```http
GET /api/chat/conversations
Headers:
  Authorization: Bearer {token}

Response:
{
  "status": true,
  "data": [
    {
      "id": 1,
      "other_user": {
        "id": 5,
        "name": "أحمد محمد",
        "avatar": "https://..."
      },
      "latest_message": {
        "message": "مرحباً، كيف حالك؟",
        "created_at": "منذ 5 دقائق"
      },
      "unread_count": 3,
      "updated_at": "2026-01-25T10:00:00Z"
    }
  ]
}
```

### 3. **جلب رسائل محادثة معينة**

```http
GET /api/chat/conversations/1/messages
Headers:
  Authorization: Bearer {token}

Response:
{
  "status": true,
  "data": [
    {
      "id": 1,
      "sender_id": 5,
      "message": "مرحباً!",
      "type": "text",
      "is_read": true,
      "created_at": "2026-01-25T09:50:00Z",
      "sender": {
        "id": 5,
        "name": "أحمد",
        "avatar": "https://..."
      }
    }
  ]
}
```

### 4. **إرسال رسالة**

```http
POST /api/chat/conversations/1/messages
Headers:
  Authorization: Bearer {token}
  Content-Type: application/json
Body:
{
  "message": "كيف حالك؟",
  "type": "text"
}

Response:
{
  "status": true,
  "message": "تم إرسال الرسالة بنجاح",
  "data": {
    "id": 2,
    "sender_id": 3,
    "message": "كيف حالك؟",
    "type": "text",
    "created_at": "2026-01-25T10:00:00Z"
  }
}
```

---

## 🔴 **Real-time Events (Pusher)**

عندما يرسل أحد المستخدمين رسالة، يتم إطلاق Event عبر Pusher:

### **Channel:**
```
private-conversation.{conversation_id}
```

### **Event Name:**
```
message.sent
```

### **Data Structure:**
```json
{
  "id": 2,
  "conversation_id": 1,
  "sender_id": 3,
  "message": "كيف حالك؟",
  "type": "text",
  "created_at": "2026-01-25T10:00:00Z",
  "sender": {
    "id": 3,
    "name": "محمد",
    "avatar": "https://..."
  }
}
```

---

## 📱 **كود Flutter للاستماع للرسائل:**

```dart
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

// تهيئة Pusher
PusherChannelsFlutter pusher = PusherChannelsFlutter.getInstance();
await pusher.init(
  apiKey: 'YOUR_PUSHER_APP_KEY',
  cluster: 'eu',
  onEvent: (event) {
    // تتم معالجة الـ event هنا
    print('Event: ${event.eventName}');
    print('Data: ${event.data}');
  },
);

await pusher.connect();

// الاشتراك في قناة المحادثة
await pusher.subscribe(
  channelName: 'private-conversation.1',
  onEvent: (event) {
    if (event.eventName == 'message.sent') {
      // رسالة جديدة!
      Map<String, dynamic> message = jsonDecode(event.data);
      print('New message: ${message['message']}');
      // حدّث واجهة المستخدم
    }
  },
);
```

---

## 🎯 **المميزات:**

- ✅ محادثات فردية (1-to-1)
- ✅ تتبع الرسائل غير المقروءة
- ✅ Real-time messaging عبر Pusher
- ✅ حفظ توقيت آخر قراءة لكل مستخدم
- ✅ دعم أنواع مختلفة من الرسائل (نص، صورة، ملف)
- ✅ حماية المحادثات (يمكن الوصول فقط للمشاركين)

---

## 🚀 **الخطوات التالية:**

1. ✅ احصل على بيانات Pusher من Dashboard
2. ✅ حدّث `.env` بالقيم الصحيحة
3. ✅ شغّل `php artisan config:clear`
4. ✅ اختبر الـ API endpoints في Postman
5. ✅ اربط Flutter app مع Pusher
6. ✅ اختبر الشات real-time! 🎉

---

## 📞 **الدعم:**

إذا واجهت أي مشكلة:
1. تأكد من صحة بيانات Pusher في `.env`
2. تحقق من أن `BROADCAST_DRIVER=pusher`
3. تأكد من تشغيل `php artisan config:clear`
4. راجع Pusher Dashboard للتأكد من استلام الـ events

تم! نظام الشات جاهز للاستخدام! 💬🚀
