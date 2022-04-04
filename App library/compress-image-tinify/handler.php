<?php 
use Illuminate\Support\Arr;
class TinifyCompressImageExtension{
    protected $user;
    protected $settings;
    public function __construct($user,$settings)
    {
        $this->user = $user;
        $this->settings = $settings;
    }
    
    
    public function uninstall(){
        $this->user->options()->where(['key' => 'tinify'])->delete();
        cache()->tags(['tinify'])->forget('tinify_'. $this->user->id);
        return ['status' => 'success'];


    }
    public function save(){
        if((!isset($this->settings['tinify_api']) || !$this->settings['tinify_api']) && $this->settings['enable'])
        {
            return response()->json([
                'status'    => 'error',
                'message'   => 'TinyPNG API KEY missing'
            ]);
        }
        try{
            \Tinify\setKey($this->settings['tinify_api']);
            \Tinify\validate();
        }
        catch(\Tinify\Exception $e)
        {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Validation of API key failed. Pleae check your api key'
            ]);
        }
        $this->user->options()->updateOrCreate(['key' => 'tinify'], ['value' => @json_encode(Arr::only($this->settings, ['enable', 'tinify_api']))]);
        cache()->tags(['tinify'])->forget('tinify_'. $this->user->id);
        return ['status' => 'success'];
    }
    public function edit(){
        $options = $this->user->options()->where(['key' => 'tinify'])->first();
        if($options)
        {
            return ['status' => 'success', 'data' => $options->value_json];
        }
        return ['status' => 'success'];
    }
   
}