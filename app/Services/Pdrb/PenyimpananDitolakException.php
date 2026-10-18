<?php

namespace App\Services\Pdrb;

/*
| Penyimpanan source ditolak karena melanggar aturan putaran
| atau aturan triwulan yang berlaku.
*/

class PenyimpananDitolakException extends \RuntimeException
{
}
