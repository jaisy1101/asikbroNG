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
                <span class="tanggal-desktop" id="tanggal-desktop">
                </span>

                <span class="tanggal-mobile" id="tanggal-mobile">
                </span>


                <span class="mx-2 pemisah">|</span>


                <span class="jam">

                    <i class="far fa-clock mr-1"></i>

                    <span id="jam">
                    </span>

                </span>

            </div>



            <!-- ===================================== -->
            <!-- TENGAH -->
            <!-- ===================================== -->
            <div class="topbar-center">

                <span class="badge badge-success putaran-badge"
                    id="putaran-header">

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

                    BPS {{ auth()->user()->wilayah->nama }}

                </span>



                <!-- ===================================== -->
                <!-- PENGATURAN -->
                <!-- ===================================== 

                <a href="#"
                   class="mr-4 text-info topbar-icon topbar-setting"
                   data-toggle="modal"
                   data-target="#pengaturanModal">

                    <i class="fas fa-cog"></i>

                </a>
                -->



                <!-- ===================================== -->
                <!-- LOGOUT -->
                <!-- ===================================== -->

                <a href="javascript:void(0)"
                class="text-info topbar-icon"
                data-toggle="modal"
                data-target="#logoutModal">

                    <i class="fas fa-sign-out-alt"></i>

                </a>
            </div>

        </div>

    </div>

</nav>