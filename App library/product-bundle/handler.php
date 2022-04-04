<?php
use App\Extensions\Beae;

class ProductBundleExtension extends BeaeExtension
{
    public function install()
    {
        $metafiedls_data = array();
        $mutation = '';

        for ($i = 1; $i <= 5; $i++) {
            $metafiedls_data['definition_bundel_' . $i] = array(
                "key" => "product_bundle_" . $i,
                "name" => "Beae product bundle",
                "namespace" => "beae",
                "ownerType" => "PRODUCT",
                "type" => "product_reference",
                "description" => "Product bundle"
            );

            $mutation .= 'beaeMetafieldProductBundle' . $i . ': metafieldDefinitionCreate(definition: $definition_bundel_' . $i . ') {
                userErrors {
                    field
                    message
                }
                createdDefinition {
                    id
                    key
                }
            }';
        }

        $response = $this->shopifyAsset->api->graph(
            '
            mutation metafieldDefinitionCreate(
                    $definition_bundel_1: MetafieldDefinitionInput!,
                    $definition_bundel_2: MetafieldDefinitionInput!,
                    $definition_bundel_3: MetafieldDefinitionInput!,
                    $definition_bundel_4: MetafieldDefinitionInput!,
                    $definition_bundel_5: MetafieldDefinitionInput!
                ) {
                ' . $mutation . '
            }

        ', $metafiedls_data);
        if ( $response['errors'] ) {
            return [
                'status' => 'error',
                'message' => 'An error occurred'
            ];
        }

        return ['status' => 'success'];
    }
    public function uninstall()
    {
        $metafiedls_ids = array();
        $mutation = '';

        $response = $this->shopifyAsset->api->graph(
            '
            {
                metafieldDefinitions(first: 50, ownerType: PRODUCT, query:"key:product_bundle*") {
                  edges {
                    node {
                      id
                      namespace
                      name
                      key
                    }
                  }
                }
              }
            '
        );

        if( ! $response['errors'] ) {
            $response_data = $response['body']['data']['metafieldDefinitions']['edges'];
            if( $response_data ) {
                foreach( $response_data as $i => $key ) {
                    $metafiedls_ids['id' . $i] = $key['node']['id'];
                    $mutation .= 'beaeMetafieldProductBundle' . $i . ':metafieldDefinitionDelete(id: $id' . $i . ') {
                        deletedDefinitionId
                        userErrors {
                          field
                          message
                        }
                    }';
                }
            }
            if ( count( $metafiedls_ids ) > 0 ) {
                $response = $this->shopifyAsset->api->graph(
                    'mutation metafieldDefinitionDelete($id0: ID!, $id1: ID!, $id2: ID!, $id3: ID!, $id4: ID!) {
                        ' . $mutation . '
                    }',
                    $metafiedls_ids
                );
                if ( ! $response['errors'] ) {
                    return ['status' => 'success'];
                }
            }
        }
        return [
            'status' => 'error',
            'message' => 'An error occurred'
        ];
    }
}
