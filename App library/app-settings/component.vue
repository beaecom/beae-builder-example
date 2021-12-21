<template>
    <div>
        <h3 v-html="data?.settings?.title"></h3>
        <p>
            Lorem ipsum dolor, sit amet consectetur adipisicing elit. Minus molestiae ea neque, a alias dicta beatae reprehenderit necessitatibus asperiores ipsa nesciunt optio qui tempore animi enim sint! Quos, placeat similique!
        </p>
        <h3 v-html="liquid('setting_title')"></h3>
        
        <component :is="'script'" class="beae-data" type="application/json"  v-html="liquid('settings')" /> 
    </div>
</template>
<script>
export default {
    name: "My app",
    props: {
        data: {
            type: Object,
            default() {
                return {};
            }
        }
    },
    computed: {
        settings(){
            return [
                {
                    type: 'text',
                    name:'title',
                    label: 'title'
                }
            ]
        },
        liquids(){
            return {
                settings: { 
                        // my-app: the app slug 
                    code: `{{shop.metafields.beae.my-app.value | json}}`,
                    preview:''
                },
                setting_title:{  // my-app: the app slug 
                                //title: the setting name in settings.json
                    code: `{{shop.metafields.beae.my-app.value.title}}`,
                    preview: ''
                }
            }
        },
        javascript(){
            return function(){
                const $el = this.$el;
                const app_settings  = $el.querySelector('.beae-data');
                console.log(app_settings.innerHTML);


            }
        },
        default(){
            return {
                settings:  {
                title: 'Ai cha cha'
            }
            }
        }
    },
    mounted() {
        
    },
}
</script>