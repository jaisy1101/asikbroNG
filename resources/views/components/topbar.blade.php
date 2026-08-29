<style>
    .asikbro-topbar {
        min-height: 64px;
    }

    .topbar-content {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        width: 100%;
        gap: 15px;
    }

    .topbar-left {
        white-space: nowrap;
        font-size: 14px;
    }

    .topbar-center {
        display: flex;
        justify-content: center;
        white-space: nowrap;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        white-space: nowrap;
    }

    .putaran-badge {
        font-size: 13px;
        border-radius: 20px;
        letter-spacing: .5px;
        padding: 8px 20px;
    }

    .topbar-icon {
        font-size: 20px;
    }

    .tanggal-mobile {
        display: none;
    }

    /* Layar mulai mengecil */
    @media (max-width: 1200px) {

        .tanggal-desktop {
            display: none;
        }

        .tanggal-mobile {
            display: inline;
        }

        .putaran-badge {
            font-size: 12px;
            padding: 7px 15px;
        }

        .topbar-user .halo {
            display: none;
        }
    }

    /* Tablet */
    @media (max-width: 900px) {

        .topbar-content {
            grid-template-columns: auto 1fr auto;
            gap: 10px;
        }

        .topbar-left .pemisah {
            display: none;
        }

        .topbar-left .jam {
            display: none;
        }

        .topbar-user {
            display: none;
        }

        .putaran-badge {
            font-size: 11px;
            padding: 7px 12px;
        }
    }

    /* Mobile */
    @media (max-width: 600px) {

        .topbar-left {
            display: none;
        }

        .topbar-content {
            grid-template-columns: 1fr auto;
        }

        .topbar-center {
            justify-content: flex-start;
        }

        .putaran-badge {
            font-size: 10px;
            padding: 6px 10px;
            letter-spacing: 0;
        }

        .topbar-icon {
            font-size: 18px;
        }

        .topbar-setting {
            margin-right: 15px !important;
        }
    }
</style>


<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow asikbro-topbar">

    <!-- Sidebar Toggle Mobile -->
    <button id="sidebarToggleTop"
            class="btn btn-link d-md-none rounded-circle mr-2">

        <i class="fa fa-bars"></i>

    </button>


    <div class="container-fluid px-3">

        <div class="topbar-content">


            <!-- ===================================== -->
            <!-- KIRI -->
            <!-- ===================================== -->
            <div class="topbar-left text-gray-700 font-weight-bold">

                <i class="far fa-calendar-alt mr-1"></i>

                <!-- Desktop -->
                <span class="tanggal-desktop">
                    Senin, 5 Desember 2026
                </span>

                <!-- Saat layar mengecil -->
                <span class="tanggal-mobile">
                    05/12/2026
                </span>


                <span class="mx-2 pemisah">|</span>


                <span class="jam">

                    <i class="far fa-clock mr-1"></i>

                    13:19:50

                </span>

            </div>



            <!-- ===================================== -->
            <!-- TENGAH -->
            <!-- ===================================== -->
            <div class="topbar-center">

                <span class="badge badge-success putaran-badge">

                    PUTARAN 1 • REKON 2026 Q2

                </span>

            </div>



            <!-- ===================================== -->
            <!-- KANAN -->
            <!-- ===================================== -->
            <div class="topbar-right">


                <!-- User -->
                <span class="topbar-user mr-4 font-weight-bold text-gray-700">

                    <span class="halo">
                        Halo,
                    </span>

                    BPS Gowa

                </span>



                <!-- ===================================== -->
                <!-- PENGATURAN -->
                <!-- ===================================== -->

                <a href="#"
                   class="mr-4 text-info topbar-icon topbar-setting"
                   data-toggle="modal"
                   data-target="#pengaturanModal">

                    <i class="fas fa-cog"></i>

                </a>



                <!-- ===================================== -->
                <!-- LOGOUT -->
                <!-- ===================================== -->

                <a href="#"
                   class="text-info topbar-icon"
                   data-toggle="modal"
                   data-target="#logoutModal">

                    <i class="fas fa-sign-out-alt"></i>

                </a>


            </div>

        </div>

    </div>

