<?php 

class AjaxCartExtension{
    protected $user;
    protected $settings;
    public function __construct($user,$settings)
    {
        $this->user = $user;
        $this->settings = $settings;
    }
    
    public function install(){
        $res = $this->user->api()->rest('POST','/admin/metafields.json',[
            'metafield' => [
                'namespace' => 'beae',
                'key' => 'ajax-cart',
                'type'    => 'json',
                'value' => @json_encode( $this->settings)
            ]
        ]);
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
            'key' => 'ajax-cart',
            
        ]);
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
                'key' => 'ajax-cart',
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
            'key' => 'ajax-cart',
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
}