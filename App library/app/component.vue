<template>
    <div>
        <h3 v-html="data?.settings?.title"></h3>
        <p v-html="data?.settings?.text">
        </p>
    </div>
</template>
<script>
export default {
    name: "The app",
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
                },
                {
                    type: 'textarea',
                    name:'text',
                    label: 'Text'
                }
            ]
        },
        
        javascript(){
            return function(){
                const $el = this.$el;
             
                console.log($el);


            }
        },
        default(){
            return {
                settings: {
                title: 'Ai cha cha',
                text: 'Lorem ispum'
            }
            }
        }
    },
    mounted() {
       
    },
}
</script>