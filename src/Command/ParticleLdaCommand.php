<?php
namespace YUti\Lda\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use YUti\Lda\CorpusReader;
use YUti\Lda\ModelWriter;
use YUti\Lda\ParticleLda;

class ParticleLdaCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('lda:particle')
            ->setDescription('');

        $this
            ->addArgument(
                'corpus',
                InputArgument::REQUIRED,
                'Set the corpus file name to train'
            )
            ->addArgument(
                'topics',
                InputArgument::REQUIRED,
                'Set the number of topics'
            )
        ;

        $this
            ->addOption(
                'particles',
                'p',
                InputOption::VALUE_REQUIRED,
                'Set the number of particles',
                100
            )
            ->addOption(
                'rejuvenation',
                'r',
                InputOption::VALUE_REQUIRED,
                'Set the number of documents used for rejuvenation',
                10
            )
            ->addOption(
                'ess-threshold',
                'e',
                InputOption::VALUE_REQUIRED,
                'Set the ess threshold',
                0.2
            )
            ->addOption(
                'alpha',
                'a',
                InputOption::VALUE_REQUIRED,
                'Set the hyper-parameter alpha (not estimated, symmetric only)',
                0.1
            )
            ->addOption(
                'beta',
                'b',
                InputOption::VALUE_REQUIRED,
                'Set the hyper-parameter beta (not estimated, symmetric only)',
                0.01
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $corpusFile = $input->getArgument('corpus');
        $topics = intval($input->getArgument('topics'));
        $particles = intval($input->getOption('particles'));
        $rejuvenation = intval($input->getOption('rejuvenation'));
        $essThreshold = intval($input->getOption('ess-threshold'));
        $alpha = doubleval($input->getOption('alpha'));
        $beta = doubleval($input->getOption('beta'));

        $corpus = (new CorpusReader())->read($corpusFile);

        $lda = new ParticleLda(
            $topics,
            $alpha,
            $beta,
            $particles,
            $rejuvenation,
            $essThreshold
        );
        $lda->setLogger($logger);

        $lda->train($corpus, $particles, $rejuvenation, $essThreshold);
    }
}
