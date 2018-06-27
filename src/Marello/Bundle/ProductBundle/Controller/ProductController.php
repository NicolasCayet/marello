<?php

namespace Marello\Bundle\ProductBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;

use Oro\Bundle\SecurityBundle\Annotation as Security;

use Marello\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class ProductController extends Controller
{
    /**
     * @Config\Route(
     *      "/{_format}",
     *      name="marello_product_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format"="html"}
     * )
     * @AclAncestor("marello_product_view")
     * @Config\Template
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Config\Route("/create", name="marello_product_create")
     * @AclAncestor("marello_product_create")
     * @Config\Template("MarelloProductBundle:Product:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Product());
    }

    /**
     * @Config\Route("/update/{id}", requirements={"id"="\d+"}, name="marello_product_update")
     * @AclAncestor("marello_product_update")
     * @Config\Template
     *
     * @param Product $product
     *
     * @return array
     */
    public function updateAction(Product $product)
    {
        return $this->update($product);
    }

    /**
     * @param Product $product
     *
     * @return array
     */
    protected function update(Product $product)
    {
        $handler = $this->get('marello_product.product_form.handler');

        if ($handler->process($product)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('marello.product.messages.success.product.saved')
            );

            return $this->get('oro_ui.router')->redirectAfterSave(
                [
                    'route'      => 'marello_product_update',
                    'parameters' => [
                        'id'                      => $product->getId(),
                    ]
                ],
                [
                    'route'      => 'marello_product_view',
                    'parameters' => [
                        'id'                      => $product->getId(),
                    ]
                ],
                $product
            );
        }

        return [
            'entity' => $product,
            'form'   => $handler->getFormView(),
        ];
    }

    /**
     * @Config\Route("/view/{id}", requirements={"id"="\d+"}, name="marello_product_view")
     * @AclAncestor("marello_product_view")
     * @Config\Template("MarelloProductBundle:Product:view.html.twig")
     *
     * @param Product $product
     *
     * @return array
     */
    public function viewAction(Product $product)
    {
        return [
            'entity' => $product,
        ];
    }

    /**
     * @Config\Route("/widget/info/{id}", name="marello_product_widget_info", requirements={"id"="\d+"})
     * @AclAncestor("marello_product_view")
     * @Config\Template
     *
     * @param Product $product
     *
     * @return array
     */
    public function infoAction(Product $product)
    {
        return [
            'product' => $product
        ];
    }

    /**
     * @Config\Route("/widget/price/{id}", name="marello_product_widget_price", requirements={"id"="\d+"})
     * @AclAncestor("marello_product_view")
     * @Config\Template
     *
     * @param Product $product
     *
     * @return array
     */
    public function priceAction(Product $product)
    {
        return [
            'product' => $product
        ];
    }
}
