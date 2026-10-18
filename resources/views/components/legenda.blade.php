{{--
    Legenda tertempel, dipakai ulang di Entri Tabel dan Hasil Konserda.

    <x-legenda :items="[
        ['penanda' => 'garis', 'warna' => '#4e73df', 'label' => 'Sudah diubah di putaran ini'],
        ['penanda' => 'ikon',  'ikon' => 'fas fa-exclamation-triangle text-warning', 'label' => '...'],
        ['penanda' => 'blok',  'warna' => '#fff3cd', 'label' => '...'],
    ]" />
--}}

@props(['items' => []])

<div {{ $attributes->merge(['class' => 'legenda d-flex flex-wrap align-items-center']) }}>

    @foreach($items as $item)

        <span class="legenda-item mr-4 mb-1">

            @if(($item['penanda'] ?? 'garis') === 'ikon')

                <i class="{{ $item['ikon'] }} mr-1"></i>

            @else

                <span class="legenda-{{ $item['penanda'] ?? 'garis' }}"
                      style="--legenda-warna: {{ $item['warna'] }};">
                </span>

            @endif

            {{ $item['label'] }}

        </span>

    @endforeach

</div>
