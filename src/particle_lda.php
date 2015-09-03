<?php

// require_once 'read_documents.php';
require_once 'read_documents_raw.php';
require_once 'write_results.php';

class Particle
{
    private $root;
    private $parent;

    private $k;
    private $alpha;
    private $beta;

    private $ntd;
    private $nwt;
    private $nt;

    private $maxReentDocuments;
    private $recentDocuments;
    private $recentTopics;

    private $wordTypes;

    private $logWeight;

    public function __construct($k, $alpha, $beta, $maxRecentDocuments = 20)
    {
        $this->root = $this;
        $this->parent = null;

        $this->k = $k;
        $this->alpha = $alpha;
        $this->beta = $beta;

        $this->nwt = [];
        $this->nt = array_fill(0, $this->k, 0);

        $this->wordTypes = [];
        $this->logWeight = 0;

        $this->maxRecentDocuments = $maxRecentDocuments;
        $this->recentDocuments = [];
        $this->recentTopics = [];
    }

    public function getLogWeight()
    {
        return $this->logWeight;
    }

    public function getDocumentTopicCounts()
    {
        return $this->ntd;
    }

    public function getTopicCountsOfWord($word)
    {
        if (array_key_exists($word, $this->nwt)) {
            return $this->nwt[$word];
        }

        if ($this->parent) {
            return $this->parent->getTopicCountsOfWord($word);
        }

        return array_fill(0, $this->k, 0);
    }

    public function prepareForNewDocument()
    {
        $this->ntd = array_fill(0, $this->k, 0);
        if (count($this->recentDocuments) === $this->maxRecentDocuments) {
            array_shift($this->recentDocuments);
            array_shift($this->recentTopics);
        }
        $this->recentDocuments[] = [];
        $this->recentTopics[] = [];
    }

    public function processNextWord($word)
    {
        if (!array_key_exists($word, $this->nwt)) {
            $this->nwt[$word] = $this->getTopicCountsOfWord($word);
            $this->root->wordTypes[$word] = true;
        }

        $topic = $this->sample($word);

        ++$this->ntd[$topic];
        ++$this->nwt[$word][$topic];
        ++$this->nt[$topic];

        $di = count($this->recentDocuments);
        $this->recentDocuments[$di - 1][] = $word;
        $this->recentTopics[$di - 1][] = $topic;
    }

    public function cloneParticle()
    {
        $newParticle = new Particle(
            $this->k,
            $this->alpha,
            $this->beta,
            $this->maxRecentDocuments
        );
        $newParticle->root = $this->root;
        $newParticle->parent = $this;
        $newParticle->ntd = $this->ntd;
        $newParticle->nt = $this->nt;
        $newParticle->recentDocuments = $this->recentDocuments;
        $newParticle->recentTopics = $this->recentTopics;

        return $newParticle;
    }

    private function sample($word)
    {
        $total = 0;
        $nWordTypes = count($this->root->wordTypes);
        $cfreq = array();

        $td_denom = array_sum($this->ntd) + $this->k * $this->alpha;
        for ($t = 0; $t < $this->k; ++$t) {
            $td_numer = $this->ntd[$t] + $this->alpha;
            $wt_numer = $this->getTopicCountsOfWord($word)[$t] + $this->beta;
            $wt_denom = $this->nt[$t] + $nWordTypes * $this->beta;
            $freq = $td_numer * $wt_numer / ($td_denom * $wt_denom);
            $total += $freq;
            $cfreq[] = $total;
        }

        $this->logWeight += log($total);

        $r = mt_rand() / mt_getrandmax() * $total;
        for ($t = 0; $t < $this->k; ++$t) {
            if ($r < $cfreq[$t]) {
                return $t;
            }
        }
        return $this->k - 1;
    }

    public function getIndexAtRandom()
    {
        $total = 0;
        foreach ($this->recentDocuments as $document) {
            $total += count($document);
        }

        $wi = intval(mt_rand() / mt_getrandmax() * $total);
        for ($di = 0; $di < count($this->recentDocuments); ++$di) {
            $document = $this->recentDocuments[$di];
            if ($wi < count($document)) {
                return [$di, $wi];
            }
            $wi -= count($document);
        }
    }

