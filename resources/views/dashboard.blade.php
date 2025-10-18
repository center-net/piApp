@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">لوحة التحكم</div>

                <div class="card-body">
                    @auth
                        <h3>مرحباً، {{ Auth::user()->name }}!</h3>
                        <p>أنت الآن مسجل الدخول باستخدام Pi.</p>

                        <hr>

                        <h4>إنشاء دفعة جديدة</h4>
                        <form action="{{ route('api.create-payment') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="to" class="form-label">معرّف المستخدم المستلم</label>
                                <input type="text" class="form-control" id="to" name="to" required>
                            </div>
                            <div class="mb-3">
                                <label for="amount" class="form-label">المبلغ</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="memo" class="form-label">ملاحظات</label>
                                <input type="text" class="form-control" id="memo" name="memo">
                            </div>
                            <button type="submit" class="btn btn-primary">إنشاء الدفعة</button>
                        </form>
                    @else
                        <h3>غير مسجل الدخول</h3>
                        <p><a href="{{ route('login') }}">اضغط هنا</a> لتسجيل الدخول باستخدام Pi.</p>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection