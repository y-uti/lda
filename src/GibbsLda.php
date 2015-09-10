<?php
namespace YUti\Lda;

class GibbsLda
{
    private $k;
    private $a;
    private $b;

    private $corpus;
    private $documents;
    private $wordTypeCount;

    private $z;
    private $ntd;
    private $nwt;
    private $nt;

    public function __construct($k, $a, $b)
    {
        $this->k = $k;
        $this->a = $a;
        $this->b = $b;
    }

    public function getDocTopicFreq()
    {
        return $this->ntd;
    }

    public function getTopicWordFreq()
    {
        return $this->nwt;
    }

    public function train($corpus, $maxIteration)
    {
        $this->corpus = $corpus;
        $this->initialize();
        for ($i = 0; $i < $maxIteration; ++$i) {
            // echo "Iteration " . ($i + 1) . " / $maxIteration\n";
            $this->update();
        }
    }

    private function initialize()
    {
        $this->wordTypeCount = count($this->corpus->getWordTypes());
        $this->documents = $this->corpus->getDocuments();
        $this->initializeZ();
        $this->initializeNtd();
        $this->initializeNwt();
    }

    private function initializeZ()
    {
        $this->z = $this->documents;
        array_walk_recursive($this->z, function (&$topic) {
            $topic = mt_rand(0, $this->k - 1);
        });
    }

    private function initializeNtd()
    {
        $z = $this->z;
        $k = $this->k;

        $ntd = array();
        for ($d = 0; $d < count($z); ++$d) {
            $ntd[$d] = array_fill(0, $k, 0);
            foreach ($z[$d] as $t) {
                ++$ntd[$d][$t];
            }
        }

        $this->ntd = $ntd;
    }

    private function initializeNwt()
    {
        $z = $this->z;
        $w = $this->documents;
        $k = $this->k;

        $nwt = array();
        $nt = array();

        for ($t = 0; $t < $k; ++$t) {
            $nwt[$t] = array_fill(0, $this->wordTypeCount, 0);
            $nt[$t] = 0;
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

    public function update()
    {
        $w = $this->documents;
        for ($m = 0; $m < count($w); ++$m) {
            for ($n = 0; $n < count($w[$m]); ++$n) {
                $this->reassign($m, $n);
            }
        }
    }

    private function reassign($m, $n)
    {
        $t = $this->z[$m][$n];
        $w = $this->documents[$m][$n];

        --$this->ntd[$m][$t];
        --$this->nwt[$t][$w];
        --$this->nt[$t];

        $t = $this->sample($m, $w);
        $this->z[$m][$n] = $t;

        ++$this->ntd[$m][$t];
        ++$this->nwt[$t][$w];
        ++$this->nt[$t];
    }

    private function sample($m, $w)
    {
        $total = 0;
        $cfreq = array();
        for ($t = 0; $t < $this->k; ++$t) {
            $td = $this->ntd[$m][$t] + $this->a;
            $wt_numer = $this->nwt[$t][$w] + $this->b;
            $wt_denom = $this->nt[$t] + $this->wordTypeCount * $this->b;
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
