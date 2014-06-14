<?hh

require_once 'read_documents.hh';
// require_once 'read_documents_raw.hh';
require_once 'write_results.hh';

class LDA {

    private int $k;
    private float $a;
    private float $b;

    private Vector<Vector<string>> $w = Vector {};
    private Set<string> $u = Set {};

    private Vector<Vector<int>> $z = Vector {};
    private Vector<Vector<float>> $ntd = Vector {};
    private Vector<Map<string, float>> $nwt = Vector {};
    private Vector<float> $nt = Vector {};
    private int $v = 0;

    public function __construct(int $k, float $a, float $b) : void
    {
        $this->k = $k;
        $this->a = $a;
        $this->b = $b;
    }

    public function getDocTopicFreq() : Vector<Vector<float>>
    {
        return $this->ntd;
    }

    public function getTopicWordFreq() : Vector<Map<string, float>>
    {
        return $this->nwt;
    }

    public function setData(Vector<Vector<string>> $w) : void
    {
        $this->w = $w;
        $this->initialize();
    }

    private function initialize() : void
    {
        $this->initialize_z();
        $this->initialize_u();
        $this->initialize_ntd();
        $this->initialize_nwt();
    }

    private function initialize_z() : void
    {
        $w = $this->w;
        $k = $this->k;

        $z = Vector {};
        for ($m = 0; $m < count($w); ++$m) {
            $z[] = Vector {};
            for ($n = 0; $n < count($w[$m]); ++$n) {
                $z[$m][] = mt_rand(0, $k - 1);
            }
        }

        $this->z = $z;
    }

    private function initialize_u() : void
    {
        $u = Set {};
        foreach ($this->w as $doc) {
            foreach ($doc as $w) {
                $u[] = $w;
            }
        }

        $this->u = $u;
        $this->v = count($u);
    }

    private function initialize_ntd() : void
    {
        $z = $this->z;
        $k = $this->k;

        $ntd = Vector {};
        for ($d = 0; $d < count($z); ++$d) {
            $ntd[] = array_fill(0, $k, 0);
            foreach ($z[$d] as $t) {
                ++$ntd[$d][$t];
            }
        }

        $this->ntd = $ntd;
    }

    private function initialize_nwt() : void
    {
        $z = $this->z;
        $w = $this->w;
        $k = $this->k;

        $nwt = Vector {};
        $nt = Vector {};
        $words = $this->u;

        for ($t = 0; $t < $k; ++$t) {
            $nwt[] = Map {};
            foreach ($words as $wd) {
                $nwt[$t][$wd] = 0.0;
            }
            $nt[] = 0.0;
        }
        for ($d = 0; $d < count($z); ++$d) {
            for ($i = 0; $i < count($z[$d]); ++$i) {
                $t = $z[$d][$i];
                $wd = $w[$d][$i];
                ++$nwt[$t][$wd];
                ++$nt[$t];
            }
        }

        $this->nwt = $nwt;
        $this->nt = $nt;
    }

    public function update() : void
    {
        $w = $this->w;
        for ($m = 0; $m < count($w); ++$m) {
            for ($n = 0; $n < count($w[$m]); ++$n) {
                $this->reassign($m, $n);
            }
        }
    }

    private function reassign(int $m, int $n) : void
    {
        $t = $this->z[$m][$n];
        $w = $this->w[$m][$n];

        --$this->ntd[$m][$t];
        --$this->nwt[$t][$w];
        --$this->nt[$t];

        $t = $this->sample($m, $w);
        $this->z[$m][$n] = $t;

        ++$this->ntd[$m][$t];
        ++$this->nwt[$t][$w];
        ++$this->nt[$t];
    }

    private function sample(int $m, string $w) : int
    {
        $total = 0;
        $cfreq = array();
        for ($t = 0; $t < $this->k; ++$t) {
            $td = $this->ntd[$m][$t] + $this->a;
            $wt_numer = $this->nwt[$t][$w] + $this->b;
            $wt_denom = $this->nt[$t] + $this->v * $this->b;
            $freq = $td * $wt_numer / $wt_denom;
            $total += $freq;
            $cfreq[] = $total;
        }

        $r = mt_rand() / mt_getrandmax() * $total;
        for ($t = 0; $t < $this->k; ++$t) {
            if ($r < $cfreq[$t]) {
                return $t;
            }
        }
        return $this->k - 1;
    }
}

function main(int $argc, array<string> $argv) : void
{
    if ($argc < 2) {
        echo "Usage: $argv[0] filename k [iter [alpha beta]]\n";
        return;
    }

    $w = read_documents($argv[1]);
    // $w = read_documents_raw($argv[1]);
    $k = (int) $argv[2];
    $n = $argc > 3 ? (int) $argv[3] : 100;
    $a = $argc > 5 ? (float) $argv[4] : 0.1;
    $b = $argc > 5 ? (float) $argv[5] : 0.01;

    $lda = new LDA($k, $a, $b);
    $lda->setData($w);
    for ($i = 0; $i < $n; ++$i) {
        fputs(STDERR, "Iteration " . ($i + 1) . " / $n\n");
        $lda->update();
    }

    $ntd = $lda->getDocTopicFreq();
    $nwt = $lda->getTopicWordFreq();

    write_doc_topic_freq($ntd);
    write_topic_word_freq($nwt);
}

main($argc, $argv);
