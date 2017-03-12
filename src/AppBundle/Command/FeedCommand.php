<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FeedCommand
 * @package AppBundle\Command
 * @author  Etienne Dauvergne <contact@ekyna.com>
 */
class FeedCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:feed')
            ->addArgument('merchantCode', InputArgument::REQUIRED, 'The merchant code')
            ->setDescription('Reads the given merchant\'s data feed and create or updates offers.')
            ->setHelp(<<<EOT
La commande app:feed permet de récupérer les offres des sites marchands affiliés d'après leur flux de données :

    Ex: php app/console app:feed 123456 
EOT
);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $merchantCode = $input->getArgument('merchantCode');

        $manager = $container->get('doctrine.orm.entity_manager');
        $reader = $container->get('app.feed.reader');

        $merchant = $manager
            ->getRepository('AppBundle:Merchant')
            ->findOneBy(['code' => $merchantCode]);

        if (!$merchant) {
            throw new \Exception('Merchant not found.');
        }

        $count = $reader->read($merchant);

        $output->writeln(sprintf('Fetched %s offer(s).', $count));
    }
}
