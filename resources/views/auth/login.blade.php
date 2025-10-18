@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">تسجيل الدخول</div>

                <div class="card-body">
                    <form method="GET" action="{{ route('pi.redirect') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            تسجيل الدخول باستخدام Pi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection