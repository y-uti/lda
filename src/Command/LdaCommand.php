<?php
namespace YUti\Lda\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use YUti\Lda\CorpusReader;
use YUti\Lda\GibbsLda;
use YUti\Lda\ModelWriter;

class LdaCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('lda:gibbs')
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
                'iteration',
                'i',
                InputOption::VALUE_REQUIRED,
                'Set the number of iterations',
                100
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
        $corpusFile = $input->getArgument('corpus');
        $topics = intval($input->getArgument('topics'));
        $iteration = intval($input->getOption('iteration'));
        $alpha = doubleval($input->getOption('alpha'));
        $beta = doubleval($input->getOption('beta'));

        $corpus = (new CorpusReader())->read($corpusFile);

        $lda = new GibbsLda($topics, $alpha, $beta);
        $lda->train($corpus, $iteration);

        $ntd = $lda->getDocTopicFreq();
        $nwt = $lda->getTopicWordFreq();

        $writer = new ModelWriter();
        $writer->writeDocTopicFreq($ntd);
        $writer->writeTopicWordFreq($corpus, $nwt);
    }
}
