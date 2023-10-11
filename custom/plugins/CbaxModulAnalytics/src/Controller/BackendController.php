<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Controller;

use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Cbax\ModulAnalytics\Components\Statistics\Connector;
use Cbax\ModulAnalytics\Components\Base;

class BackendController extends AbstractController
{
    /**
    * Connector
    */
    private $connector;

    /**
     * Base
     */
    private $base;

    public function __construct(Connector $connector, Base $base)
    {
        $this->connector = $connector;
        $this->base = $base;
    }

    /**
     * @Route("/api/cbax/analytics/getOrdersCountAll", name="api.cbax.analytics.getOrdersCountAll",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getOrdersCountAllAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getOrdersCountAll($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesAll", name="api.cbax.analytics.getSalesAll",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesAllAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getSalesAll($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByMonth", name="api.cbax.analytics.getSalesByMonth",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByMonthAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByMonth($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByPayment", name="api.cbax.analytics.getSalesByPayment",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByPaymentAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByPayment($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByShipping", name="api.cbax.analytics.getSalesByShipping",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByShippingAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByShipping($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByManufacturer", name="api.cbax.analytics.getSalesByManufacturer",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByManufacturerAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByManufacturer($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByProducts", name="api.cbax.analytics.getSalesByProducts",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByProductsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByProducts($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getCountByProducts", name="api.cbax.analytics.getCountByProducts",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getCountByProductsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getCountByProducts($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByCountry", name="api.cbax.analytics.getSalesByCountry",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByCountryAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByCountry($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByBillingCountry", name="api.cbax.analytics.getSalesByBillingCountry",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByBillingCountryAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByBillingCountry($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByLanguage", name="api.cbax.analytics.getSalesByLanguage",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByLanguageAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByLanguage($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesBySaleschannels", name="api.cbax.analytics.getSalesBySaleschannels",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesBySaleschannelsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesBySaleschannels($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByAffiliates", name="api.cbax.analytics.getSalesByAffiliates",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByAffiliatesAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByAffiliates($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByCampaign", name="api.cbax.analytics.getSalesByCampaign",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByCampaignAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByCampaign($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByCustomergroups", name="api.cbax.analytics.getSalesByCustomergroups",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByCustomergroupsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByCustomergroups($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByWeekdays", name="api.cbax.analytics.getSalesByWeekdays",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByWeekdaysAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByWeekdays($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByTime", name="api.cbax.analytics.getSalesByTime",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByTimeAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByTime($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getOrdersByStatus", name="api.cbax.analytics.getOrdersByStatus",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getOrdersByStatusAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getOrdersByStatus($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getProductLowInstock", name="api.cbax.analytics.getProductLowInstock",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductLowInstockAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getProductLowInstock($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getProductHighInstock", name="api.cbax.analytics.getProductHighInstock",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductHighInstockAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getProductHighInstock($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByPromotion", name="api.cbax.analytics.getSalesByPromotion",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByPromotionAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByPromotion($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByPromotionOthers", name="api.cbax.analytics.getSalesByPromotionOthers",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByPromotionOthersAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByPromotionOthers($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getProductInactiveWithInstock", name="api.cbax.analytics.getProductInactiveWithInstock",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductInactiveWithInstockAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getProductInactiveWithInstock($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getProductByOrders", name="api.cbax.analytics.getProductByOrders",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductByOrdersAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getProductByOrders($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByCustomer", name="api.cbax.analytics.getSalesByCustomer",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByCustomerAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByCustomer($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getNewCustomersByTime", name="api.cbax.analytics.getNewCustomersByTime",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getNewCustomersByTimeAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getNewCustomersByTime($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getCustomerAge", name="api.cbax.analytics.getCustomerAge",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getCustomerAgeAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getCustomerAge($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getCustomerOnline", name="api.cbax.analytics.getCustomerOnline",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getCustomerOnlineAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getCustomerOnline($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "gridData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getProductSoonOutstock", name="api.cbax.analytics.getProductSoonOutstock",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductSoonOutstockAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getProductSoonOutstock($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getOrdersByTransactionStatus", name="api.cbax.analytics.getOrdersByTransactionStatus",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getOrdersByTransactionStatusAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getOrdersByTransactionStatus($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getOrdersByDeliveryStatus", name="api.cbax.analytics.getOrdersByDeliveryStatus",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getOrdersByDeliveryStatusAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getOrdersByDeliveryStatus($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getQuickOverview", name="api.cbax.analytics.getQuickOverview",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getQuickOverviewAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getQuickOverview($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "gridData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getUnfinishedOrders", name="api.cbax.analytics.getUnfinishedOrders",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getUnfinishedOrdersAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getUnfinishedOrders($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "gridData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getUnfinishedOrdersByPayment", name="api.cbax.analytics.getUnfinishedOrdersByPayment",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getUnfinishedOrdersByPaymentAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getUnfinishedOrdersByPayment($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getUnfinishedOrdersByCart", name="api.cbax.analytics.getUnfinishedOrdersByCart",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getUnfinishedOrdersByCartAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getUnfinishedOrdersByCart($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getCanceledOrdersByMonth", name="api.cbax.analytics.getCanceledOrdersByMonth",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getCanceledOrdersByMonthAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getCanceledOrdersByMonth($parameters, $context);

        if ($parameters['format'] === 'csv') {

            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getSearchTerms", name="api.cbax.analytics.getSearchTerms",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSearchTermsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSearchTerms($parameters, $context);

        if ($parameters['format'] === 'csv') {

            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "gridData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getSearchActivity", name="api.cbax.analytics.getSearchActivity",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSearchTermsActivityAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSearchActivity($parameters, $context);

        if ($parameters['format'] === 'csv') {

            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getSearchTrends", name="api.cbax.analytics.getSearchTrends",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSearchTermsTrendsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSearchTrends($parameters, $context);

        if ($parameters['format'] === 'csv') {

            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByDevice", name="api.cbax.analytics.getSalesByDevice",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByDeviceAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByDevice($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByOs", name="api.cbax.analytics.getSalesByOs",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByOsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByOs($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByBrowser", name="api.cbax.analytics.getSalesByBrowser",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByBrowserAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByBrowser($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getProductsProfit", name="api.cbax.analytics.getProductsProfit",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductsProfitAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);
        $parameters['sorting'] = explode('-', trim($request->query->get('sorting','')));

        $result = $this->connector->getProductsProfit($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "overall" => $result['overall'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getProductsInventory", name="api.cbax.analytics.getProductsInventory",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductsInventoryAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);
        $parameters['sorting'] = explode('-', trim($request->query->get('sorting','')));

        $result = $this->connector->getProductsInventory($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "overall" => $result['overall'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getVariantsCompare", name="api.cbax.analytics.getVariantsCompare",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getVariantsCompareAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);
        $parameters['propertyGroupId'] = trim($request->query->get('propertyGroupId',''));
        $parameters['categoryId'] = trim($request->query->get('categoryId',''));

        $result = $this->connector->getVariantsCompare($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "overall" => $result['overall'], "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getProductStream", name="api.cbax.analytics.getProductStream",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})

    public function getProductStreamAction(Request $request, Context $context)
    {
        $salesChannelId = $request->query->get('salesChannelId','');
        $adminLocalLanguage = trim($request->query->get('adminLocaleLanguage',''));
        $format = trim($request->query->get('format',''));
        $labels = trim($request->query->get('labels',''));
        $sorting = explode('-', trim($request->query->get('sorting','')));
        $productStreamId = trim($request->query->get('productStreamId',''));

        $dates = $this->base->getDates($request);

        $startDate = $dates['startDate'];
        $endDate = $dates['endDate'];

        $result = $this->connector->getProductStream($startDate, $endDate, $salesChannelId, $context, $adminLocalLanguage, $format, $labels, $productStreamId, $sorting);

        if ($format === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "overall" => $result['overall'], "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }
     * */

