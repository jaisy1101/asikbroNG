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
                                border-radius:10px;
                                min-width:95px;
                                height:42px;
                                font-size:15px;
                            ">

                        Tidak

                    </button>


                    <button type="button"
                            id="btnLogout"
                            class="btn btn-info"
                            style="
                                border-radius:10px;
                                min-width:95px;
                                height:42px;
                                font-size:15px;
                            ">

                        Ya

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

$('#btnLogout').click(function(){

    $.ajax({

        url:'/logout',

        type:'POST',

        data:{
            _token:'{{ csrf_token() }}'
        },

        success:function(){

            window.location.href='/login';

        }

    });

});

</script>
