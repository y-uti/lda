<?php

require_once 'read_documents.php';
// require_once 'read_documents_raw.php';
require_once 'write_results.php';

class LDA {

    private $k;
    private $a;
    private $b;

    private $w;
    private $u;

    private $z;
    private $ntd;
    private $nwt;
    private $nt;
    private $v;

    function __construct($k, $a, $b)
    {
        $this->k = $k;
        $this->a = $a;
        $this->b = $b;
    }

    function getDocTopicFreq()
    {
        return $this->ntd;
    }

    function getTopicWordFreq()
    {
        return $this->nwt;
    }

    function setData($w)
    {
        $this->w = $w;
        $this->initialize();
    }

    private function initialize()
    {
        $this->initialize_z();
        $this->initialize_u();
        $this->initialize_ntd();
        $this->initialize_nwt();
    }

    private function initialize_z()
    {
        $w = $this->w;
        $k = $this->k;

        $z = array();
        for ($m = 0; $m < count($w); ++$m) {
            $z[$m] = array();
            for ($n = 0; $n < count($w[$m]); ++$n) {
                $z[$m][$n] = mt_rand(0, $k - 1);
            }
        }

        $this->z = $z;
    }

    private function initialize_u()
    {
        $u = array();
        foreach ($this->w as $doc) {
            foreach ($doc as $w) {
                if (!in_array($w, $u)) {
                    $u[] = $w;
                }
            }
        }

        $this->u = $u;
        $this->v = count($u);
    }

    private function initialize_ntd()
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

    private function initialize_nwt()
    {
        $z = $this->z;
        $w = $this->w;
        $k = $this->k;

        $nwt = array();
        $nt = array();
        $words = $this->u;

        for ($t = 0; $t < $k; ++$t) {
            foreach ($words as $wd) {
                $nwt[$t][$wd] = 0;
            }
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

    private function get_words($w)
    {
        $words = array();
        foreach ($w as $doc) {
            foreach ($doc as $word) {
                if (!in_array($word, $words)) {
                    $words[] = $word;
                }
            }
        }

        return $words;
    }

    function update()
    {
        $w = $this->w;
        for ($m = 0; $m < count($w); ++$m) {
            for ($n = 0; $n < count($w[$m]); ++$n) {
                $this->reassign($m, $n);
            }
        }
    }

    private function reassign($m, $n)
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

    private function sample($m, $w)
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

function main($argc, $argv)
{
    if ($argc < 2) {
        echo "Usage: $argv[0] filename k [iter [alpha beta]]\n";
        return;
    }

    $w = read_documents($argv[1]);
    // $w = read_documents_raw($argv[1]);
    $k = $argv[2];
    $n = $argc > 3 ? $argv[3] : 100;
    $a = $argc > 5 ? $argv[4] : 0.1;
    $b = $argc > 5 ? $argv[5] : 0.01;

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
