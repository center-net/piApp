<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login with Pi</title>
</head>
<body>
  <h2>Login with Pi</h2>
  <button id="btnLogin">Login with Pi</button>

  <script src="https://sdk.minepi.com/pi-sdk.js"></script>
  <script>
    // مثال عام، عدّل حسب توثيق Pi SDK الفعلي
    async function piLogin(){
      try {
        // scopes حسب توثيق Pi
        const scopes = ['username', 'payments'];
        const auth = await Pi.authenticate(scopes);
        // auth قد يحتوي على accessToken, username, publicKey
        const payload = {
          accessToken: auth.accessToken || auth.token || '',
          username: auth.username || '',
          publicKey: auth.publicKey || auth.pubKey || ''
        };

        // إرسال إلى Laravel API
        const res = await fetch('/api/pi/auth', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify(payload)
        });

        const data = await res.json();
        if(res.ok){
          // إذا تعمل داخل WebView: نرسل رسالة إلى Flutter أو نجري redirect بخيار token
          // طريقتان: 1) window.flutter_inappwebview.postMessage  2) redirect with token in URL hash
          // سنستخدم تغيير window.location إلى custom-scheme مع token
          const token = data.token;
          // طريقة آمنة: نعيد التوكن في عنوان URL (hash)
          window.location.href = 'https://callback.local/#token=' + token;
        } else {
          alert('Auth failed: ' + JSON.stringify(data));
        }
      } catch(e){
        console.error(e);
        alert('Error: ' + e.message);
      }
    }

    document.getElementById('btnLogin').addEventListener('click', piLogin);
  </script>
</body>
</html>