<?php

namespace AppBundle\Feed;

use AppBundle\Entity\Merchant;
use AppBundle\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class Reader
 * @package AppBundle\Feed
 * @author  Etienne Dauvergne <contact@ekyna.com>
 */
class Reader
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var \AppBundle\Repository\ProductRepository
     */
    private $productRepository;

    /**
     * @var \AppBundle\Repository\OfferRepository
     */
    private $offerRepository;

    /**
     * @var Merchant
     */
    private $merchant;

    /**
     * @var int
     */
    private $count;


    /**
     * Constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;

        $this->offerRepository = $this->em->getRepository('AppBundle:Offer');
        $this->productRepository = $this->em->getRepository('AppBundle:Product');
    }

    /**
     * Reads the merchant's feed and creates or update the resulting offers.
     *
     * @param Merchant $merchant
     *
     * @return int The number of created or updated offers.
     */
    public function read(Merchant $merchant)
    {
        $this->merchant = $merchant;
        $this->count = 0;

        $url = $merchant->getFeedUrl();
        $format = strtolower(pathinfo($merchant->getFeedUrl(), PATHINFO_EXTENSION));

        switch ($format) {
            case 'json':
                $this->loadJsonFeed($url);
                break;

            case 'csv':
                $this->loadCsvFeed($url);
                break;

            case 'xml':
                $this->loadXmlFeed($url);
                break;
        }

        $this->em->flush();

        return $this->count;
    }

    /**
     * Loads the JSON feed.
     *
     * @param string $url
     */
    private function loadJsonFeed($url)
    {
        $results = json_decode(file_get_contents($url), true);

        foreach ($results as $data) {
            $this->createOrUpdateOffer($data['ean_code'], $data['price']);
        }
    }

    /**
     * Loads the CSV feed.
     *
     * @param string $url
     */
    private function loadCsvFeed($url)
    {
        $handle = fopen($url, 'r');

        while (false !== $data = fgetcsv($handle, null, ';')) {
            $this->createOrUpdateOffer($data[0], $data[1]);
        }

        fclose($handle);
    }

    /**
     * Loads the XML feed.
     *
     * @param string $url
     */
    private function loadXmlFeed($url)
    {
        $dom = new \DOMDocument();
        $dom->load($url);

        $offers = $dom->getElementsByTagName('offer');

        /** @var \DomElement $offer */
        foreach ($offers as $offer) {
            $this->createOrUpdateOffer(
                $offer->getAttribute('ean_code'),
                $offer->getAttribute('price')
            );
        }
    }

    /**
     * Creates or updates the offer regarding to the given ean code.
     *
     * @param string $eanCode
     * @param string $price
     *
     * @return bool
     */
    private function createOrUpdateOffer($eanCode, $price)
    {
        $product = $this->productRepository->findOneBy(['eanCode' => $eanCode]);

        if (null === $product) {
            return false;
        }

        $offer = $this->offerRepository->findOneBy([
            'merchant' => $this->merchant,
            'product' => $product,
        ]);

        if (null === $offer) {
            $offer = new Offer();
            $offer
                ->setProduct($product)
                ->setMerchant($this->merchant);
        }

        $offer
            ->setPrice($price)
            ->setUpdatedAt(new \DateTime());

        $this->em->persist($offer);

        $this->count++;

        return true;
    }
}