    /**
     * @Route("/api/cbax/analytics/getSalesByProductsFilter", name="api.cbax.analytics.getSalesByProductsFilter",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByProductsFilterAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);
        $parameters['productStreamId'] = trim($request->query->get('productStreamId',''));

        $result = $this->connector->getSalesByProductsFilter($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "overall" => $result['overall'], "overallCount" => $result['overallCount'], "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/download", name="api.cbax.analytics.download", defaults={"auth_required"=false, "_routeScope"={"administration"}}, methods={"GET"})
     */
    public function download(Request $request, Context $context)
    {
        $params = $request->query->all();
        $fileName = $params['fileName'];
        $fileSize = $params['fileSize'];

        return $this->base->getDownloadResponse($fileName, $fileSize);
    }

    /**
     * @Route("/api/cbax/analytics/getProductImpressions", name="api.cbax.analytics.getProductImpressions",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getProductImpressionsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getProductImpressions($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData'], "overall" => $result['overall']));
    }

    /**
     * @Route("/api/cbax/analytics/getVisitors", name="api.cbax.analytics.getVisitors",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getVisitorsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getVisitors($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData']));
    }

    /**
     * @Route("/api/cbax/analytics/getVisitorImpressions", name="api.cbax.analytics.getVisitorImpressions",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getVisitorImpressionsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getVisitorImpressions($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData']));
    }

    /**
     * @Route("/api/cbax/analytics/getReferer", name="api.cbax.analytics.getReferer",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getRefererAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getReferer($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "gridData" => $result['gridData']));
    }

    /**
     * @Route("/api/cbax/analytics/getCategoryImpressions", name="api.cbax.analytics.getCategoryImpressions",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getCategoryImpressionsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getCategoryImpressions($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData'], "overall" => $result['overall']));
    }

    /**
     * @Route("/api/cbax/analytics/getManufacturerImpressions", name="api.cbax.analytics.getManufacturerImpressions",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getManufacturerImpressionsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getManufacturerImpressions($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData'], "overall" => $result['overall']));
    }

    /**
     * @Route("/api/cbax/analytics/getLexiconImpressions", name="api.cbax.analytics.getLexiconImpressions",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getLexiconImpressionsAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getLexiconImpressions($parameters, $context);

        if (empty($result))
        {
            return new JsonResponse(array("success" => false));
        }

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData'], "overall" => $result['overall']));
    }

    /**
     * @Route("/api/cbax/analytics/getSingleProduct", name="api.cbax.analytics.getSingleProduct",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSingleProductAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);
        $parameters['productId'] = $request->query->get('productId','');
        $parameters['compareIds'] = $request->query->get('compareIds','');

        $result = $this->connector->getSingleProduct($parameters, $context);

        /*
        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }
        */

