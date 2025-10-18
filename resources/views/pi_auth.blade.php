<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login with Pi</title>
</head>

<body>
    <h2>Login with Pi</h2>
    <button id="btnLogin" disabled>جاري التهيئة...</button>
    <div id="status" style="margin-top: 10px;"></div>

    <script src="https://sdk.minepi.com/pi-sdk.js"></script>
    <script>
        // تهيئة Pi SDK قبل أي استخدام آخر
        let piInitialized = false;
        let piAuth = null; // تعريف متغير auth بشكل صريح

        async function initPiSDK() {
            try {
                // تهيئة Pi SDK
                await Pi.init({
                    version: '2.0', // استخدام أحدث نسخة متاحة
                    sandbox: true, // أو false للإنتاج
                });

                piInitialized = true;
                document.getElementById('btnLogin').disabled = false;
                document.getElementById('btnLogin').textContent = 'Login with Pi';
                document.getElementById('status').textContent = 'Pi SDK تم تهيئته بنجاح';
            } catch (e) {
                console.error('فشل تهيئة Pi SDK:', e);
                document.getElementById('status').textContent = 'فشل تهيئة Pi SDK: ' + e.message;
            }
        }

        // تهيئة Pi SDK عند تحميل الصفحة
        window.addEventListener('load', initPiSDK);

        // مثال عام، عدّل حسب توثيق Pi SDK الفعلي
        async function piLogin() {
            if (!piInitialized) {
                alert('Pi SDK لم يتم تهيئته بعد');
                return;
            }

            try {
                document.getElementById('status').textContent = 'جاري محاولة تسجيل الدخول...';
                document.getElementById('btnLogin').disabled = true;

                // scopes حسب توثيق Pi
                const scopes = ['username', 'payments'];
                piAuth = await Pi.authenticate(scopes);
                console.log(piAuth); // تحقق من الحقول

                // piAuth قد يحتوي على accessToken, username, publicKey
                const payload = {
                    accessToken: piAuth.accessToken || piAuth.token || '',
                    username: piAuth.username || '',
                    publicKey: piAuth.publicKey || piAuth.pubKey || ''
                };

                document.getElementById('status').textContent = 'جاري إرسال البيانات إلى الخادم...';

                // إرسال إلى Laravel API
                const res = await fetch('/api/pi/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok) {
                    // إذا تعمل داخل WebView: نرسل رسالة إلى Flutter أو نجري redirect بخيار token
                    // طريقتان: 1) window.flutter_inappwebview.postMessage  2) redirect with token in URL hash
                    // سنستخدم تغيير window.location إلى custom-scheme مع token
                    const token = data.token;
                    document.getElementById('status').textContent = 'تم تسجيل الدخول بنجاح! جاري التوجيه...';
                    // طريقة آمنة: نعيد التوكن في عنوان URL (hash)
                    window.location.href = 'https://callback.local/#token=' + token;
                } else {
                    document.getElementById('status').textContent = 'فشل المصادقة';
                    alert('Auth failed: ' + JSON.stringify(data));
                    document.getElementById('btnLogin').disabled = false;
                }
            } catch (e) {
                console.error(e);
                document.getElementById('status').textContent = 'حدث خطأ: ' + e.message;
                alert('Error: ' + e.message);
                document.getElementById('btnLogin').disabled = false;
            }
        }

        document.getElementById('btnLogin').addEventListener('click', piLogin);
    </script>
</body>

</html>
