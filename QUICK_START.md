# 🚀 دليل البدء السريع

## المتطلبات

- PHP 8.1+
- Composer
- Node.js 16+
- Flutter 3.0+
- MySQL 8.0+

---

## 🔧 إعداد Backend (Laravel)

### 1. تثبيت الـ Dependencies:
```bash
cd c:\laragon\www\piApp
composer install
```

### 2. إعداد ملف `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

### 3. تعديل `.env` بقيم Pi:
```env
DB_DATABASE=piapp
DB_USERNAME=root
DB_PASSWORD=

PI_CLIENT_ID=YOUR_PI_CLIENT_ID
PI_CLIENT_SECRET=YOUR_PI_CLIENT_SECRET
PI_REDIRECT_URI=http://localhost:8000/pi/callback
PI_API_KEY=YOUR_PI_API_KEY
PI_VERIFY_ENDPOINT=https://api.minepi.com/v1/user
PI_SANDBOX=true
```

### 4. إنشاء قاعدة البيانات:
```bash
php artisan migrate
```

### 5. تشغيل السيرفر:
```bash
php artisan serve
```

السيرفر سيكون متاحاً على: `http://localhost:8000`

---

## 📱 إعداد Frontend (Flutter)

### 1. الذهاب لمجلد Flutter:
```bash
cd c:\laragon\www\piApp\frontend
```

### 2. تثبيت الـ Dependencies:
```bash
flutter pub get
```

### 3. تعديل `lib/services/pi_auth_service.dart`:
```dart
// غير هذا السطر إلى رابط الخادم الفعلي
static const String _baseUrl = 'http://localhost:8000'; // للتطوير المحلي
```

### 4. تشغيل التطبيق:

**Android:**
```bash
flutter run -d android-emulator
```

**iOS:**
```bash
flutter run -d iphone
```

**Web:**
```bash
flutter run -d chrome
```

---

## 🧪 اختبار المصادقة

### طريقة 1: اختبار مباشر عبر Postman

1. افتح Postman
2. أنشئ طلب POST إلى: `http://localhost:8000/api/pi/auth`
3. أضف Headers:
```json
{
  "Content-Type": "application/json"
}
```

4. أضف Body:
```json
{
  "accessToken": "test_token_123",
  "username": "test_user",
  "publicKey": "test_public_key_123"
}
```

5. اضغط Send وشاهد الاستجابة

### طريقة 2: اختبار من المتصفح

1. افتح: `http://localhost:8000/pi-login`
2. سيظهر الزر "جاري التهيئة..."
3. إذا لم يتغير، افتح Console (F12) لرؤية الأخطاء
4. تحقق من أن Pi SDK يحمل بنجاح

---

## 📋 تسلسل المصادقة

```
1. المستخدم يفتح التطبيق
   ↓
2. يضغط على "تسجيل الدخول مع Pi"
   ↓
3. ينفتح WebView مع http://localhost:8000/pi-login
   ↓
4. يتحمل Pi SDK
   ↓
5. المستخدم يضغط الزر
   ↓
6. Pi.authenticate() يعيد accessToken
   ↓
7. يرسل إلى /api/pi/auth
   ↓
8. الخادم يتحقق من الـ token
   ↓
9. ينشئ/يحدث المستخدم
   ↓
10. يرسل API token
    ↓
11. الـ App يحفظ الـ token
    ↓
12. ينتقل إلى الصفحة الرئيسية
```

---

## 🐛 استكشاف الأخطاء

### خطأ: "Pi SDK غير متاح"
- تحقق من اتصال الإنترنت
- تأكد من أن `https://sdk.minepi.com/pi-sdk.js` يحمل

### خطأ: "فشل التحقق من التوكن"
- تحقق من قيمة `PI_API_KEY` في `.env`
- تحقق من نقطة النهاية `PI_VERIFY_ENDPOINT`
- شاهد السجلات: `storage/logs/laravel.log`

### خطأ: "انتهت صلاحية الجلسة"
- الـ token انتهى
- امسح البيانات وحاول مجدداً

### المستخدم لا ينتقل إلى الصفحة الرئيسية
- تحقق من أن callback يعمل
- اختبر `/api/me` مع الـ token

---

## 📊 الملفات المهمة

| الملف | الوصف |
|------|-------|
| `app/Http/Controllers/Api/PiAuthController.php` | معالج المصادقة الرئيسي |
| `resources/views/pi_auth.blade.php` | صفحة المصادقة |
| `frontend/lib/services/pi_auth_service.dart` | خدمة المصادقة في Flutter |
| `routes/api.php` | API routes |
| `app/Models/User.php` | نموذج المستخدم |

---

## 🔑 المتغيرات البيئية المهمة

```env
# قاعدة البيانات
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=piapp
DB_USERNAME=root
DB_PASSWORD=

# Pi Network
PI_CLIENT_ID=
PI_CLIENT_SECRET=
PI_API_KEY=
PI_SANDBOX=true
```

---

## 📦 البيانات المحفوظة في قاعدة البيانات

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    pi_username VARCHAR(255) UNIQUE,
    public_key VARCHAR(500) UNIQUE,
    email VARCHAR(255) UNIQUE,
    pi_id VARCHAR(255) UNIQUE,
    pi_access_token VARCHAR(MAX) ENCRYPTED,
    pi_refresh_token VARCHAR(MAX) ENCRYPTED,
    last_login_at TIMESTAMP NULL,
    meta JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🎯 الخطوات التالية

1. ✅ إنهاء الإعداد الأساسي
2. ⬜ الحصول على credentials من Pi Network
3. ⬜ اختبار المصادقة الكاملة
4. ⬜ نشر على الـ production
5. ⬜ إضافة features إضافية

---

## 💬 الدعم والمساعدة

- تحقق من `IMPROVEMENTS.md` للتفاصيل الكاملة
- اقرأ التعليقات في الكود
- اتبع الـ logs في `storage/logs/laravel.log`

---

**نسخة الدليل:** 1.0.0  
**آخر تحديث:** 2024
