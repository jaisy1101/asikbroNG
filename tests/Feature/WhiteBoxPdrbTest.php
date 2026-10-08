<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\RekonsiliasiController;
use App\Jobs\GenerateKonserdaJob;
use App\Models\User;
use App\Services\Derived\DerivedLapanganUsaha;
use App\Services\Derived\DerivedPengeluaran;
use App\Services\Integrasi\IntegrasiPdrbService;
use App\Services\Konserda\KonserdaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Real services against an isolated, minimal SQLite schema, not production migrations. */
class WhiteBoxPdrbTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:',
            'database.connections.sqlite.url'=>null, 'logging.default'=>'null', 'queue.default'=>'sync']);
        DB::purge('sqlite');
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $tables = [
            'periode'=>['tahun','triwulan'],
            'wilayah'=>['nama','jenis','parent_id'],
            'kategori_lapangan_usaha'=>['nama','parent_id'],
            'kategori_pengeluaran'=>['nama','parent_id'],
            'rekonsiliasi'=>['periode_id','status','nama'],
            'rekonsiliasi_periode'=>['rekonsiliasi_id','periode_id'],
            'putaran'=>['rekonsiliasi_id','nomor','status','tanggal_mulai','tanggal_selesai'],
            'data_pdrb_lapangan_usaha'=>['submission_id','wilayah_id','periode_id','jenis_tabel_id','kategori_lapus_id','nilai','tipe_data'],
            'data_pdrb_pengeluaran'=>['submission_id','wilayah_id','periode_id','jenis_tabel_id','kategori_pengeluaran_id','nilai','tipe_data'],
            'integrasi_pdrb'=>['putaran_id','wilayah_id','periode_id','jenis_tabel_id','total_lapus','total_pengeluaran','selisih','status'],
            'hasil_konserda'=>['putaran_id','periode_id','wilayah_parent_id','modul_id','jenis_tabel_id','kategori_id','nilai_provinsi','nilai_agregasi_kabkota','selisih','diskrepansi_persen','batas_toleransi','status'],
            'hasil_konserda_details'=>['hasil_konserda_id','wilayah_id','nilai'],
        ];
        foreach ($tables as $name=>$columns) {
            Schema::create($name, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $c) {
                    if (in_array($c,['nilai','total_lapus','total_pengeluaran','selisih','nilai_provinsi','nilai_agregasi_kabkota','diskrepansi_persen','batas_toleransi'])) $t->double($c)->nullable();
                    elseif (str_ends_with($c,'_id') || in_array($c,['tahun','triwulan','nomor'])) $t->integer($c)->nullable();
                    else $t->string($c)->nullable();
                }
                $t->timestamps();
            });
        }
        $this->insert('wilayah', ['id'=>1,'nama'=>'Provinsi','jenis'=>'provinsi']);
        $this->insert('wilayah', ['id'=>2,'nama'=>'Kab A','jenis'=>'kabupaten','parent_id'=>1]);
        $this->insert('wilayah', ['id'=>3,'nama'=>'Kab B','jenis'=>'kabupaten','parent_id'=>1]);
        foreach (['kategori_lapangan_usaha','kategori_pengeluaran'] as $t) {
            $this->insert($t,['id'=>1,'nama'=>'A']);
            $this->insert($t,['id'=>2,'nama'=>'B']);
            $this->insert($t,['id'=>3,'nama'=>'A1','parent_id'=>1]);
        }
        foreach ([[1,2026,2],[2,2026,1],[3,2025,4],[4,2025,2],[5,2025,1]] as [$id,$year,$q])
            $this->insert('periode',['id'=>$id,'tahun'=>$year,'triwulan'=>$q]);
        $this->insert('rekonsiliasi',['id'=>1,'periode_id'=>1,'status'=>'berlangsung']);
        $this->insert('rekonsiliasi_periode',['rekonsiliasi_id'=>1,'periode_id'=>1]);
        $this->insert('putaran',['id'=>1,'rekonsiliasi_id'=>1,'nomor'=>0,'status'=>'berlangsung']);
    }

    private function insert(string $t, array $data): int { return DB::table($t)->insertGetId($data); }
    private function table(int $m): string { return $m===1 ? 'data_pdrb_lapangan_usaha' : 'data_pdrb_pengeluaran'; }
    private function column(int $m): string { return $m===1 ? 'kategori_lapus_id' : 'kategori_pengeluaran_id'; }
    private function data(int $m, float $value, int $kind=2, int $period=1, int $cat=1, int $region=2, string $type='source', ?int $submission=null): void
    {
        $this->insert($this->table($m),['nilai'=>$value,'jenis_tabel_id'=>$kind,'periode_id'=>$period,
            $this->column($m)=>$cat,'wilayah_id'=>$region,'tipe_data'=>$type,'submission_id'=>$submission]);
    }
    private function derived(int $m, int $kind, int $cat=1, int $period=1): mixed
    {
        return DB::table($this->table($m))->where(['wilayah_id'=>2,'periode_id'=>$period,'jenis_tabel_id'=>$kind,$this->column($m)=>$cat])->value('nilai');
    }
    private function near(float $want, mixed $actual): void { $this->assertNotNull($actual); $this->assertEqualsWithDelta($want,(float)$actual,0.0000006); }

    public static function cases(): iterable
    {
        for ($m=1;$m<=2;$m++) for ($n=1;$n<=25;$n++) yield sprintf('D%02d-modul%d',$n,$m)=>['D',$n,$m];
        for ($n=1;$n<=13;$n++) yield sprintf('I%02d',$n)=>['I',$n,1];
        for ($n=1;$n<=28;$n++) yield sprintf('K%02d',$n)=>['K',$n,1];
    }

    #[DataProvider('cases')]
    public function test_report_case(string $group, int $n, int $m): void
    {
        match ($group) { 'D'=>$this->runDerived($n,$m), 'I'=>$this->runIntegration($n), 'K'=>$this->runKonserda($n) };
    }

    private function runDerived(int $n, int $m): void
    {
        $s=$m===1 ? new DerivedLapanganUsaha : new DerivedPengeluaran;
        if ($n===1 || $n===2) {
            $this->data($m,77,3,1,1,2,'derived');
            if ($n===2) $this->data($m,0,1);
            $s->hitungDistribusi(2,1); $this->near(77,$this->derived($m,3)); return;
        }
        if ($n===3 || $n===4) {
            $this->data($m,$n===3?60:1,1); $this->data($m,$n===3?40:2,1,1,2);
            if ($n===3) $this->data($m,20,1,1,3);
            $s->hitungDistribusi(2,1);
            $this->near($n===3?60:33.333333,$this->derived($m,3));
            $this->near($n===3?40:66.666667,$this->derived($m,3,2));
            if ($n===3) $this->near(20,$this->derived($m,3,3)); return;
        }
        if ($n===5 || $n===6) {
            $now=$n===5?2:1; $prev=$n===5?3:2;
            $this->data($m,$n===5?110:120,2,$now); $this->data($m,100,2,$prev);
            $s->hitungQtQ(2,$now); $this->near($n===5?10:20,$this->derived($m,4,1,$now)); return;
        }
        if ($n>=7 && $n<=11) {
            foreach (['hitungQtQ'=>[2,4,2],'hitungYtY'=>[2,5,4],'hitungImplisitQtQ'=>[7,8,2],'hitungImplisitYtY'=>[7,9,4]] as $method=>[$source,$out,$prev]) {
                DB::table($this->table($m))->delete();
                $this->data($m,77,$out,1,1,2,'derived');
                if ($n===7) DB::table('periode')->where('id',$prev)->delete();
                elseif (!DB::table('periode')->where('id',$prev)->exists()) $this->insert('periode',['id'=>$prev,'tahun'=>$prev===2?2026:2025,'triwulan'=>$prev===2?1:2]);
                if ($n!==8) $this->data($m,$n===11?80:120,$source,1,1,2,$source===7?'derived':'source');
                if ($n===10 || $n===11) $this->data($m,$n===10?0:100,$source,$prev,1,2,$source===7?'derived':'source');
                $s->$method(2,1);
                if ($n===8) $this->assertNull($this->derived($m,$out));
                else $this->near($n===7?77:($n===11?-20:0),$this->derived($m,$out));
            }
            return;
        }
        if ($n===12) {
            $this->data($m,150); $this->data($m,100,2,4); $this->data($m,999,2,2);
            $s->hitungYtY(2,1); $this->near(50,$this->derived($m,5)); return;
        }
        if (in_array($n,[13,14,15,16,17])) {
            if ($n===16) { $s->hitungCtC(2,999); $this->assertSame(0,DB::table($this->table($m))->count()); return; }
            $this->data($m,100,2,2);
            if ($n!==17) $this->data($m,120);
            if ($n!==14) { $this->data($m,$n===15?0:80,2,5); $this->data($m,$n===15?0:100,2,4); }
            $s->hitungCtC(2,1);
            if ($n===14) $this->assertNull($this->derived($m,6));
            else $this->near($n===15?0:($n===17?-44.444444:22.222222),$this->derived($m,6)); return;
        }
        if ($n>=18 && $n<=21) {
            if ($n===19) {
                foreach ([1,2] as $only) {
                    DB::table($this->table($m))->delete(); $this->data($m,77,7,1,1,2,'derived'); $this->data($m,100,$only);
                    $s->hitungImplisit(2,1); $this->near(77,$this->derived($m,7));
                } return;
            }
            $this->data($m,150,1); $this->data($m,$n===21?0:100,2,1,$n===20?2:1);
            $s->hitungImplisit(2,1);
            if ($n===20) $this->assertNull($this->derived($m,7)); else $this->near($n===21?0:150,$this->derived($m,7)); return;
        }
        if ($n===22) {
            foreach (['hitungImplisitQtQ'=>[8,2],'hitungImplisitYtY'=>[9,4]] as $method=>[$out,$prev]) {
                $this->data($m,120,7,1,1,2,'derived'); $this->data($m,100,7,$prev,1,2,'derived');
                $s->$method(2,1); $this->near(20,$this->derived($m,$out));
                DB::table($this->table($m))->delete();
            } return;
        }
        if ($n===23) {
            foreach (['hitungDistribusi'=>3,'hitungQtQ'=>4,'hitungYtY'=>5,'hitungCtC'=>6,'hitungImplisit'=>7,'hitungImplisitQtQ'=>8,'hitungImplisitYtY'=>9] as $method=>$out) {
                DB::table($this->table($m))->delete();
                foreach ([1,2,4,5] as $p) { $this->data($m,100,1,$p); $this->data($m,100,2,$p); }
                foreach ([1,2,4] as $p) $this->data($m,100,7,$p,1,2,'derived');
                $this->data($m,88,$out,1,1,3,'derived'); $this->data($m,89,$out,3,1,2,'derived');
                $s->$method(2,1); $count=DB::table($this->table($m))->count(); $s->$method(2,1);
                $this->assertSame($count,DB::table($this->table($m))->count());
                $row=DB::table($this->table($m))->where(['jenis_tabel_id'=>$out,'wilayah_id'=>2,'periode_id'=>1])->first();
                $this->assertSame('derived',$row->tipe_data); $this->assertNull($row->submission_id);
                $this->near(88,DB::table($this->table($m))->where(['wilayah_id'=>3,'jenis_tabel_id'=>$out])->value('nilai'));
                $this->near(89,DB::table($this->table($m))->where(['periode_id'=>3,'jenis_tabel_id'=>$out])->value('nilai'));
                DB::table($this->table($m))->where(['periode_id'=>1,'jenis_tabel_id'=>1,'tipe_data'=>'source'])->update(['nilai'=>150]);
                DB::table($this->table($m))->where(['periode_id'=>1,'jenis_tabel_id'=>2,'tipe_data'=>'source'])->update(['nilai'=>120]);
                $s->$method(2,1); $this->assertSame($count,DB::table($this->table($m))->count());
                $expected=match($out){3=>100,4=>20,5=>20,6=>10,7=>125,8=>0,9=>0};
                $this->near($expected,$this->derived($m,$out));
            } return;
        }
        if ($n===24) {
            $this->data($m,60,1); $this->data($m,40,1,1,2); $this->data($m,900,1,1,3,2,'derived');
            $s->hitungDistribusi(2,1); $this->near(60,$this->derived($m,3)); $this->near(40,$this->derived($m,3,2)); $this->assertNull($this->derived($m,3,3)); return;
        }
        if ($n===25) {
            $s->hitungQtQ(2,999); $s->hitungYtY(2,999);
            $this->assertSame(0,DB::table($this->table($m))->count());
            $this->data($m,100,1); $this->data($m,50,1,1,999);
            $s->hitungDistribusi(2,1); $this->near(100,$this->derived($m,3)); $this->near(50,$this->derived($m,3,999));
        }
    }

    private function runIntegration(int $n): void
    {
        $s=new IntegrasiPdrbService;
        if ($n===1) { $this->expectException(ModelNotFoundException::class); $s->generate(999,2); return; }
        if ($n===2 || $n===3) {
            if ($n===3) DB::table('rekonsiliasi_periode')->delete();
            $s->generate(1,$n===2?999:2); $this->assertSame(0,DB::table('integrasi_pdrb')->count()); return;
        }
        if ($n===13) {
            $this->insert('rekonsiliasi_periode',['rekonsiliasi_id'=>1,'periode_id'=>2]);
            $this->data(1,100); $this->data(2,100); $this->data(1,999,2,1,1,3); $this->data(1,888,2,3);
            $s->generate(1,2); $this->assertSame(4,DB::table('integrasi_pdrb')->count());
            $this->near(100,DB::table('integrasi_pdrb')->where(['periode_id'=>1,'jenis_tabel_id'=>2])->value('total_lapus')); return;
        }
        if ($n===9) {
            foreach ([1,2] as $m) { $this->data($m,60); $this->data($m,40,2,1,2); $this->data($m,20,2,1,3); $this->data($m,500,2,1,1,2,'source',42); }
        } elseif ($n!==10) {
            [$a,$b]=match($n){5=>[0.05,0],6=>[0,0.05],7=>[0.050001,0],8=>[120,100],11=>[100,0],default=>[100,100]};
            $this->data(1,$a); if ($n!==11) $this->data(2,$b);
            if ($n===8) { $this->data(1,100,1); $this->data(2,100,1); }
        }
        $s->generate(1,2); $row=DB::table('integrasi_pdrb')->where('jenis_tabel_id',2)->first();
        $this->assertSame(in_array($n,[7,8,11])?'selisih':'sesuai',$row->status);
        $this->near(match($n){5=>0.05,6=>-0.05,7=>0.050001,8=>20,11=>100,default=>0},$row->selisih);
        if ($n===9) { $this->near(100,$row->total_lapus); $this->near(100,$row->total_pengeluaran); }
        if ($n===8) $this->assertSame('sesuai',DB::table('integrasi_pdrb')->where('jenis_tabel_id',1)->value('status'));
        if ($n===10) { $this->near(0,$row->total_lapus); $this->near(0,$row->total_pengeluaran); }
        if ($n===7) {
            DB::table($this->table(1))->update(['nilai'=>0]); DB::table($this->table(2))->delete(); $this->data(2,0.050001);
            $s->generate(1,2); $r=DB::table('integrasi_pdrb')->where('jenis_tabel_id',2)->first(); $this->assertSame('selisih',$r->status); $this->near(-0.050001,$r->selisih);
        }
        if ($n===12) {
            DB::table($this->table(1))->update(['nilai'=>120]); $s->generate(1,2);
            $this->assertSame(2,DB::table('integrasi_pdrb')->count()); $this->near(20,DB::table('integrasi_pdrb')->where('jenis_tabel_id',2)->value('selisih'));
        }
    }

    private function konserdaResult(?int $category=null): object
    {
        return DB::table('hasil_konserda')->where(['putaran_id'=>1,'periode_id'=>1,'modul_id'=>1,'jenis_tabel_id'=>1])->where('kategori_id',$category)->first();
    }
    private function runKonserda(int $n): void
    {
        $c=new RekonsiliasiController; $s=new KonserdaService;
        if ($n<=5) {
            Bus::fake();
            if ($n===1) DB::table('rekonsiliasi')->update(['status'=>'selesai']);
            if ($n===2) DB::table('putaran')->update(['status'=>'selesai']);
            if ($n===5) $this->insert('putaran',['id'=>2,'rekonsiliasi_id'=>1,'nomor'=>2,'status'=>'berlangsung']);
            $r=$c->tutup(); $this->assertSame($n<=2?404:200,$r->getStatusCode());
            if ($n<=2) { Bus::assertNothingDispatched(); return; }
            $id=$n===5?2:1; Bus::assertDispatched(GenerateKonserdaJob::class,fn($job)=>$job->putaranId===$id);
            $row=DB::table('putaran')->where('id',$id)->first(); $this->assertSame('selesai',$row->status); $this->assertNotNull($row->tanggal_selesai);
            $this->assertSame('berlangsung',DB::table('rekonsiliasi')->value('status'));
            if ($n===4) { $this->assertSame(404,$c->tutup()->getStatusCode()); Bus::assertDispatchedTimes(GenerateKonserdaJob::class,1); }
            if ($n===5) $this->assertSame('berlangsung',DB::table('putaran')->where('id',1)->value('status'));
            return;
        }
        if ($n===6) {
            $mock=$this->mock(KonserdaService::class); $mock->shouldReceive('generate')->once()->with(17);
            (new GenerateKonserdaJob(17))->handle($mock); $this->addToAssertionCount(1); return;
        }
        if ($n>=7 && $n<=16) {
            [$p,$a,$d,$status]=match($n){7=>[1000,981,1.9,'aman'],8=>[1000,980,2,'peringatan'],9=>[1000,901,9.9,'peringatan'],10=>[1000,900,10,'ekstrem'],11=>[1000,951,4.9,'aman'],12=>[1000,950,5,'peringatan'],13=>[1000,901,9.9,'peringatan'],14=>[1000,1100,10,'ekstrem'],15=>[-1000,-900,10,'ekstrem'],16=>[0,100,0,'aman']};
            $this->data(1,$p,1,1,1,1); $this->data(1,$a,1); $s->generate(1);
            $r=$this->konserdaResult(in_array($n,[11,12,13])?1:null); $this->near($d,$r->diskrepansi_persen); $this->near($p-$a,$r->selisih); $this->assertSame($status,$r->status);
            if ($n===13) { DB::table($this->table(1))->where('wilayah_id',2)->update(['nilai'=>900]); $s->generate(1); $this->assertSame('ekstrem',$this->konserdaResult(1)->status); }
            return;
        }
        if ($n===17) {
            $this->data(1,100,1,1,1,1); $this->data(1,50,1,1,3,1); $this->data(1,90,1); $s->generate(1);
            $this->near(100,$this->konserdaResult()->nilai_provinsi); $this->near(50,$this->konserdaResult(3)->nilai_provinsi); $this->assertSame(16,DB::table('hasil_konserda')->count()); return;
        }
        if ($n===18) {
            $this->insert('rekonsiliasi_periode',['rekonsiliasi_id'=>1,'periode_id'=>2]);
            foreach ([1,2] as $m) foreach ([1,2] as $p) foreach ([1,2] as $kind) { $this->data($m,100*$m+$p+$kind,$kind,$p,1,1); $this->data($m,30*$m,$kind,$p); $this->data($m,20*$m,$kind,$p,1,3); }
            $s->generate(1); $this->assertSame(32,DB::table('hasil_konserda')->count()); $this->assertSame(64,DB::table('hasil_konserda_details')->count());
            foreach (DB::table('hasil_konserda')->whereNull('kategori_id')->get() as $r) {
                $this->near(100*$r->modul_id+$r->periode_id+$r->jenis_tabel_id,$r->nilai_provinsi);
                $this->near(50*$r->modul_id,$r->nilai_agregasi_kabkota);
                $this->near(30*$r->modul_id,DB::table('hasil_konserda_details')->where(['hasil_konserda_id'=>$r->id,'wilayah_id'=>2])->value('nilai'));
                $this->near(20*$r->modul_id,DB::table('hasil_konserda_details')->where(['hasil_konserda_id'=>$r->id,'wilayah_id'=>3])->value('nilai'));
            } return;
        }
        if ($n===19) {
            $this->insert('wilayah',['id'=>4,'parent_id'=>999,'jenis'=>'kabupaten']); $this->data(1,999,1,1,1,4); $s->generate(1);
            $this->near(0,$this->konserdaResult()->nilai_agregasi_kabkota); $this->assertSame(0,DB::table('hasil_konserda_details')->where('wilayah_id',4)->count()); return;
        }
        if ($n===20) {
            $this->insert('putaran',['id'=>2,'rekonsiliasi_id'=>1,'nomor'=>1]); $s->generate(2); $other=DB::table('hasil_konserda')->where('putaran_id',2)->pluck('id')->all();
            $this->data(1,100,1,1,1,1); $s->generate(1); $this->data(1,20,1); $s->generate(1);
            $this->assertSame(32,DB::table('hasil_konserda')->count()); $this->assertSame(64,DB::table('hasil_konserda_details')->count());
            $this->assertSame($other,DB::table('hasil_konserda')->where('putaran_id',2)->pluck('id')->all()); $this->near(20,$this->konserdaResult()->nilai_agregasi_kabkota);
            $this->assertSame(0,DB::table('hasil_konserda_details')->whereNotIn('hasil_konserda_id',DB::table('hasil_konserda')->select('id'))->count()); return;
        }
        if ($n===21) {
            $s->generate(1); DB::table('wilayah')->where('id',1)->delete();
            try { $s->generate(1); $this->fail('Expected missing province exception'); }
            catch (\Exception $e) { $this->assertSame('Provinsi tidak ditemukan',$e->getMessage()); }
            $this->assertSame(0,DB::table('hasil_konserda')->count()); $this->assertSame(0,DB::table('hasil_konserda_details')->count()); return;
        }
        if ($n===22) { $s->generate(1); DB::table('rekonsiliasi_periode')->delete(); $s->generate(1); $this->assertSame(0,DB::table('hasil_konserda')->count()); return; }
        if ($n===23) {
            DB::table('wilayah')->where('parent_id',1)->delete(); $this->data(1,100,1,1,1,1); $s->generate(1);
            $this->near(0,$this->konserdaResult()->nilai_agregasi_kabkota); $this->assertSame('ekstrem',$this->konserdaResult()->status); $this->assertSame(0,DB::table('hasil_konserda_details')->count()); return;
        }
        if ($n===24) { DB::table('kategori_lapangan_usaha')->delete(); $s->generate(1); $this->near(0,$this->konserdaResult()->nilai_provinsi); $this->assertSame(2,DB::table('hasil_konserda')->where('modul_id',1)->count()); return; }
        if ($n===25) {
            $this->data(1,100,1,1,1,1); $this->data(1,50,1,1,1,1,'source',42); $s->generate(1); $this->near(150,$this->konserdaResult()->nilai_provinsi);
            (new IntegrasiPdrbService)->generate(1,1); $this->near(100,DB::table('integrasi_pdrb')->where('jenis_tabel_id',1)->value('total_lapus')); return;
        }
        if ($n===26) {
            Schema::create('jobs',function(Blueprint $t){$t->id();$t->string('queue');$t->text('payload');$t->integer('attempts');$t->integer('reserved_at')->nullable();$t->integer('available_at');$t->integer('created_at');});
            config(['queue.default'=>'database','queue.connections.database.connection'=>'sqlite']);
            $this->assertSame(200,$c->tutup()->getStatusCode()); $this->assertSame(1,DB::table('jobs')->count());
            $this->assertSame('selesai',DB::table('putaran')->value('status')); $this->assertSame(0,DB::table('hasil_konserda')->count());
            $this->artisan('queue:work',['connection'=>'database','--once'=>true,'--tries'=>1])->assertExitCode(0);
            $this->assertSame(0,DB::table('jobs')->count()); $this->assertSame(16,DB::table('hasil_konserda')->count()); return;
        }
        if ($n===27) {
            $mock=$this->mock(KonserdaService::class); $mock->shouldReceive('generate')->once()->with(1)->andThrow(new \RuntimeException('forced failure'));
            try { $c->tutup(); $this->fail('Expected job exception'); } catch (\RuntimeException $e) { $this->assertSame('forced failure',$e->getMessage()); }
            $this->assertSame('selesai',DB::table('putaran')->value('status')); $this->assertSame(0,DB::table('hasil_konserda')->count()); return;
        }
        if ($n===28) {
            $this->postJson('/api/rekonsiliasi/tutup')->assertUnauthorized();
            $user=new User; $user->forceFill(['id'=>99,'role_id'=>2]); $this->actingAs($user,'web');
            $this->postJson('/api/rekonsiliasi/tutup')->assertForbidden(); $this->assertSame('berlangsung',DB::table('putaran')->value('status'));
        }
    }
}