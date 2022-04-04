<?php 

class SwatchColorsExtension{
    protected $user;
    protected $settings;
    public function __construct($user,$settings)
    {
        $this->user = $user;
        $this->settings = $settings;
    }
    
    public function install(){
        $colors = [];
        $data = $this->settings->colors;
        if(is_array(($data)))
        {
            foreach($data as $color)
            {
                if(isset($color->value) &&  isset($color->value->native) && is_array($color->value->native))
                {
                    $tmp = implode(';', $color->value->native);
                    $colors[strtolower($color->name)] = $tmp;
                }
            }
        }
        $res = $this->user->api()->rest('POST','/admin/metafields.json',[
            'metafield' => [
                'namespace' => 'beae',
                'key' => 'colors',
                'type'    => 'json',
                'value' => @json_encode($colors )
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
            'key' => 'colors',
            
        ]);
        if($res && !$res['errors'])
        {
            if(isset( $res['body']->metafields ) && count($res['body']->metafields))
            {
                $metafield_id = $res['body']->metafields[0]->id;
                $this->user->api()->rest('DELETE','/admin/metafields/'.$metafield_id.'.json');
            }
        }
        $this->user->options()->where(['key'=>'colors'])->delete();
        return ['status' => 'success'];


    }
    public function save(){
        if(!isset($this->settings['colors']))
        {
            return [
                'status' => 'error',
                'message' => 'Colors is missing'
            ];
        }
        $colors = [];
        $data = $this->settings['colors'];
        if(is_array(($data)))
        {
            foreach($data as $color)
            {
                if(isset($color['value']) && is_array($color['value']) && isset($color['value']['native']) && is_array($color['value']['native']))
                {
                    $tmp = implode(';', $color['value']['native']);
                    
                    $colors[strtolower($color['name'])] =  $tmp;
                }
            }
        }
        $res = $this->user->api()->rest('POST','/admin/metafields.json',[
            'metafield' => [
                'namespace' => 'beae',
                'key' => 'colors',
                'type'    => 'json',
                'value' => json_encode($colors)
            ]
        ]);
        if($res['errors'])
        {
            return ['status' => 'error', 'message' => $this->parseError($res)];
        }
        $this->user->options()->updateOrCreate(['key' => 'colors'],['value'=> @json_encode( $this->settings['colors'])]);
        
        return ['status' => 'success'];
    }
    public function edit(){
        $option = $this->user->options()->where(['key'=>'colors'])->first();
        if($option && $option->id)
        {
            return [
                'status' => 'success',
                'data' => [
                    'colors' => $option->value_json
                ]
            ];
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