{{--
    Hasil Konserda selalu dari putaran terakhir yang sudah ditutup
    (Konserda dihitung saat putaran ditutup). Bila ada putaran yang
    berlangsung, diberi penanda bahwa yang tampil adalah hasil sebelumnya.

    Pemakaian: putaranId = tampilkanInfoKonserda(responseStatus);
    Membutuhkan elemen <div id="info-konserda"></div>.
--}}

<script>

function tampilkanInfoKonserda(status){

    let wadah = document.getElementById('info-konserda');
    let putaran = status.putaran_konserda;

    if(!putaran){

        wadah.innerHTML = `
            <div class="alert alert-info shadow-sm">
                <i class="fas fa-info-circle mr-2"></i>
                Belum ada hasil Konserda. Hasil tersedia setelah putaran pertama ditutup.
            </div>
        `;

        return null;

    }

    let rekon = putaran.rekonsiliasi
        ? `Rekonsiliasi ${putaran.rekonsiliasi.tahun} Q${putaran.rekonsiliasi.triwulan}`
        : '';

    let tanggal = putaran.tanggal_selesai
        ? ', ditutup ' + formatWaktuWita(putaran.tanggal_selesai)
        : '';

    let berjalan = status.putaran_aktif;

    if(putaran.status_konserda === 'proses' || putaran.status_konserda === 'gagal'){

        wadah.innerHTML = putaran.status_konserda === 'proses'
            ? `
                <div class="alert alert-info shadow-sm">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Hasil Konserda <b>Putaran ${putaran.nomor}</b> (${rekon}${tanggal}) sedang dihitung.
                    Muat ulang halaman ini beberapa saat lagi.
                </div>
            `
            : `
                <div class="alert alert-danger shadow-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Hasil Konserda <b>Putaran ${putaran.nomor}</b> gagal dihitung. Silakan hubungi admin.
                </div>
            `;

        return null;

    }

    wadah.innerHTML = berjalan
        ? `
            <div class="alert alert-warning shadow-sm">
                <i class="fas fa-history mr-2"></i>
                Ini hasil Konserda <b>Putaran ${putaran.nomor}</b> (${rekon}${tanggal}).
                <b>Putaran ${berjalan.nomor}</b> sedang berlangsung; hasilnya tersedia setelah putaran ditutup.
            </div>
        `
        : `
            <div class="alert alert-info shadow-sm">
                <i class="fas fa-info-circle mr-2"></i>
                Hasil Konserda <b>Putaran ${putaran.nomor}</b> (${rekon}${tanggal}).
            </div>
        `;

    return putaran.id;

}

</script>
