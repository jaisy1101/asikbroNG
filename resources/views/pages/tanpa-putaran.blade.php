@extends('layouts.app')

@section('content')

<div class="card shadow">

    <div class="card-body text-center py-5">

        <i class="fas fa-lock mb-4"
           style="font-size: 60px; color: var(--brand-blue);">
        </i>

        <h5 class="font-weight-bold text-gray-800">
            {{ $judul }} belum dapat dibuka
        </h5>

        <p class="text-gray-600 mb-4">
            {{ $pesan }}
        </p>

        <a href="/" class="btn btn-primary">
            <i class="fas fa-home mr-2"></i>
            Kembali ke Dashboard
        </a>

    </div>

</div>

@endsection
