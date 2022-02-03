<?php

namespace Kanata\Commands\Traits;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

trait LogoTrait
{
    public function writeLogo(OutputInterface $output): void
    {
        $outputStyle = new OutputFormatterStyle('#fff', '#074f8d', ['bold']);
        $output->getFormatter()->setStyle('fire', $outputStyle);
        $output->writeln('<fire>######################');
        $output->writeln('######################');
        $output->writeln('Welcome to Kanata!');
        $output->writeln('######################</>');
    }
}