</nav>


<!-- ================================================= -->
<!-- MODAL PENGATURAN -->
<!-- ================================================= -->
<div class="modal fade"
     id="pengaturanModal"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered"
         role="document"
         style="max-width: 400px;">

        <div class="modal-content border-0 shadow"
             style="
                border-radius: 20px;
                overflow: hidden;
             ">

            <!-- Header -->
            <div class="modal-header align-items-center px-4 py-3">

                <div class="d-flex align-items-center">

                    <i class="fas fa-cog text-info mr-3"
                       style="font-size: 25px;"></i>

                    <h5 class="modal-title font-weight-bold text-gray-800 mb-0">
                        Pengaturan
                    </h5>

                </div>

                <button type="button"
                        class="close"
                        data-dismiss="modal">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- Isi -->
            <div class="modal-body p-0">

                <a href="#"
                   class="d-flex align-items-center px-4 py-3 text-decoration-none border-bottom">

                    <i class="fas fa-lock text-info mr-3"
                       style="font-size: 20px; width: 25px;"></i>

                    <span class="text-gray-800"
                          style="font-size: 15px;">
                        Ubah Kata Sandi
                    </span>

                </a>

                <a href="#"
                   class="d-flex align-items-center px-4 py-3 text-decoration-none">

                    <i class="fas fa-phone-alt text-info mr-3"
                       style="font-size: 20px; width: 25px;"></i>

                    <span class="text-gray-800"
                          style="font-size: 15px;">
                        Kontak Admin
                    </span>

                </a>

            </div>

        </div>

    </div>

</div>

<!-- ================================================= -->
<!-- MODAL LOGOUT -->
<!-- ================================================= -->
<div class="modal fade"
     id="logoutModal"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered"
         role="document"
         style="max-width: 400px;">

        <div class="modal-content border-0 shadow"
             style="
                border-radius: 20px;
                overflow: hidden;
             ">

            <!-- Header -->
            <div class="modal-header align-items-center px-4 py-3">

                <div class="d-flex align-items-center">

                    <i class="fas fa-sign-out-alt text-info mr-3"
                       style="font-size: 24px;"></i>

                    <h5 class="modal-title font-weight-bold text-gray-800 mb-0">
                        Keluar
                    </h5>

                </div>

                <button type="button"
                        class="close"
                        data-dismiss="modal">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- Isi -->
            <div class="modal-body text-center"
                 style="
                    padding: 28px 32px 30px 32px;
                 ">

                <!-- Icon -->
                <div class="d-flex justify-content-center mb-4">

                    <div class="d-flex align-items-center justify-content-center"
                         style="
                            width: 72px;
                            height: 72px;
                            border-radius: 50%;
                            border: 3px solid #36b9cc;
                            color: #36b9cc;
                            font-size: 34px;
                            font-weight: 600;
                         ">

                        ?

                    </div>

                </div>

                <!-- Pertanyaan -->
                <h5 class="font-weight-bold text-gray-700 mb-4"
                    style="
                        font-size: 18px;
                        line-height: 1.5;
                    ">

                    Apakah Anda yakin ingin keluar?

                </h5>

                <!-- Tombol -->
                <div class="d-flex justify-content-center">

                    <button type="button"
                            class="btn btn-light mr-2"
                            data-dismiss="modal"
                            style="
                                border-radius: 10px;
                                min-width: 95px;
                                height: 42px;
                                font-size: 15px;
                            ">

                        Tidak

                    </button>

                    <a href="/logout"
                       class="btn btn-info d-flex align-items-center justify-content-center"
                       style="
                            border-radius: 10px;
                            min-width: 95px;
                            height: 42px;
                            font-size: 15px;
                       ">

                        Ya

                    </a>

                </div>

            </div>

        </div>

    </div>

</div>