    public function rejuvenate($docIndex, $wordIndex)
    {
        $word = $this->recentDocuments[$docIndex][$wordIndex];
        $topic = $this->recentTopics[$docIndex][$wordIndex];

        if (!array_key_exists($word, $this->nwt)) {
            $this->nwt[$word] = $this->getTopicCountsOfWord($word);
            $this->root->wordTypes[$word] = true;
        }

        if ($docIndex === count($this->recentDocuments) - 1) {
            --$this->ntd[$topic];
        }
        --$this->nwt[$word][$topic];
        --$this->nt[$topic];

        $topic = $this->sample($word);

        if ($docIndex === count($this->recentDocuments) - 1) {
            ++$this->ntd[$topic];
        }
        ++$this->nwt[$word][$topic];
        ++$this->nt[$topic];

        $this->recentTopics[$docIndex][$wordIndex] = $topic;
    }
}

function main($argc, $argv)
{
    if ($argc < 5) {
        echo "Usage: $argv[0] filename k S R [ess-threshold [alpha beta]]\n";
        return;
    }

    $k = intval($argv[2]);
    $s = intval($argv[3]);
    $r = intval($argv[4]);
    $threshold = $argc > 5 ? doubleval($argv[5]) : $s * 0.2;
    $alpha = $argc > 6 ? doubleval($argv[6]) : 0.1;
    $beta = $argc > 6 ? doubleval($argv[7]) : 0.01;

    $particles = createParticles($s, $k, $alpha, $beta);

    $documents = read_documents_raw($argv[1]);
    $nDocuments = count($documents);

    for ($i = 0; $i < $nDocuments; ++$i) {
        fputs(STDERR, "Process documents ($i / $nDocuments)\n");
        prepareForNewDocument($particles);
        foreach ($documents[$i] as $word) {
            processNextWord($particles, $word);
            $ess = calculateEss($particles);
            if ($ess < $threshold) {
                $particles = resample($particles);
                for ($ri = 0; $ri < $r; ++$ri) {
                    rejuvenate($particles);
                }
            }
        }
        echo implode(' ', getDocTopicFreq($particles, $k, $s)) . "\n";
    }
}

function createParticles($nParticles, $nTopics, $alpha, $beta)
{
    $particles = [];
    for ($i = 0; $i < $nParticles; ++$i) {
        $particles[$i] = new Particle($nTopics, $alpha, $beta);
    }

    return $particles;
}

function prepareForNewDocument($particles)
{
    foreach ($particles as $particle) {
        $particle->prepareForNewDocument();
    }
}

function processNextWord($particles, $word)
{
    foreach ($particles as $particle) {
        $particle->processNextWord($word);
    }
}

function calculateEss($particles)
{
    $weights = getWeights($particles);

    $totalWeight = array_sum($weights);

    $normWeights = array_map(
        function ($weight) use ($totalWeight) {
            return $weight / $totalWeight;
        },
        $weights
    );

    $sqNormWeights = array_map(
        function ($weight) {
            return pow($weight, 2);
        },
        $normWeights
    );

    $ess = 1 / array_sum($sqNormWeights);

    return $ess;
}

function resample($particles)
{
    $weights = getWeights($particles);

    $totalWeight = 0;
    $cumulativeWeights = [];
    foreach ($weights as $weight) {
        $totalWeight += $weight;
        $cumulativeWeights[] = $totalWeight;
    }

    $newParticles = [];
    for ($si = 0; $si < count($particles); ++$si) {
        $r = mt_rand() / mt_getrandmax() * $totalWeight;
        $r = $r < $totalWeight ? $r : 0;
        for ($i = 0; $i < count($cumulativeWeights); ++$i) {
            if ($r < $cumulativeWeights[$i]) {
                $newParticles[] = $particles[$i]->cloneParticle();
                break;
            }
        }
    }

    return $newParticles;
}

function getWeights($particles)
{
    $logWeights = array_map(
        function ($particle) {
            return $particle->getLogWeight();
        },
        $particles
    );

    $maxLogWeight = max($logWeights);

    $weights = array_map(
        function ($logWeight) use ($maxLogWeight) {
            return exp($logWeight - $maxLogWeight);
        },
        $logWeights
    );

    return $weights;
}

function rejuvenate($particles)
{
    list ($di, $wi) = $particles[0]->getIndexAtRandom();
    foreach ($particles as $particle) {
        $particle->rejuvenate($di, $wi);
    }
}

function getDocTopicFreq($particles, $k, $s)
{
    $result = array_fill(0, $k, 0);
    foreach ($particles as $particle) {
        $counts = $particle->getDocumentTopicCounts();
        for ($i = 0; $i < count($counts); ++$i) {
            $result[$i] += $counts[$i];
        }
    }

    $result = array_map(
        function ($c) use ($s) {
            return $c / $s;
        },
        $result
    );

    return $result;
}

main($argc, $argv);
