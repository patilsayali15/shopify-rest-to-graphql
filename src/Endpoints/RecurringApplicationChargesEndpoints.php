<?php

namespace Thalia\ShopifyRestToGraphql\Endpoints;

use Thalia\ShopifyRestToGraphql\GraphqlService;

class RecurringApplicationChargesEndpoints
{
    private $graphqlService;

    private $shopDomain;
    private $accessToken;

    public function __construct(string $shopDomain = null, string $accessToken = null)
    {

        if ($shopDomain === null || $accessToken === null) {
            throw new \InvalidArgumentException('Shop domain and access token must be provided.');
        }


        $this->shopDomain = $shopDomain;
        $this->accessToken = $accessToken;

        $this->graphqlService = new GraphqlService($this->shopDomain, $this->accessToken);

    }

    public function appSubscriptionCreate($params)
    {
        /*
        Graphql Reference : https://shopify.dev/docs/api/admin-graphql/2025-01/mutations/appSubscriptionCreate
        Rest Reference : https://shopify.dev/docs/api/admin-rest/2025-01/resources/recurringapplicationcharge#post-recurring-application-charges
        */



        $recurringapplicationcharge = $params['recurring_application_charge'];

        $chargevariables = array();

        if (!empty($recurringapplicationcharge['name'])) {
            $chargevariables['name'] = $recurringapplicationcharge['name'];
        }

        if (!empty($recurringapplicationcharge['return_url'])) {
            $chargevariables['returnUrl'] = $recurringapplicationcharge['return_url'];
        }

        if (!empty($recurringapplicationcharge['test'])) {
            $chargevariables['test'] = $recurringapplicationcharge['test'];
        }

        if (!empty($recurringapplicationcharge['trial_days'])) {
            $chargevariables['trialDays'] = $recurringapplicationcharge['trial_days'];
        }

        $chargevariables['lineItems'] = array();

        if (!empty($recurringapplicationcharge['price'])) {
            $chargevariables['lineItems']['plan']['appRecurringPricingDetails']['price']['amount'] = $recurringapplicationcharge['price'];
            $chargevariables['lineItems']['plan']['appRecurringPricingDetails']['price']['currencyCode'] = 'USD';
        }


        $recurringapplicationchargequery = <<<'GRAPHQL'
    mutation AppSubscriptionCreate($name: String!, $lineItems: [AppSubscriptionLineItemInput!]!, $returnUrl: URL!, $test: Boolean, $trialDays: Int) {
        appSubscriptionCreate(name: $name, returnUrl: $returnUrl, lineItems: $lineItems, test: $test, trialDays: $trialDays) {
            userErrors {
                field
                message
            }
            appSubscription {
                id
            }
            confirmationUrl
        }
    }
    GRAPHQL;

        $responseData = $this->graphqlService->graphqlQueryThalia($recurringapplicationchargequery, $chargevariables);
        $response = array();

        if (isset($responseData['data']['appSubscriptionCreate']['userErrors']) && !empty($responseData['data']['appSubscriptionCreate']['userErrors'])) {

            throw new \Exception('GraphQL Error: ' . print_r($responseData['data']['appSubscriptionCreate']['userErrors'], true));

        } else {

            $response['confirmation_url'] = $responseData['data']['appSubscriptionCreate']['confirmationUrl'];

        }

        return $response;
    }

    public function currentAppInstallationForRecurring($recurringChargeId)
    {
        /*
        Graphql Reference : https://shopify.dev/docs/api/admin-graphql/2025-01/queries/currentAppInstallation?example=Retrieves+a+list+of+recurring+application+charges
        Rest Reference : https://shopify.dev/docs/api/admin-rest/2025-01/resources/recurringapplicationcharge#get-recurring-application-charges
        */



        $getappinstallationquery = <<<'GRAPHQL'
        query {
            currentAppInstallation {
                activeSubscriptions {
                    id
                    name
                    status
                }
            }
        }
        GRAPHQL;

        $responseData = $this->graphqlService->graphqlQueryThalia($getappinstallationquery);


        if (isset($responseData['errors']) && !empty($responseData['errors'])) {

            throw new \Exception('GraphQL Error: ' . print_r($responseData['errors'], true));

        } else {

            $responseData = $responseData['data']['currentAppInstallation']['activeSubscriptions'];

        }

        $recurringChargeId = "gid://shopify/AppSubscription/{$recurringChargeId}";
        $response = array();

        if ($recurringChargeId == $responseData[0]['id']) {

            $response['status'] = 'active';
            $response['message'] = 'The recurring charge is active';

        } else {

            $response['status'] = 'inactive';
            $response['message'] = 'The recurring charge is not active and is in different status';

        }

        return $response;
    }


}
