<?php
namespace Acme\Bundle\DemoFlexibleEntityBundle\Controller;

use Oro\Bundle\AddressBundle\Entity\Address;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Oro\Bundle\FlexibleEntityBundle\Manager\FlexibleManager;
use Acme\Bundle\DemoFlexibleEntityBundle\Form\Type\CustomerType;
use Acme\Bundle\DemoFlexibleEntityBundle\Entity\Customer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use YsTools\BackUrlBundle\Annotation\BackUrl;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

/**
 * Customer entity controller
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 *
 * @Route("/customer")
 */
class CustomerController extends Controller
{

    /**
     * Get attribute codes
     * @return array
     */
    protected function getAttributeCodesToDisplay()
    {
        return array('company', 'dob', 'gender', 'website', 'hobby');
    }

    /**
     * Get customer manager
     * @return FlexibleManager
     */
    protected function getCustomerManager()
    {
        return $this->container->get('customer_manager');
    }

    /**
     * @Route("/list.{_format}",
     *      name="acme_demoflexibleentity_customer_list",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     */
    public function listAction(Request $request)
    {
        /** @var $gridManager CustomerDatagridManager */
        $gridManager = $this->get('customer_grid_manager');
        $datagrid = $gridManager->getDatagrid();

        if ('json' == $request->getRequestFormat()) {
            $view = 'OroGridBundle:Datagrid:list.json.php';
        } else {
            $view = 'AcmeDemoFlexibleEntityBundle:Customer:list.html.twig';
        }

        return $this->render(
            $view,
            array(
                'datagrid' => $datagrid,
                'form'     => $datagrid->getForm()->createView()
            )
        );
    }

    /**
     * @Route("/index")
     * @Template("AcmeDemoFlexibleEntityBundle:Customer:index.html.twig")
     *
     * @return array
     */
    public function indexAction()
    {
        // explicit attribute code list means only load values for this attributes
        $attributes = $this->getAttributeCodesToDisplay();

        return $this->queryAction(implode('&', $attributes), null, null, null, null);
    }

    /**
     * Query customers
     *
     * @param string $attributes attribute codes
     * @param string $criteria   criterias
     * @param string $orderBy    order by
     * @param int    $limit      limit
     * @param int    $offset     offset
     *
     * @Route(
     *     "/query/{attributes}/{criteria}/{orderBy}/{limit}/{offset}",
     *     defaults={"attributes" = null, "criteria" = null, "orderBy" = null, "limit" = null, "offset" = null}
     * )
     *
     * @Template("AcmeDemoFlexibleEntityBundle:Customer:index.html.twig")
     *
     * @return array
     */
    public function queryAction($attributes, $criteria, $orderBy, $limit, $offset)
    {
        // prepare params
        if (!is_null($attributes) and $attributes !== 'null') {
            $attributes = explode('&', $attributes);
        } else {
            $attributes = array();
        }
        if (!is_null($criteria) and $criteria !== 'null') {
            parse_str($criteria, $criteria);
        } else {
            $criteria = array();
        }
        if (!is_null($orderBy) and $orderBy !== 'null') {
            parse_str($orderBy, $orderBy);
        } else {
            $orderBy = array();
        }

        // get entities
        $customers = $this->getCustomerManager()->getFlexibleRepository()->findByWithAttributes(
            $attributes,
            $criteria,
            $orderBy,
            $limit,
            $offset
        );

        return array('customers' => $customers);
    }

    /**
     * @Route("/query-lazy-load")
     * @Template("AcmeDemoFlexibleEntityBundle:Customer:index.html.twig")
     *
     * @return multitype
     */
    public function queryLazyLoadAction()
    {
        $customers = $this->getCustomerManager()->getFlexibleRepository()->findBy(array());

        return array('customers' => $customers);
    }

    /**
     * @param integer $id
     *
     * @Route("/show/{id}")
     * @Template()
     *
     * @return multitype
     */
    public function showAction($id)
    {
        // with any values
        $customer = $this->getCustomerManager()->getFlexibleRepository()->findWithAttributes($id);

        return array('customer' => $customer);
    }

    /**
     * Create customer
     *
     * @Route("/create")
     * @Template("AcmeDemoFlexibleEntityBundle:Customer:edit.html.twig")
     *
     * @return multitype
     */
    public function createAction()
    {
        $entity = $this->getCustomerManager()->createFlexible();

        return $this->editAction($entity);
    }

    /**
     * Edit customer
     *
     * @param Customer $entity
     *
     * @Route("/edit/{id}", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     *
     * @return multitype
     */
    public function editAction(Customer $entity)
    {
        $request = $this->getRequest();

        // create form
        $entClassName = $this->getCustomerManager()->getFlexibleName();
        $valueClassName = $this->getCustomerManager()->getFlexibleValueName();
        $form = $this->createForm(new CustomerType($entClassName, $valueClassName), $entity);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);

            if ($form->isValid()) {
                $em = $this->getCustomerManager()->getStorageManager();
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'Customer successfully saved');

                return $this->redirect($this->generateUrl('acme_demoflexibleentity_customer_list'));
            }
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * Remove customer
     * @param Customer $entity
     *
     * @Route("/remove/{id}", requirements={"id"="\d+"})
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeAction(Customer $entity)
    {
        $em = $this->getCustomerManager()->getStorageManager();
        $em->remove($entity);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Customer successfully removed');

        return $this->redirect($this->generateUrl('acme_demoflexibleentity_customer_list'));
    }

    /**
     * Create address form
     *
     * @Template
     * @Route("/create_address", name="oro_address_create")
     */
    public function createAddressAction()
    {
        /** @var  $addressManager \Oro\Bundle\AddressBundle\Entity\Manager\AddressManager */
        $addressManager = $this->get('oro_address.address.manager');

        $account = $addressManager->createFlexible();

        return $this->editAddressAction($account);
    }

    /**
     * Edit address form
     *
     * @Route("/edit_address/{id}", name="oro_address_edit", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template("AcmeDemoFlexibleEntityBundle:Customer:createAddress.html.twig")
     * @BackUrl("back")
     */
    public function editAddressAction(Address $entity)
    {
        if ($this->get('oro_address.form.handler.address')->process($entity)) {
            $backUrl = $this->getRedirectUrl($this->generateUrl('oro_address_edit', array('id' => $entity->getId())));

            $this->getFlashBag()->add('success', 'Address successfully saved');
            return $this->redirect($backUrl);
        }

        return array(
            'form' => $this->get('oro_address.form.address')->createView(),
        );
    }

    /**
     * Get redirect URLs
     *
     * @param  string $default
     * @return string
     */
    protected function getRedirectUrl($default)
    {
        $flashBag = $this->getFlashBag();
        if ($this->getRequest()->query->has('back')) {
            $backUrl = $this->getRequest()->get('back');
            $flashBag->set('backUrl', $backUrl);
        } elseif ($flashBag->has('backUrl')) {
            $backUrl = $flashBag->get('backUrl');
            $backUrl = reset($backUrl);
        } else {
            $backUrl = null;
        }

        return $backUrl ? $backUrl : $default;
    }

    /**
     * @return FlashBag
     */
    protected function getFlashBag()
    {
        return $this->get('session')->getFlashBag();
    }
}
