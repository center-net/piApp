<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تطبيق Pi</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero {
            background-color: #4a69bd;
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #4a69bd;
            border-color: #4a69bd;
        }
        .btn-primary:hover {
            background-color: #3c5aa6;
            border-color: #3c5aa6;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">تطبيق Pi</a>
        </div>
    </nav>

    <div class="hero">
        <div class="container">
            <h1 class="display-4">مرحباً بك في تطبيق Pi</h1>
            <p class="lead">تطبيق يتيح لك استخدام Pi للدفعات والمصادقة</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">ميزات التطبيق</h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">• تسجيل الدخول باستخدام Pi</li>
                            <li class="list-group-item">• إنشاء دفعات جديدة</li>
                            <li class="list-group-item">• إدارة حسابك</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">ابدأ الآن</h3>
                        <p class="card-text">سجل الدخول باستخدام حسابك على Pi للبدء في استخدام التطبيق.</p>
                        <a href="{{ route('login') }}" class="btn btn-primary">تسجيل الدخول</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>