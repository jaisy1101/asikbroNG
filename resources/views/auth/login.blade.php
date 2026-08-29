<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Login - ASIKBRO</title>

    <link href="{{ asset('assets/vendor/fontawesome-free/css/all.min.css') }}"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800,900"
          rel="stylesheet">

    <link href="{{ asset('assets/css/sb-admin-2.min.css') }}"
          rel="stylesheet">
</head>


<body style="
    margin: 0;
    min-height: 100vh;
    font-family: 'Nunito', sans-serif;
    background: linear-gradient(135deg, #003b57 0%, #006c9c 100%);
    overflow-x: hidden;
">

    <div class="container-fluid position-relative"
         style="
            min-height: 100vh;
            padding: 0;
         ">

        <!-- Dekorasi Lingkaran -->
        <div style="
            position: absolute;
            top: -60px;
            left: 52%;
            width: 210px;
            height: 210px;
            border-radius: 50%;
            background: linear-gradient(145deg, #5fb8ef, #00507a);
            opacity: .75;
        "></div>

        <div style="
            position: absolute;
            top: 150px;
            right: 70px;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: linear-gradient(145deg, #cfcfcf, #ffffff);
            box-shadow: inset -18px -18px 25px rgba(0,0,0,.20);
        "></div>


        <!-- Konten -->
        <div class="row no-gutters align-items-center"
             style="min-height: calc(100vh - 42px);">


            <!-- ===================================== -->
            <!-- KIRI -->
            <!-- ===================================== -->
            <div class="col-lg-6 d-flex align-items-center">

                <div style="
                    padding-left: 8%;
                    padding-right: 8%;
                    width: 100%;
                ">

                    <!-- Logo -->
                    <img src="{{ asset('assets/img/bps.png') }}"
                         alt="Logo BPS"
                         style="
                            width: 170px;
                            max-width: 100%;
                            margin-bottom: 35px;
                         ">


                    <h2 class="text-white font-weight-bold mb-1"
                        style="
                            font-size: 38px;
                            line-height: 1.1;
                        ">

                        Selamat Datang! di

                    </h2>

                    <h3 class="font-weight-bold mb-1"
                        style="
                            font-size: 29px;
                            color: #2ca7df;
                        ">

                        BPS Provinsi Sulawesi Selatan

                    </h3>

                    <h4 class="text-white font-weight-bold mb-0"
                        style="
                            font-size: 27px;
                        ">

                        Web Rekon PDRB
                        <span style="color: #72bf44;">
                            Triwulanan
                        </span>

                    </h4>

                </div>

            </div>



            <!-- ===================================== -->
            <!-- KANAN -->
            <!-- ===================================== -->
            <div class="col-lg-6 d-flex justify-content-center align-items-center">

                <div style="
                    width: 100%;
                    max-width: 430px;
                    padding: 40px 25px;
                ">

                    <h1 class="text-white font-weight-bold mb-2"
                        style="
                            font-size: 64px;
                            letter-spacing: 1px;
                        ">

                        Masuk

                    </h1>

                    <p class="text-white mb-5"
                       style="
                            font-size: 14px;
                            font-weight: 600;
                       ">

                        Masuk Dengan Akun Pengguna

                    </p>


                    <form>


                        <!-- Nama Pengguna -->
                        <div class="form-group mb-4">

                            <div class="input-group">

                                <div class="input-group-prepend">

                                    <span class="input-group-text border-0"
                                          style="
                                            background: #ffffff;
                                            border-radius: 18px 0 0 18px;
                                            padding-left: 18px;
                                            padding-right: 10px;
                                          ">

                                        <i class="far fa-user"
                                           style="
                                                color: #888;
                                                font-size: 20px;
                                           "></i>

                                    </span>

                                </div>

                                <input type="text"
                                       class="form-control border-0"
                                       placeholder="Nama Pengguna"
                                       style="
                                            height: 52px;
                                            border-radius: 0 18px 18px 0;
                                            font-size: 16px;
                                            font-weight: 600;
                                            letter-spacing: 1px;
                                       ">

                            </div>

                        </div>



                        <!-- Kata Sandi -->
                        <div class="form-group mb-1">

                            <div class="input-group">

                                <div class="input-group-prepend">

                                    <span class="input-group-text border-0"
                                          style="
                                            background: #ffffff;
                                            border-radius: 18px 0 0 18px;
                                            padding-left: 18px;
                                            padding-right: 10px;
                                          ">

                                        <i class="fas fa-lock"
                                           style="
                                                color: #888;
                                                font-size: 18px;
                                           "></i>

                                    </span>

                                </div>

                                <input type="password"
                                       id="password"
                                       class="form-control border-0"
                                       placeholder="Kata Sandi"
                                       style="
                                            height: 52px;
                                            font-size: 16px;
                                            font-weight: 600;
                                            letter-spacing: 1px;
                                       ">


                                <div class="input-group-append">

                                    <button type="button"
                                            id="togglePassword"
                                            class="btn border-0"
                                            style="
                                                background: #ffffff;
                                                border-radius: 0 18px 18px 0;
                                                width: 52px;
                                            ">

                                        <i class="fas fa-eye"
                                           id="passwordIcon"
                                           style="color: #888;"></i>

                                    </button>

                                </div>

                            </div>

                        </div>


                        <!-- Lupa Password -->
                        <div class="mb-4">

                            <a href="#"
                               style="
                                    color: #2daae1;
                                    font-size: 14px;
                                    font-weight: 700;
                                    letter-spacing: 1px;
                                    text-decoration: none;
                               ">

                                Lupa Password?

                            </a>

                        </div>


                        <!-- Tombol Masuk -->
                        <button type="button"
                                class="btn btn-block text-white py-3"
                                style="
                                    border: none;
                                    border-radius: 18px;
                                    font-size: 18px;
                                    font-weight: 700;
                                    background: linear-gradient(
                                        90deg,
                                        #5bad4c,
                                        #83c83f,
                                        #58ad52
                                    );
                                ">

                            Masuk

                        </button>

                    </form>

                </div>

            </div>

        </div>



        <!-- ===================================== -->
        <!-- FOOTER -->
        <!-- ===================================== -->
        <div class="text-center text-white d-flex justify-content-center align-items-center"
             style="
                height: 42px;
                background: rgba(33, 171, 226, .55);
                font-size: 13px;
             ">

            © 2026 Badan Pusat Statistik

        </div>

    </div>



    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>


    <script>
        $('#togglePassword').click(function () {

            const password = $('#password');
            const icon = $('#passwordIcon');

            if (password.attr('type') === 'password') {

                password.attr('type', 'text');

                icon.removeClass('fa-eye')
                    .addClass('fa-eye-slash');

            } else {

                password.attr('type', 'password');

                icon.removeClass('fa-eye-slash')
                    .addClass('fa-eye');

            }

        });
    </script>

</body>

</html>