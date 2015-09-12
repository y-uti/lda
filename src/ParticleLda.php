<?php
namespace YUti\Lda;

class ParticleLda
{
    private $nTopics;
    private $alpha;
    private $beta;

    private $corpus;
    private $documents;
    private $wordTypeCount;

    private $particles;

    private $logger;

    public function __construct($nTopics, $alpha, $beta)
    {
        $this->nTopics = $nTopics;
        $this->alpha = $alpha;
        $this->beta = $beta;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function train($corpus, $particles, $rejuvenation, $essThreshold)
    {
        $this->corpus = $corpus;
        $this->initialize();

        $this->createParticles($particles);

        $nDocuments = count($this->documents);
        for ($i = 0; $i < $nDocuments; ++$i) {
            $this->logger->info("Process documents (" . ($i + 1) . " / $nDocuments)");
            $this->prepareForNewDocument();
            foreach ($this->documents[$i] as $word) {
                $this->processNextWord($word);
                $ess = $this->calculateEss();
                if ($ess < $essThreshold) {
                    $this->resample();
                    for ($ri = 0; $ri < $rejuvenation; ++$ri) {
                        $this->rejuvenate();
                    }
                }
            }
            echo implode(' ', $this->getDocTopicFreq()) . "\n";
        }
    }

    private function initialize()
    {
        $this->wordTypeCount = count($this->corpus->getWordTypes());
        $this->documents = $this->corpus->getDocuments();
    }

    private function createParticles($nParticles)
    {
        $particles = [];
        for ($i = 0; $i < $nParticles; ++$i) {
            $particles[$i] = new Particle($this->nTopics, $this->alpha, $this->beta);
        }

        $this->particles = $particles;
    }

    private function prepareForNewDocument()
    {
        foreach ($this->particles as $particle) {
            $particle->prepareForNewDocument();
        }
    }

    private function processNextWord($word)
    {
        foreach ($this->particles as $particle) {
            $particle->processNextWord($word);
        }
    }

    private function calculateEss()
    {
        $weights = $this->getWeights();

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

    private function resample()
    {
        $weights = $this->getWeights();

        $totalWeight = 0;
        $cumulativeWeights = [];
        foreach ($weights as $weight) {
            $totalWeight += $weight;
            $cumulativeWeights[] = $totalWeight;
        }

        $newParticles = [];
        for ($si = 0; $si < count($this->particles); ++$si) {
            $r = mt_rand() / mt_getrandmax() * $totalWeight;
            $r = $r < $totalWeight ? $r : 0;
            for ($i = 0; $i < count($cumulativeWeights); ++$i) {
                if ($r < $cumulativeWeights[$i]) {
                    $newParticles[] = $particles[$i]->cloneParticle();
                    break;
                }
            }
        }

        $this->particles = $newParticles;
    }

    private function getWeights()
    {
        $logWeights = array_map(
            function ($particle) {
                return $particle->getLogWeight();
            },
            $this->particles
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

    private function rejuvenate()
    {
        list($di, $wi) = $this->particles[0]->getIndexAtRandom();
        foreach ($this->particles as $particle) {
            $particle->rejuvenate($di, $wi);
        }
    }

    private function getDocTopicFreq()
    {
        $result = array_fill(0, $this->nTopics, 0);
        foreach ($this->particles as $particle) {
            $counts = $particle->getDocumentTopicCounts();
            for ($i = 0; $i < count($counts); ++$i) {
                $result[$i] += $counts[$i];
            }
        }

        $result = array_map(
            function ($c) {
                return $c / count($this->particles);
            },
            $result
        );

        return $result;
    }
}
