@extends('layouts.app')

{{--
    Entri Tabel (Lapangan Usaha / Pengeluaran)
    Variabel route: $modulId (1 = LU, 2 = Pengeluaran), $namaModul
    Hanya dapat dibuka saat ada putaran berlangsung (middleware putaran.berlangsung).
--}}

@php
    // Kabkota: kode BPS 4 digit (7301); provinsi: 73 -> 7300.
    $kodeWilayah = (string) auth()->user()?->wilayah?->kodeBerkas();
    $contohBerkas = $kodeWilayah . '_' . str_replace(' ', '_', $namaModul) . '.xlsx';
@endphp

@section('content')

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">

    <ol class="breadcrumb bg-white shadow-sm">

        <li class="breadcrumb-item">
            {{ $namaModul }}
        </li>

        <li class="breadcrumb-item active" aria-current="page">
            Entri Tabel
        </li>

    </ol>

</nav>


<!-- Pilihan cara input (info putaran sudah ada di topbar) -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="btn-group" role="group" id="toggle-mode">

            <button type="button" class="btn btn-outline-primary" data-mode="unggah">
                <i class="fas fa-file-upload mr-1"></i>
                Unggah Berkas
            </button>

            <button type="button" class="btn btn-outline-primary" data-mode="isi">
                <i class="fas fa-edit mr-1"></i>
                Isi Tabel
            </button>

        </div>

    </div>

</div>


<!-- =====================================================
     UNGGAH BERKAS
===================================================== -->
<div id="mode-unggah" class="card shadow d-none">

    <div class="card-body">

        <div class="border border-secondary rounded d-flex flex-column justify-content-center align-items-center"
             style="
                border-style: dashed !important;
                min-height: 350px;
             ">

            <div class="mb-4">

                <i class="fas fa-cloud-upload-alt"
                   style="
                        font-size: 70px;
                        color: #4e73df;
                   ">
                </i>

            </div>

            <h5 class="font-weight-bold text-gray-700">

                Cari file atau seret di sini

            </h5>

            <div class="alert alert-info mt-3 mb-0 small text-center">
                <i class="fas fa-info-circle mr-1"></i>
                Nama berkas harus mengandung kode wilayah Anda (<b>{{ $kodeWilayah }}</b>),
                contoh: <code>{{ $contohBerkas }}</code>
            </div>

            <input type="file"
                id="fileExcel"
                hidden
                accept=".xlsx,.xls">


            <div class="mt-4">

                <button id="btnUpload"
                        class="btn btn-primary m-1">

                    <i class="fas fa-folder-open mr-2"></i>

                    Telusuri File

                </button>

                <button id="btnTemplate"
                        class="btn btn-outline-success m-1"
                        title="Berisi nilai terkini wilayah Anda untuk periode yang terbuka">

                    <i class="fas fa-file-excel mr-2"></i>

                    Unduh Template

                </button>

            </div>

            <div class="small text-gray-600 mt-2 text-center">
                Template berisi nilai terkini untuk periode yang terbuka. Ubah nilai yang perlu diperbaiki, lalu unggah kembali.
            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     ISI TABEL
===================================================== -->
<div id="mode-isi" class="card shadow d-none">

    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

            <div class="btn-group mb-2" role="group" id="toggle-jenis">

                <button type="button" class="btn btn-primary" data-jenis="1">
                    ADHB <span class="badge badge-warning badge-draf d-none"></span>
                </button>

                <button type="button" class="btn btn-outline-primary" data-jenis="2">
                    ADHK <span class="badge badge-warning badge-draf d-none"></span>
                </button>

            </div>

            <x-legenda class="mb-2" :items="[
                ['penanda' => 'garis', 'warna' => '#4e73df', 'label' => 'Sudah diubah di putaran ini'],
                ['penanda' => 'garis', 'warna' => '#f6c23e', 'label' => 'Belum disimpan'],
                ['penanda' => 'ikon', 'ikon' => 'fas fa-exclamation-triangle text-warning', 'label' => 'Nilai induk tidak sama dengan sub-kategori'],
            ]" />

        </div>

        <div id="pesan-isi" class="text-center text-gray-600 py-5">
            Memuat tabel...
        </div>

        <div class="table-responsive d-none" id="wadah-tabel">

            <table class="table table-bordered table-data-large tabel-isian mb-0">

                <thead class="thead-light" id="header-isian"></thead>

                <tbody id="body-isian"></tbody>

            </table>

        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">

            <div id="status-draf" class="text-gray-600 small mb-2"></div>

            <button id="btnSimpan" class="btn btn-success mb-2" disabled>
                <i class="fas fa-save mr-2"></i>
                Simpan
            </button>

        </div>

    </div>