        return new JsonResponse(array(
            "success" => true,
            "seriesData" => $result['seriesData'],
            "productName" => $result['productName'],
            "seriesCompareData" => $result['seriesCompareData'],
            "compareProductNames" => $result['compareProductNames'],
            "gridData" => $result['gridData']
        ));
    }

    /**
     * @Route("/api/cbax/analytics/getCrossSelling", name="api.cbax.analytics.getCrossSelling",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getCrossSellingAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);
        $parameters['productId'] = $request->query->get('productId', '');

        $result = $this->connector->getCrossSelling($parameters, $context);

        if (empty($result))
        {
            return new JsonResponse(array("success" => false));
        }

        /*
        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }
        */

        return new JsonResponse(array(
            "success" => true,
            "productName" => $result['productName'],
            'alsoViewed' => $result['alsoViewed'],
            'alsoBought' => $result['alsoBought']
        ));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByTaxrate", name="api.cbax.analytics.getSalesByTaxrate",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByTaxrateAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByTaxrate($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesBySalutation", name="api.cbax.analytics.getSalesBySalutation",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesBySalutationAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesBySalutation($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData']));
    }

    /**
     * @Route("/api/cbax/analytics/getCustomerBySalutation", name="api.cbax.analytics.getCustomerBySalutation",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getCustomerBySalutationAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getCustomerBySalutation($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData']));
    }

    /**
     * @Route("/api/cbax/analytics/getSalesByCurrency", name="api.cbax.analytics.getSalesByCurrency",  methods={"GET"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    public function getSalesByCurrencyAction(Request $request, Context $context)
    {
        $parameters = $this->base->getBaseParameters($request);

        $result = $this->connector->getSalesByCurrency($parameters, $context);

        if ($parameters['format'] === 'csv') {
            return new JsonResponse(array("success" => true, "fileSize" => $result));
        }

        return new JsonResponse(array("success" => true, "seriesData" => $result['seriesData'], "gridData" => $result['gridData']));
    }
}
