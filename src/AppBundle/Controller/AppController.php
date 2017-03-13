<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AppController
 * @package AppBundle\Controller
 */
class AppController extends Controller
{
    /**
     * App index action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $products = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->findAll();

        return $this->render('AppBundle:App:index.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * App product detail action.
     *
     * @param int $productId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productAction($productId)
    {
        $product = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->find($productId);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $offers = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Offer')
            ->findBy(
                ['product' => $product],
                ['price' => 'ASC']
            );

        return $this->render('AppBundle:App:product.html.twig', [
            'product' => $product,
            'offers'  => $offers,
        ]);
    }
}
