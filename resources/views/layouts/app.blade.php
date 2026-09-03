<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Asikbro</title>

    <!-- Font Awesome -->
    <link href="{{ asset('assets/vendor/fontawesome-free/css/all.min.css') }}"
          rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet">

    <!-- SB Admin -->
    <link href="{{ asset('assets/css/sb-admin-2.min.css') }}"
          rel="stylesheet">

    <!-- Custom -->
    <link href="{{ asset('assets/css/custom.css') }}"
          rel="stylesheet">

</head>


<body id="page-top">

    <div id="wrapper"
         class="asikbro-wrapper">

        {{-- Sidebar --}}
        @include('components.sidebar')


        <!-- Content Wrapper -->
        <div id="content-wrapper"
             class="d-flex flex-column asikbro-content-wrapper">

            <div id="content">

                {{-- Topbar --}}
                @include('components.topbar')


                {{-- Isi halaman --}}
                <div class="container-fluid asikbro-content">

                    @yield('content')

                </div>

            </div>


            {{-- Footer --}}
            @include('components.footer')

        </div>

    </div>


    <!-- JS -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    <script src="{{ asset('assets/js/sb-admin-2.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/chart.js/Chart.min.js') }}"></script>

    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>


    {{-- Script halaman --}}
    @yield('scripts')

</body>

</html>