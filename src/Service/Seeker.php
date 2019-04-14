<?php

declare(strict_types = 1);

namespace App\Service;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * @package App\Service
 */
class Seeker
{
    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:merge_all_csv',
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        $this->file = new \SplFileObject(dirname(__DIR__) . '/../files/all.csv');
    }

    /**
     * @return array
     */
    public function startSearching(): array
    {
        $i = 0;
        foreach ($this->file as $product) {
            if ($product !== "\n") {
                dump($i++, $product);
            }
        }
    }
}