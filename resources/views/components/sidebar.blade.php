<ul class="navbar-nav sidebar sidebar-dark accordion sidebar-custom"
    id="accordionSidebar">

    <!-- Brand -->
    <a class="sidebar-brand d-flex flex-column align-items-center justify-content-center"
       href="/"
       style="
            height: 150px;
            padding-top: 20px;
            padding-bottom: 20px;
       ">

        <!-- Logo -->
        <img src="{{ asset('assets/img/bps.png') }}"
             alt="Logo BPS"
             style="
                width: 72px;
                height: auto;
             ">

        <!-- Text -->
        <div class="sidebar-brand-text text-center mt-3"
             style="
                white-space: normal;
                line-height: 1.3;
                font-size: 15px;
                font-weight: 800;
             ">

            BPS PROVINSI <br>
            SULAWESI SELATAN

        </div>

    </a>

    <hr class="sidebar-divider my-0">


    <!-- Beranda -->
    <li class="nav-item {{ request()->is('/') ? 'active' : '' }}">

        <a class="nav-link" href="/">

            <i class="fas fa-home"></i>
            <span>Beranda</span>

        </a>

    </li>


    <!-- Pengeluaran -->
    <li class="nav-item {{ request()->is('pengeluaran/*') ? 'active' : '' }}">

        <a class="nav-link {{ request()->is('pengeluaran/*') ? '' : 'collapsed' }}"
           href="#"
           data-toggle="collapse"
           data-target="#collapsePengeluaran"
           aria-expanded="{{ request()->is('pengeluaran/*') ? 'true' : 'false' }}"
           aria-controls="collapsePengeluaran">

            <i class="fas fa-money-bill-wave"></i>

            <span>Pengeluaran</span>

        </a>

        <div id="collapsePengeluaran"
             class="collapse {{ request()->is('pengeluaran/*') ? 'show' : '' }}"
             data-parent="#accordionSidebar">

            <div class="bg-white py-2 collapse-inner rounded">

                <a class="collapse-item {{ request()->is('pengeluaran/unggah-tabel') ? 'active' : '' }}"
                   href="/pengeluaran/unggah-tabel">

                    Unggah Tabel

                </a>

                <a class="collapse-item {{ request()->is('pengeluaran/daftar-tabel') ? 'active' : '' }}"
                   href="/pengeluaran/daftar-tabel">

                    Daftar Tabel

                </a>

                <a class="collapse-item {{ request()->is('pengeluaran/perubahan-nilai') ? 'active' : '' }}"
                   href="/pengeluaran/perubahan-nilai">

                    Perubahan Nilai

                </a>

                <a class="collapse-item {{ request()->is('pengeluaran/hasil-konserda') ? 'active' : '' }}"
                   href="/pengeluaran/hasil-konserda">

                    Hasil Konserda

                </a>

            </div>

        </div>

    </li>


    <!-- Lapangan Usaha -->
    <li class="nav-item {{ request()->is('lapangan-usaha/*') ? 'active' : '' }}">

        <a class="nav-link {{ request()->is('lapangan-usaha/*') ? '' : 'collapsed' }}"
           href="#"
           data-toggle="collapse"
           data-target="#collapseLapanganUsaha"
           aria-expanded="{{ request()->is('lapangan-usaha/*') ? 'true' : 'false' }}"
           aria-controls="collapseLapanganUsaha">

            <i class="fas fa-briefcase"></i>

            <span>Lapangan Usaha</span>

        </a>

        <div id="collapseLapanganUsaha"
             class="collapse {{ request()->is('lapangan-usaha/*') ? 'show' : '' }}"
             data-parent="#accordionSidebar">

            <div class="bg-white py-2 collapse-inner rounded">

                <a class="collapse-item {{ request()->is('lapangan-usaha/unggah-tabel') ? 'active' : '' }}"
                   href="/lapangan-usaha/unggah-tabel">

                    Unggah Tabel

                </a>

                <a class="collapse-item {{ request()->is('lapangan-usaha/daftar-tabel') ? 'active' : '' }}"
                   href="/lapangan-usaha/daftar-tabel">

                    Daftar Tabel

                </a>

                <a class="collapse-item {{ request()->is('lapangan-usaha/perubahan-nilai') ? 'active' : '' }}"
                   href="/lapangan-usaha/perubahan-nilai">

                    Perubahan Nilai

                </a>

                <a class="collapse-item {{ request()->is('lapangan-usaha/hasil-konserda') ? 'active' : '' }}"
                   href="/lapangan-usaha/hasil-konserda">

                    Hasil Konserda

                </a>

            </div>

        </div>

    </li>


    <!-- Integrasi -->
    <li class="nav-item {{ request()->is('integrasi') ? 'active' : '' }}">

        <a class="nav-link" href="/integrasi">

            <i class="fas fa-link"></i>
            <span>Integrasi</span>

        </a>

    </li>


    <!-- Monitoring -->
    <li class="nav-item {{ request()->is('monitoring') ? 'active' : '' }}">

        <a class="nav-link" href="/monitoring">

            <i class="fas fa-chart-area"></i>
            <span>Monitoring</span>

        </a>

    </li>


    <!-- Operator -->
    <li class="nav-item {{ request()->is('operator') ? 'active' : '' }}">

        <a class="nav-link" href="/operator">

            <i class="fas fa-users-cog"></i>
            <span>Operator</span>

        </a>

    </li>

    <hr class="sidebar-divider d-none d-md-block">

</ul>