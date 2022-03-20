<?php

namespace Kanata\Commands\Traits;

use Symfony\Component\Console\Output\OutputInterface;

trait LogoTrait
{
    public function writeLogo(OutputInterface $output): void
    {
        $output->writeln('Kanata by Savio Resende');
        $output->writeln('');
    }
}