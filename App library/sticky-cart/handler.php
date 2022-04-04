<?php
use App\Extensions\BeaeExtension;
use Illuminate\Support\Facades\Storage;
class StickyCartExtension extends BeaeExtension {

    public function install(){
        $res = $this->user->api()->rest('POST','/admin/metafields.json',[
            'metafield' => [
                'namespace' => 'beae',
                'key' => 'sticky_cart',
                'type'    => 'json',
                'value' => @json_encode( $this->settings)
            ]
        ]);

        $upload_assets = $this->uploadAssets();
        if ( ! $upload_assets ) {
            return [
                'status' => 'error',
                'message' => 'Assets upload failer'
            ];
        }

        if($res['errors'])
        {
            return [
                'status' => 'error',
                'message' =>  $this->parseError($res)
            ];
        }
        return ['status' => 'success'];
    }
    public function uninstall(){
        $res =  $this->user->api()->rest('GET','/admin/metafields.json',[

            'namespace' => 'beae',
            'key' => 'sticky_cart',

        ]);

        $response = $this->shopifyAsset->asset(['key' => 'snippets/beae_footer.liquid']);
        if ($response['status'] == 'success') {
            $code = $response['asset']->value;
            $preg = '/<!--\s*?BEGIN-sticky_caRT\s*?-->([\s\S]+)<!--\s*?END-sticky_caRT\s*?-->/m';
            if ( preg_match( $preg, $code) ) {
                $code = preg_replace($preg, '', $code);
                $response = $this->shopifyAsset->upload(
                    [
                        'key' => 'snippets/beae_footer.liquid',
                        'value' => $code
                    ]
                );
                if ( $response['status'] != 'success') {
                    return [
                        'status' => 'error',
                        'message' => 'An error occurred'
                    ];
                }
            }
        }

        if($res && !$res['errors'])
        {
            if(isset( $res['body']->metafields ) && count($res['body']->metafields))
            {
                $metafield_id = $res['body']->metafields[0]->id;
                $this->user->api()->rest('DELETE','/admin/metafields/'.$metafield_id.'.json');
            }
        }
        return ['status' => 'success'];


    }
    public function save(){
        $res = $this->user->api()->rest('POST','/admin/metafields.json',[
            'metafield' => [
                'namespace' => 'beae',
                'key' => 'sticky_cart',
                'type'    => 'json',
                'value' => json_encode($this->settings)
            ]
        ]);
        if($res['errors'])
        {
            return ['status' => 'error', 'message' => $this->parseError($res)];
        }
        return ['status' => 'success'];
    }
    public function edit(){
        $res = $this->user->api()->rest('GET','/admin/metafields.json',[
            'namespace' => 'beae',
            'key' => 'sticky_cart',
        ]);
        if($res && !$res['errors'] && isset($res['body']->metafields) && count($res['body']->metafields))
        {
            $data = @json_decode($res['body']->metafields[0]->value);
            return ['status' => 'success', 'data' => $data];
        }

        return ['status' => 'success'];
    }
    public function parseError($response){
        $errors = [];
        if(is_array($response['body']))
        {
            foreach($response['body'] as $key=> $error)
            {
                $errors[] = $key. " " .$error[0];
            }
        }
        else if($response['exception'])
        {
            $errors[] = $response['exception']->getMessage();
        }
        if(count($errors))
        {
            return implode(', ', $errors);
        }
        return 'Unknow error';
    }

    public function uploadAssets()
    {
        $upload_res = true;

        $response = $this->shopifyAsset->asset(['key' => 'snippets/beae_footer.liquid']);

        if ($response['status'] == 'success') {
            $code = $response['asset']->value;
            if ( ! preg_match( '/{%\-?\s+section\s+(\'|\")beae-sticky_cart(\'|\")\s+\-?%}/m', $code) ) {
                $code .= "\n<!--BEGIN-sticky_caRT-->
                        \n{%- if request.page_type == 'product' -%}
                        \n{%- section 'beae-sticky_cart' -%}
                        \n{%- endif -%}
                        \n<!--END-sticky_caRT-->";

                $response = $this->shopifyAsset->upload(
                    [
                        'key' => 'snippets/beae_footer.liquid',
                        'value' => $code
                    ]
                );
                $upload_res = $response['status'] != 'success' ? false : $upload_res;
            }
        }

        $response = $this->shopifyAsset->upload(
            [
                'key' => 'sections/beae-sticky_cart.liquid',
                'value' => $this->getExtensionAssest()
            ]
         );

         $upload_res = $response['status'] != 'success' ? false : $upload_res;

         return $upload_res;
    }
    public function getExtensionAssest()
    {
        $template = '';
        $path = public_path(__FILE__);
        $asset_name = __DIR__ . '/assets/beae-sticky_cart.liquid';
        if ( file_exists($asset_name) ) {
            $template = file_get_contents($asset_name);
        }

        return $template;
    }

}