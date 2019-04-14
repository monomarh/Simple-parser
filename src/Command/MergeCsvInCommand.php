<?php

declare(strict_types = 1);

namespace App\Command;

use Cowsayphp\Cow;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package App\Command
 */
class MergeCsvInCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected static $defaultName = 'app:merge_all_csv';

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Merge all csv from files')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rowKey = 0;

        if (file_exists(dirname(__DIR__) . '/../files/all.csv')) {
            unlink(dirname(__DIR__) . '/../files/all.csv');
        }

        foreach (glob(dirname(__DIR__) . '/../files/*.csv') as $filename) {

            if (($file = new \SplFileObject($filename, 'rb')) !== false) {

                while (($data = $file->fgetcsv()) !== null) {
                    $fieldsCount = count($data);

                    for ($i=0; $i < $fieldsCount; $i++)
                    {
                        $arrayFromCsv[$rowKey][] = $data[$i];
                    }
                    $rowKey++;
                }
            }
        }

        $fp = fopen(dirname(__DIR__) . '/../files/all.csv', 'wb');

        foreach ($arrayFromCsv as $fields) {
            foreach ($fields as $propertyKey => $field) {
                $fields[$propertyKey] = str_replace(PHP_EOL, '', $field);
            }
            fputcsv($fp, $fields);
        }

        fclose($fp);

        $this->io->success(Cow::say('all.csv file is ready'));
    }
}
