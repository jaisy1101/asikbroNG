@extends('layouts.app')

@section('content')

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">

    <ol class="breadcrumb bg-white shadow-sm">

        <li class="breadcrumb-item">
            Lapangan Usaha
        </li>

        <li class="breadcrumb-item active" aria-current="page">
            Unggah Tabel
        </li>

    </ol>

</nav>

{{--  //Filter Digunakan nanti saat input data per cell
<!-- Filter Section 1 -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="d-flex flex-wrap align-items-center justify-content-between">

            <div class="d-flex flex-wrap align-items-center">

                <!-- Toggle ADHB / ADHK -->
                <div class="btn-group mr-3 mb-2" role="group">

                    <button type="button"
                            class="btn btn-primary">
                        ADHB
                    </button>

                    <button type="button"
                            class="btn btn-outline-primary">
                        ADHK
                    </button>

                </div>

                <!-- Dropdown Wilayah -->
                <div class="mr-3 mb-2">

                    <select class="form-control">

                        <option selected disabled>
                            Pilih Wilayah
                        </option>

                        <option>
                            Sulawesi Selatan
                        </option>

                        <option>
                            Kota Makassar
                        </option>

                        <option>
                            Kabupaten Gowa
                        </option>

                    </select>

                </div>

            </div>

            <!-- Tombol Unduh -->
            <div class="mb-2">

                <button class="btn btn-success">

                    <i class="fas fa-download mr-2"></i>

                    Unduh Tabel

                </button>

            </div>

        </div>

    </div>

</div>

<!-- Filter Section 2 -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="d-flex flex-wrap">

            <button class="btn btn-primary mr-2 mb-2">
                Distribusi
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                Indeks Implisit
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                Laju Implisit
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                YtoY
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                QtoQ
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                CtoC
            </button>

        </div>

    </div>

</div>
--}}

<!-- Upload Box -->
<div class="card shadow">

    <div class="card-body">

        <div class="border border-secondary rounded d-flex flex-column justify-content-center align-items-center"
             style="
                border-style: dashed !important;
                min-height: 350px;
             ">

            <!-- Upload Icon -->
            <div class="mb-4">

                <i class="fas fa-cloud-upload-alt"
                   style="
                        font-size: 70px;
                        color: #4e73df;
                   ">
                </i>

            </div>

            <!-- Text -->
            <h5 class="font-weight-bold text-gray-700">

                Cari file atau seret di sini

            </h5>

            <!-- Button -->
            <input type="hidden"
                id="modul_id"
                value="1">
            <input type="file"
                id="fileExcel"
                hidden
                accept=".xlsx,.xls">


            <button id="btnUpload"
                    class="btn btn-primary mt-4">

                <i class="fas fa-folder-open mr-2"></i>

                Telusuri File

            </button>

        </div>

    </div>

</div>

@endsection

@section('scripts')

<script>

let fileInput = document.getElementById('fileExcel');
let btnUpload = document.getElementById('btnUpload');


// buka explorer
btnUpload.addEventListener('click', function(){

    fileInput.click();

});


// ketika file dipilih
fileInput.addEventListener('change', function(){

    let file = this.files[0];


    if(file){


        Swal.fire({

            title: 'Konfirmasi Upload',

            html: `

                File yang dipilih:

                <br>

                <b>${file.name}</b>

                <br><br>

                Ukuran:
                ${(file.size / 1024 / 1024).toFixed(2)} MB

            `,

            icon: 'question',

            showCancelButton: true,

            confirmButtonText: 'Upload',

            cancelButtonText: 'Batal'


        })
        .then((result)=>{


            if(result.isConfirmed){

                uploadFile(file);

            }
            else{

                fileInput.value = "";

            }


        });


    }

});



function uploadFile(file){


    let formData = new FormData();


    formData.append('file', file);


    formData.append(
        'modul_id',
        document.getElementById('modul_id').value
    );



    Swal.fire({

        title: 'Mengunggah file...',

        html: `
            <div id="progress-text">
                0%
            </div>

            <div class="progress mt-3">

                <div id="upload-progress"
                    class="progress-bar"
                    role="progressbar"
                    style="width:0%">
                </div>

            </div>
        `,

        allowOutsideClick: false,

        showConfirmButton: false

    });



    axios.post('/api/submission/upload', formData, {


        headers: {

            'Content-Type': 'multipart/form-data'

        },


        onUploadProgress: function(progressEvent){


            let percent = Math.round(
                (progressEvent.loaded * 100) / progressEvent.total
            );


            let mbLoaded = (
                progressEvent.loaded / 1024 / 1024
            ).toFixed(2);


            let mbTotal = (
                progressEvent.total / 1024 / 1024
            ).toFixed(2);



            document.getElementById('upload-progress')
            .style.width = percent + '%';



            document.getElementById('progress-text')
            .innerHTML = `

                ${percent}% 

                <br>

                ${mbLoaded} MB / ${mbTotal} MB

            `;


        }


    })

    .then(response => {


        Swal.close();


        notifSukses(
            response.data.message 
            ?? 'Upload berhasil'
        );


    })


    .catch(error => {


        Swal.close();


        console.error(error);


        notifError(
            error.response?.data?.message
            ?? 'Upload gagal'
        );


    });


}


</script>

@endsection