</div>

@endsection


@section('scripts')

<script>

const MODUL_ID = {{ $modulId }};

const KODE_WILAYAH = @json($kodeWilayah);

const JENIS = [1, 2];

let dataIsian = null;            // respons GET /api/entri-tabel
let jenisAktif = 1;
let draf = { 1: {}, 2: {} };     // "periode:kategori" -> teks yang diketik



/* =====================================================
   UTILITAS
===================================================== */

function kunci(periodeId, kategoriId){
    return periodeId + ':' + kategoriId;
}

function escapeHtml(teks){
    return String(teks ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

/*
| "1.033.386,54" (format Indonesia) atau "1033386.54" (salinan Excel).
| Kosong -> null, tidak valid -> NaN.
*/
function parseAngka(teks){

    let s = String(teks ?? '').trim().replace(/\s/g, '');

    if(s === ''){
        return null;
    }

    if(s.includes(',')){
        s = s.replace(/\./g, '').replace(',', '.');
    }
    else if((s.match(/\./g) || []).length > 1){
        s = s.replace(/\./g, '');
    }

    return /^-?\d*\.?\d+$/.test(s) ? Number(s) : NaN;

}

function formatAngka(nilai){

    if(nilai === null || nilai === undefined || Number.isNaN(nilai)){
        return '';
    }

    return Number(nilai).toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

}

// Untuk diedit: tanpa pemisah ribuan, koma desimal, presisi penuh.
function angkaMentah(nilai){
    return nilai === null ? '' : String(nilai).replace('.', ',');
}

function nilaiTersimpan(jenis, k){
    return dataIsian.nilai[jenis][k]?.nilai ?? null;
}

function nilaiSaatIni(jenis, k){
    return (k in draf[jenis]) ? parseAngka(draf[jenis][k]) : nilaiTersimpan(jenis, k);
}

function jumlahDraf(jenis){
    return Object.keys(draf[jenis]).length;
}

function sama(a, b){
    if(a === null || b === null){
        return a === b;
    }
    return Math.abs(a - b) <= 0.0000011;
}



/* =====================================================
   MUAT DATA
===================================================== */

function muatData(){

    return axios.get(`/api/entri-tabel/${MODUL_ID}`)

    .then(response => {

        dataIsian = response.data;

        renderTabel();

    })

    .catch(error => {

        let pesan = error.response?.data?.message ?? 'Tabel gagal dimuat.';

        document.getElementById('pesan-isi').innerText = pesan;
        document.getElementById('pesan-isi').classList.remove('d-none');
        document.getElementById('wadah-tabel').classList.add('d-none');

    });

}



/* =====================================================
   RENDER TABEL
===================================================== */

function renderTabel(){

    let header = `<tr><th class="kolom-kategori">Kategori</th>`;

    dataIsian.periode.forEach(p => {
        header += `<th>${p.label}</th>`;
    });

    document.getElementById('header-isian').innerHTML = header + `</tr>`;


    let html = '';

    dataIsian.kategori.forEach(k => {

        html += `
            <tr class="level-${k.level}" data-kategori="${k.id}">
                <td class="kolom-kategori"
                    style="padding-left:${(k.level - 1) * 20 + 12}px;"
                    title="${escapeHtml((k.kode ?? '') + ' ' + k.nama)}">
                    ${escapeHtml(k.kode ?? '')} ${escapeHtml(k.nama)}
                    <i class="fas fa-exclamation-triangle text-warning ml-1 ikon-induk d-none"></i>
                </td>
        `;

        dataIsian.periode.forEach(p => {
            html += `
                <td class="sel-isian" data-periode="${p.id}" data-kategori="${k.id}">
                    <input type="text" inputmode="decimal" autocomplete="off">
                </td>
            `;
        });

        html += `</tr>`;

    });

    document.getElementById('body-isian').innerHTML = html;

    // Jangan forEach(perbaruiSel): indeks forEach terbaca sebagai sedangDiedit.
    document.querySelectorAll('#body-isian td.sel-isian').forEach(td => perbaruiSel(td));

    document.getElementById('pesan-isi').classList.add('d-none');
    document.getElementById('wadah-tabel').classList.remove('d-none');

    cekSemuaInduk();
    perbaruiStatus();

}


function perbaruiSel(td, sedangDiedit = false){

    let k = kunci(td.dataset.periode, td.dataset.kategori);
    let input = td.querySelector('input');
    let tersimpan = dataIsian.nilai[jenisAktif][k];
    let adaDraf = k in draf[jenisAktif];
    let nilai = nilaiSaatIni(jenisAktif, k);

    if(sedangDiedit !== true){
        input.value = adaDraf && Number.isNaN(nilai)
            ? draf[jenisAktif][k]
            : formatAngka(nilai);
    }

    td.classList.toggle('sel-draf', adaDraf);
    td.classList.toggle('sel-diubah', !adaDraf && !!tersimpan?.diubah);
    input.classList.toggle('text-danger', adaDraf && Number.isNaN(nilai));

    if(adaDraf){
        td.title = 'Belum disimpan · sebelumnya: '
            + (tersimpan ? formatAngka(tersimpan.nilai) : 'kosong');
    }
    else if(tersimpan?.diubah){
        td.title = 'Diubah' + (tersimpan.oleh ? ' oleh ' + tersimpan.oleh : '')
            + (tersimpan.waktu ? ' · ' + formatWaktuWita(tersimpan.waktu) : '');
    }
    else{
        td.title = '';
    }

}



/* =====================================================
   PENGECEKAN INDUK (hanya peringatan)
===================================================== */

function cekInduk(jenis){

    let hasil = [];

    dataIsian.kategori.forEach(k => {

        let aturan = k.aturan_induk;

        if(!aturan){
            return;
        }

        dataIsian.periode.forEach(p => {

            let induk = nilaiSaatIni(jenis, kunci(p.id, k.id));
            let anak = aturan.anak.map(id => nilaiSaatIni(jenis, kunci(p.id, id)));

            if(induk === null && anak.every(v => v === null)){
                return;
            }

            if(Number.isNaN(induk) || anak.some(v => Number.isNaN(v))){
                return;
            }

            let nilaiAnak = anak.map(v => v ?? 0);

            let harapan = aturan.rumus === 'selisih'
                ? nilaiAnak[0] - nilaiAnak[1]
                : nilaiAnak.reduce((a, b) => a + b, 0);

            if(Math.abs((induk ?? 0) - harapan) > 0.01){
                hasil.push({ kategori: k, periode: p, induk: induk ?? 0, harapan: harapan });
            }

        });

    });

    return hasil;

}


function cekSemuaInduk(){

    let perKategori = {};

    cekInduk(jenisAktif).forEach(w => {
        (perKategori[w.kategori.id] ??= []).push(w);
    });

    document.querySelectorAll('#body-isian tr').forEach(tr => {

        let ikon = tr.querySelector('.ikon-induk');
        let daftar = perKategori[tr.dataset.kategori];

        ikon.classList.toggle('d-none', !daftar);

        if(daftar){

            let rumus = daftar[0].kategori.aturan_induk.rumus === 'selisih'
                ? 'sub-kategori 1 - sub-kategori 2'
                : 'jumlah sub-kategori';

            ikon.title = daftar.map(w =>
                `${w.periode.label}: induk ${formatAngka(w.induk)}, ${rumus} ${formatAngka(w.harapan)}`
            ).join('\n');

        }

    });

}



/* =====================================================
   STATUS DRAF
===================================================== */

function perbaruiStatus(){

    let total = 0;

    JENIS.forEach(jenis => {

        let n = jumlahDraf(jenis);
        total += n;

        let badge = document.querySelector(`#toggle-jenis [data-jenis="${jenis}"] .badge-draf`);
        badge.textContent = n;
        badge.classList.toggle('d-none', n === 0);

    });

    document.getElementById('status-draf').innerText = total
        ? `${total} sel belum disimpan`
        : 'Tidak ada perubahan yang belum disimpan';

    document.getElementById('btnSimpan').disabled = !dataIsian;

}



/* =====================================================
   INTERAKSI SEL
===================================================== */

let tbody = document.getElementById('body-isian');

tbody.addEventListener('focusin', e => {

    let td = e.target.closest('td.sel-isian');

    if(!td){
        return;
    }

    let k = kunci(td.dataset.periode, td.dataset.kategori);

    e.target.value = (k in draf[jenisAktif])
        ? draf[jenisAktif][k]
        : angkaMentah(nilaiTersimpan(jenisAktif, k));

    e.target.select();

});

tbody.addEventListener('input', e => {

    let td = e.target.closest('td.sel-isian');

    if(!td){
        return;
    }

    let k = kunci(td.dataset.periode, td.dataset.kategori);
    let nilai = parseAngka(e.target.value);

    if(!Number.isNaN(nilai) && sama(nilai, nilaiTersimpan(jenisAktif, k))){
        delete draf[jenisAktif][k];
    }
    else{
        draf[jenisAktif][k] = e.target.value;
    }

    perbaruiSel(td, true);
    cekSemuaInduk();
    perbaruiStatus();

});

tbody.addEventListener('focusout', e => {

    let td = e.target.closest('td.sel-isian');

    if(td){
        perbaruiSel(td);
    }

});

// Enter = pindah ke sel di bawahnya, seperti di Excel.
tbody.addEventListener('keydown', e => {

    if(e.key !== 'Enter'){
        return;
    }

    let td = e.target.closest('td.sel-isian');
    let trBerikut = td?.parentElement.nextElementSibling;

    if(trBerikut){
        e.preventDefault();
        trBerikut.children[td.cellIndex]?.querySelector('input')?.focus();
    }

});


document.querySelectorAll('#toggle-jenis button').forEach(btn => {

    btn.addEventListener('click', () => {

        jenisAktif = Number(btn.dataset.jenis);

        document.querySelectorAll('#toggle-jenis button').forEach(b => {
            b.classList.toggle('btn-primary', b === btn);
            b.classList.toggle('btn-outline-primary', b !== btn);
        });

        if(dataIsian){
            renderTabel();
        }

    });

});



/* =====================================================
   SIMPAN
===================================================== */

document.getElementById('btnSimpan').addEventListener('click', () => {

    let tidakValid = JENIS.some(jenis =>
        Object.values(draf[jenis]).some(teks => Number.isNaN(parseAngka(teks)))
    );

    if(tidakValid){
        notifError('Ada sel berisi angka yang tidak valid (ditandai merah). Perbaiki dulu sebelum menyimpan.');
        return;
    }

    let payload = {};
    let kosong = 0;

    JENIS.forEach(jenis => {

        payload[jenis] = [];

        dataIsian.periode.forEach(p => {
            dataIsian.kategori.forEach(k => {

                let nilai = nilaiSaatIni(jenis, kunci(p.id, k.id));

                if(nilai === null){
                    kosong++;
                }

                payload[jenis].push({ periode_id: p.id, kategori_id: k.id, nilai: nilai });

            });
        });

    });

    let berubah = jumlahDraf(1) + jumlahDraf(2);
    let peringatan = cekInduk(1).length + cekInduk(2).length;

    let rincian = berubah
        ? `<b>${berubah} sel</b> akan diubah (ADHB ${jumlahDraf(1)}, ADHK ${jumlahDraf(2)}).`
        : 'Tidak ada nilai yang diubah. Simpan sebagai konfirmasi bahwa nilai sudah sesuai?';

    if(kosong){
        rincian += `<br><br><i class="fas fa-info-circle text-info"></i> ${kosong} sel kosong akan disimpan sebagai <b>0</b>.`;
    }

    if(peringatan){
        rincian += `<br><br><i class="fas fa-exclamation-triangle text-warning"></i> ${peringatan} nilai induk tidak sama dengan sub-kategorinya.`;
    }

    Swal.fire({
        title: 'Simpan Tabel?',
        html: rincian,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal'
    })
    .then(result => {

        if(!result.isConfirmed){
            return;
        }

        Swal.fire({
            title: 'Menyimpan...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        axios.post(`/api/entri-tabel/${MODUL_ID}`, { nilai: payload })

        .then(response => {

            draf = { 1: {}, 2: {} };

            return muatData().then(() => {
                notifCekIntegrasi(`Data tersimpan (${response.data.jumlah_perubahan} sel berubah)`);
            });

        })

        .catch(error => {

            console.error(error);

            notifError(error.response?.data?.message ?? 'Data gagal disimpan');

        });

    });

});


window.addEventListener('beforeunload', e => {

    if(jumlahDraf(1) + jumlahDraf(2) > 0){
        e.preventDefault();
        e.returnValue = '';
    }

});



/* =====================================================
   PILIHAN CARA INPUT
===================================================== */

function pilihMode(mode){

    document.querySelectorAll('#toggle-mode button').forEach(b => {
        b.classList.toggle('btn-primary', b.dataset.mode === mode);
        b.classList.toggle('btn-outline-primary', b.dataset.mode !== mode);
    });

    document.getElementById('mode-unggah').classList.toggle('d-none', mode !== 'unggah');
    document.getElementById('mode-isi').classList.toggle('d-none', mode !== 'isi');

    try {
        localStorage.setItem('entriTabelMode', mode);
    } catch (e) {}

}

document.querySelectorAll('#toggle-mode button').forEach(btn => {
    btn.addEventListener('click', () => pilihMode(btn.dataset.mode));
});

let modeAwal = 'unggah';

try {
    modeAwal = localStorage.getItem('entriTabelMode') ?? 'unggah';
} catch (e) {}

pilihMode(modeAwal === 'isi' ? 'isi' : 'unggah');

muatData();



/* =====================================================
   UNGGAH BERKAS
===================================================== */

let fileInput = document.getElementById('fileExcel');
let btnUpload = document.getElementById('btnUpload');


btnUpload.addEventListener('click', function(){

    fileInput.click();

});


document.getElementById('btnTemplate').addEventListener('click', function(){

    unduhBerkas(`/api/entri-tabel/${MODUL_ID}/template`, 'template.xlsx');

});


fileInput.addEventListener('change', function(){

    let file = this.files[0];

    if(!file){
        return;
    }

    // Diperiksa juga di server.
    if(!KODE_WILAYAH || !file.name.includes(KODE_WILAYAH)){

        notifError(`Nama berkas harus mengandung kode wilayah Anda (${KODE_WILAYAH}). Berkas "${file.name}" tidak diunggah.`);

        fileInput.value = "";

        return;

    }

    let adaDraf = jumlahDraf(1) + jumlahDraf(2) > 0;

    Swal.fire({

        title: 'Konfirmasi Upload',

        html: `
            File yang dipilih:
            <br>
            <b>${escapeHtml(file.name)}</b>
            <br><br>
            Ukuran:
            ${(file.size / 1024 / 1024).toFixed(2)} MB
            ${adaDraf
                ? '<br><br><i class="fas fa-exclamation-triangle text-warning"></i> Perubahan di Isi Tabel yang belum disimpan akan dibuang.'
                : ''}
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

        fileInput.value = "";

    });

});


function uploadFile(file){

    let formData = new FormData();

    formData.append('file', file);

    formData.append('modul_id', MODUL_ID);


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

            let mbLoaded = (progressEvent.loaded / 1024 / 1024).toFixed(2);

            let mbTotal = (progressEvent.total / 1024 / 1024).toFixed(2);

            document.getElementById('upload-progress').style.width = percent + '%';

            document.getElementById('progress-text').innerHTML = `
                ${percent}%
                <br>
                ${mbLoaded} MB / ${mbTotal} MB
            `;

            if(percent === 100){
                document.getElementById('progress-text').innerHTML += '<br>Memproses tabel...';
            }

        }

    })

    .then(response => {

        draf = { 1: {}, 2: {} };

        return muatData().then(() => {
            notifCekIntegrasi(`Berkas berhasil diunggah (${response.data.jumlah_perubahan} sel berubah)`);
        });

    })

    .catch(error => {

        console.error(error);

        notifError(
            error.response?.data?.message
            ?? 'Upload gagal'
        );

    });

}

</script>

@endsection
