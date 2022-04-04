<template>
    <div class="beae-element beae-products-bundle beae-container">
        <div class="beae-product-bundle__content beae__row beae-flex">
            <div
                class="beae-product-bundle__content-list beae-col-auto beae-is-flex beae-flex"
                :class="{'beae-column': data?.settings?.layout == 'vertical'}"
                v-html="liquid('products_render')"
            />
            <div class="beae-product-bundle__atc-box beae-col-auto beae-flex beae-fl_center">
                <p class="beae-product-bundle__total-price">
                    <span class="beae-product-bundle__price--total_title" v-html="lang('Total price:', 'total_price')" />
                    <span class="beae-product-bundle__price--total"></span>
                </p>
                <button class="beae-button beae-product-bundle__atc-btn"><span v-html="lang(data?.settings?.atc_text, 'product_bundle_atc')" /></button>
            </div>
        </div>
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
                    name:'atc_text',
                    label: 'Add to cart text'
                },
                {
                    name: 'products',
                    type: 'picker',
                    label: 'Select products',
                    options: {
                        type: 'product',
                        multiple: true
                    }
                 },
                 {
                    name: 'layout',
                    type: 'dropdown',
                    label: 'Layout type',
                    value: 'horizontal',
                    options: {
                        default: false,
                        values: {
                            horizontal: 'Horizontal',
                            vertical: 'Vertical',
                        }
                    }
                }
            ]
        },

        requestShopifyType() {
            return {
                shopify_type: 'product'
            };
        },

        javascript(){
            return function(){
                const $el = this.$el;
                const products_item = $el.querySelectorAll('.beae-product-bundle__item');
                if (products_item.leng == 0) {
                    return;
                }

                const handleVariantSelect = () => {
                    if ( products_item ) {
                        products_item.forEach( (item, index) => {
                            let select_id = item.querySelector('select[name=beae-product-bundle-select]');
                            let item_image =  item.querySelector('.beae-product-bundle__image');

                            if ( ! select_id ) return;
                            select_id.addEventListener('change', (e) => {
                                let selected_index = select_id.options[select_id.selectedIndex];
                                if ( ! selected_index ) return;

                                let price = selected_index.getAttribute('data-price');
                                let compare_at_price = selected_index.getAttribute('data-compare-price') || 0;
                                let image = selected_index.getAttribute('data-image');
                                let product_id = selected_index.value;

                                item.querySelector('input[name=product_id]').value = product_id;
                                item.querySelector('.product-bundle__checkbox-input').setAttribute('data-product-price', price);
                                handleTotalPrice();
                                updatePrice(price, compare_at_price, item);


                                item_image.classList.add('beae-loading-image');
                                item_image.querySelector('img').setAttribute('src', image);
                                item_image.querySelector('img').onload = () => {
                                    item_image.classList.remove('beae-loading-image');
                                }

                            })

                        });
                    }

                    function updatePrice(price, compare_at_price, el) {
                        const $price_regular = el.querySelector(
                            '.beae-product-bundle__price--regular'
                        );
                        const $price = el.querySelector('.beae-product-bundle__price--sale');

                        if ($price) {
                            $price.innerHTML = window.Beae.formatMoney(price);
                        }

                        if ($price_regular) {
                            $price_regular.innerHTML = window.Beae.formatMoney(
                                compare_at_price
                            );
                            if (compare_at_price > price) {
                                $price_regular.style.display = 'inherit';
                            } else {
                                $price_regular.style.display = 'none';
                            }
                        }
                    }
                }

                const handleMainProductVariantSelect= () => {
                    if ( products_item.length == 0 ) return;
                    const product_form = document.querySelector('form.beae-product-form--single');
                    const main_select = product_form.querySelector('select[name=id]');
                    const main_product_bundle = products_item[0].querySelector('select[name=beae-product-bundle-select]');

                    if ( ! main_select || ! main_product_bundle ) return;
                    //Main select event
                    main_select.addEventListener('change', (e) => {
                    let value = e.target.value;
                    if (value && value !=  main_product_bundle.value) {
                        main_product_bundle.value = value;
                        main_product_bundle.dispatchEvent(new Event('change'));
                    }
                    })

                    //Bundle main product selet event
                    main_product_bundle.addEventListener('change', (e) => {
                    let value = e.target.value;
                    if (value) {
                        main_select.value = value;
                        main_select.dispatchEvent(new Event('swatch'));
                    }
                    })
                }

                const handleATC = ()=> {
                    const $btn_atc = $el.querySelector('.beae-product-bundle__atc-btn');
                    let items = [];

                    $btn_atc.addEventListener('click', (e) => {
                        e.preventDefault();
                        const _this = e.target;
                        $btn_atc.classList.add('beae-loading');

                        $el.querySelectorAll('input[type=checkbox]').forEach( (checkbox, index) => {
                            if (checkbox.checked) {
                            let product_id = products_item[index].querySelector('input[name=product_id]').value;
                            if (!product_id) return;

                            let item = {
                                'id': product_id,
                                'quantity': 1
                            };

                            items.push(item);
                            }
                        });

                        if(items.length > 0) {
                        fetch('/cart/add.js', {
                            method: 'POST',
                            headers: {
                            'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ items })
                        })
                        .then(response => {
                            if ( window.Beae.ajaxCartSuccess )
                            window.Beae.ajaxCartSuccess(response);
                            $btn_atc.classList.remove('beae-loading');
                        })
                        .catch((error) => {
                            console.error('Error:', error);
                        });
                        }
                    })
                }

                const handleTotalPrice = () => {
                    let total_price = 0;
                    const checkboxs = $el.querySelectorAll('input[type=checkbox]');

                    if ( ! checkboxs ) return;

                    checkboxs.forEach( (checkbox, index) => {
                        if (checkbox.checked) {
                            let price = checkbox.getAttribute('data-product-price');
                            if (price)
                                total_price += parseInt(price);
                        }
                    })

                    $el.querySelector('.beae-product-bundle__price--total').innerHTML = window.Beae.formatMoney(total_price);
                }

                handleVariantSelect();
                handleMainProductVariantSelect();
                handleTotalPrice();
                if(!window.ECOM_LIVE)
                    handleATC();
            }
        },
        default(){
            return {
                settings: {
                    heading: 'Products bundle',
                    atc_text: 'Add selected item\'s'
                },
                style: {
                    add_to_cart_button: {
                        buttonBackgroundnormalmode: {
                            classic: {
                                "background-color": "rgba(5, 150, 105,1)"
                            }
                        },
                    }
                }
            }
        },
        liquids() {
            return {
                heading: {
                    code: `${this.data?.settings?.heading}`,
                    preview: `
                        <div class="beae-skeleton-item">
                            <div class="beae-skeleton-col-12">
                                <div class="beae-skeleton-row">
                                    <div class="beae-skeleton-col-9 beae-skeleton-big"></div>
                                </div>
                            </div>
                        </div>
                    `
                },
                products_render: {
                    code: `
                    {%- capture product_handles -%}
                            ${this.handles}
                    {%- endcapture -%}
                    {%- capture current_product_handle -%}{{product.handle}}{%- endcapture -%}
                    {%- liquid
                        assign product_bundle = product.metafields.beae
                        assign product_handles_metaf = ''
                        unless product_bundle.size == 0
                            for i in (1..5)
                                assign meta_name = 'product_bundle_' | append: i
                                if product_bundle[meta_name] != blank
                                    assign product_handles_metaf = product_handles_metaf | append: ',' | append: product_bundle[meta_name].value.handle
                                endif
                            endfor
                        endunless

                        if product_handles_metaf == blank
                            assign product_handles = product_handles | replace: current_product_handle, ''| prepend: ',' | prepend: product.handle
                        else
                            assign product_handles = product_handles_metaf | replace: current_product_handle, '' | prepend: ',' | prepend: product.handle
                        endif

                        assign product_handles = product_handles | split: ","
                    -%}
                        {%- for product_handle in product_handles -%}
                        {%- if product_handle != blank -%}
                        {%- assign product = all_products[product_handle] -%}
                        {%- assign target = product.selected_or_first_available_variant -%}
                            <div class="beae-product-bundle__item">
                                <div class="beae-product-bundle__item-content">
                                    <a href="{{ product.url }}" title="{{ product.title | escape }}" class="beae-product-bundle__image">
                                        <img src="{{ product.featured_image | img_url: '100x' }}" alt="{{ product.featured_image.alt }}" />
                                    </a>
                                </div>
                                <h3 class="beae-product-bundle__title">
                                    <a href="{{ product.url }}" title="{{ product.title | escape }}" class="beae-product-bundle__url">{{ product.title }}</a>
                                </h3>
                                <div class="beae-product-bundle__price beae-flex beae-fl_center">
                                    <div class="beae-product-bundle__price--sale">{{ target.price| money }}</div>
                                    <div class="beae-product-bundle__price--regular beae-ml__15"
                                        {% unless product.price_varies %} style="display:none" {% endunless %}
                                    >
                                        {{ target.compare_at_price | money }}
                                    </div>
                                </div>
                                <div class="beae-product-bundle__checkbox beae-flex beae-fl_center">
                                    <input type="hidden" name="product_id" value="{{ target.id }}" />
                                    <label class="beae-pr product-bundle__checkbox-label">
                                        <input type="checkbox" class="product-bundle__checkbox-input" data-product-price="{{ target.price }}" checked/>
                                    </label>
                                    {%- unless product.has_only_default_variant -%}
                                        <select id="beae-product-bundle-select-{{ product.handle }}" name="beae-product-bundle-select">
                                            {%- for variant in product.variants -%}
                                                <option
                                                    value="{{ variant.id }}"
                                                    data-price="{{ variant.price }}"
                                                    {%- if variant.compare_at_price -%} data-compare-price="{{ variant.compare_at_price }}"{%- endif -%}
                                                    data-image="{{ variant.image | img_url: '100x' }}"
                                                {% if variant == product.selected_or_first_available_variant %}selected="selected"{% endif %}
                                                >
                                                {{ variant.title }}
                                                </option>
                                            {%- endfor- %}
                                        </select>
                                    {%- endunless -%}
                                </div>
                            </div>
                            {%- endif -%}
                        {%- endfor -%}
                        `,
                    preview: `<div class="beae-skeleton-item beae__row">
                                <div class="beae-skeleton-col-12 beae-col-3">
                                    <div class="beae-skeleton-row">
                                        <div class="beae-skeleton-col-12"></div>
                                        <div class="beae-skeleton-col-2"></div>
                                        <div class="beae-skeleton-col-10 beae-skeleton-empty"></div>
                                        <div class="beae-skeleton-col-8 beae-skeleton-big"></div>
                                        <div class="beae-skeleton-col-4 beae-skeleton-big beae-skeleton-empty"></div>
                                    </div>
                                </div>
                                <div class="beae-skeleton-col-12 beae-col-3">
                                    <div class="beae-skeleton-row">
                                        <div class="beae-skeleton-col-12"></div>
                                        <div class="beae-skeleton-col-2"></div>
                                        <div class="beae-skeleton-col-10 beae-skeleton-empty"></div>
                                        <div class="beae-skeleton-col-8 beae-skeleton-big"></div>
                                        <div class="beae-skeleton-col-4 beae-skeleton-big beae-skeleton-empty"></div>
                                    </div>
                                </div>
                            </div>`
                }
            }
        },
        style() {
            return [
                {
                    group_alias: 'box',
                    options: {
                        group_title: "General"
                    }
                },
                {
                    group_alias: 'items',
                    options: {
                        group_title: "Items box",
                        selector: ' .beae-product-bundle__item'
                    },
                     modify: {
                        params: {
                            position: 10,
                            fields: {alias: 'spacing'}
                        }
                    }
                },
                {
                    group_alias: 'text',
                    options:{
                        group_title: 'Product title',
                        group_name: 'product_title',
                        selector: ' .beae-product-bundle__url',
                    }
                },
                {
                    group_alias: 'text',
                    options: {
                        group_title: 'Checkbox',
                        group_name: 'checkbox',
                        selector: ' .product-bundle__checkbox-input'
                    },
                    modify: {
                        params: [
                            {
                                position: 20,
                                fields: [
                                    { type: 'paragraph', content: '** Input **'},
                                    {
                                        type: 'tab',
                                        name: 'tab',
                                        value: 'normal',
                                        options: {
                                            tabs: [
                                                {
                                                    name: 'normal',
                                                    title: 'Normal'
                                                },
                                                {
                                                    name: 'hover',
                                                    title: 'Hover'
                                                },
                                                {
                                                    name: 'active',
                                                    title: 'Active'
                                                }
                                            ]
                                        },
                                        css: false
                                    },

                                    {
                                        alias: 'background',
                                        options: {
                                            css: {
                                                selector: 'root .product-bundle__checkbox-input'
                                            },
                                            options: {
                                                visible: data => data['tab'] === 'normal'
                                            }
                                        }
                                    },
                                    {
                                        alias: 'background',
                                        options: {
                                            name: 'backgroundHover',
                                            css: {
                                                properties: {
                                                    background: ''
                                                },
                                                selector: 'root .product-bundle__checkbox-input:hover'
                                            },
                                            options: {
                                                visible: data => data['tab'] === 'hover'
                                            }
                                        }
                                    },
                                    {
                                        alias: 'background',
                                        options: {
                                            name: 'backgroundActive',
                                            css: {
                                                properties: {
                                                    background: ''
                                                },
                                                important: true,
                                                selector: 'root .product-bundle__checkbox-input:checked'
                                            },
                                            options: {
                                                visible: data => data['tab'] === 'active'
                                            }
                                        }
                                    },
                                    {
                                        type: 'popup',
                                        label: 'Border',
                                        name: 'popup',
                                        options: {
                                            type: 'border',
                                            visible: data => data['tab'] === 'normal'
                                        },
                                        css: {
                                            selector: 'root .product-bundle__checkbox-input'
                                        }
                                    },
                                    {
                                        type: 'popup',
                                        label: 'Border',
                                        name: 'popupHover',
                                        options: {
                                            type: 'border',
                                            visible: data => data['tab'] === 'hover'
                                        },
                                        css: {
                                            properties: {
                                                border: ''
                                            },
                                            selector: 'root .product-bundle__checkbox-input:hover'
                                        }
                                    },
                                    {
                                        type: 'popup',
                                        label: 'Border',
                                        name: 'popupActive',
                                        options: {
                                            type: 'border',
                                            visible: data => data['tab'] === 'active'
                                        },
                                        css: {
                                            properties: {
                                                border: ''
                                            },
                                            important: true,
                                            selector: 'root .product-bundle__checkbox-input:checked'
                                        }
                                    },
                                    {
                                        type: 'dimension',
                                        label: 'Border radius',
                                        name: 'border-radius',
                                        options: {
                                            type: 'radius',
                                            units: 'default',
                                            visible: data => data['tab'] === 'normal'
                                        },
                                        css: {
                                            important:true,
                                            selector: 'root .product-bundle__checkbox-input'
                                        }
                                    },
                                    {
                                        type: 'dimension',
                                        label: 'Border radius',
                                        name: 'border-radiusHover',
                                        options: {
                                            units: 'default',
                                            type: 'radius',
                                            visible: data => data['tab'] === 'hover'
                                        },
                                        css: {
                                            important:true,
                                            properties: {
                                                'border-radius': ''
                                            },
                                            selector: 'root .product-bundle__checkbox-input:hover'
                                        }
                                    },
                                    {
                                        type: 'dimension',
                                        label: 'Border radius',
                                        name: 'border-radiusActive',
                                        options: {
                                            units: 'default',
                                            type: 'radius',
                                            visible: data => data['tab'] === 'active'
                                        },
                                        css: {
                                            properties: {
                                                'border-radius': ''
                                            },
                                            important: true,
                                            selector: 'root .product-bundle__checkbox-input:checked'
                                        }
                                    },
                                    {
                                        name: "transition",
                                        type: "number",
                                        label: 'Transition Duration <span class="lowercase">(ms)</span>',
                                        options: {
                                            min: 0,
                                            max: 5000,
                                            visible: {
                                                keep_data: true,
                                                condition: (data) => {
                                                    return data["tab"] === "hover";
                                                }
                                            }
                                        },
                                        css: {
                                            selector: "root .product-bundle__checkbox-input",
                                            properties: {
                                                transition: "all %value%ms ease"
                                            }
                                        }
                                    },
                                    {
                                        type: 'line'
                                    },
                                    { alias: 'spacing', options: {css: {selector: 'root .product-bundle__checkbox-input'}}},
                                ]
                            }
                        ],
                        remove:{
                            index:0,
                            length:4
                        }
                    }
                },
                {
                    group_alias: 'input',
                    options: {
                        group_name: 'select',
                        group_title: 'Variant select',
                        selector: ' .beae-product-bundle__checkbox select',
                    },
                    modify: {
                        remove: {
                            index: 3, length: 1
                        }
                    }
                },
                {
                    group_alias: 'button',
                    options:{
                        group_title: 'Button',
                        group_name: 'add_to_cart_button',
                        selector: ' .beae-product-bundle__atc-btn',
                    },

                },
                {
                    group_alias: 'text',
                    options:{
                        group_title: 'Total text',
                        group_name: 'total_text',
                        selector: ' .beae-product-bundle__price--total_title',
                    }
                },
                {
                    group_alias: 'text',
                    options:{
                        group_title: 'Total Price',
                        group_name: 'total_price',
                        selector: ' .beae-product-bundle__price--total',
                    }
                },
            ];
        },
        css() {
            return `
                .beae-products-bundle .beae-skeleton-col-12 {
                    min-width: 200px;
                    margin-right: 20px;
                }
                .beae-product-bundle__item {
                    text-align: center;
                    text-decoration: none;
                    padding: 20px;
                }
                .beae-product-bundle__price--sale{
                    color: rgb(26, 27, 24);
                    display:inline-flex;
                }
                .beae-product-bundle__atc-btn {
                    margin: 15px 0;
                }
                .beae-product-bundle__price--regular{
                    text-decoration: line-through;
                    color: #6B7280;
                    display:inline-flex;
                    margin-left: 15px;
                }
                .beae-product-bundle__content-list .beae-product-bundle__item:first-child .product-bundle__checkbox-label {
                    opacity: 0.4;
                    pointer-events: none;
                }
                .product-bundle__checkbox-input{
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    appearance: none;
                    padding: 0;
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                    display: inline-block;
                    vertical-align: middle;
                    background-origin: border-box;
                    -webkit-user-select: none;
                    -moz-user-select: none;
                    -ms-user-select: none;
                    user-select: none;
                    flex-shrink: 0;
                    height: 1.6rem;
                    width: 1.6rem !important;
                    background-color: #fff;
                    border: 1px solid rgba(209,213,219,1);
                    border-radius:4px;
                }
                .product-bundle__checkbox-input:focus {
                    box-shadow:rgb(255, 255, 255) 0px 0px 0px 2px, #059669 0px 0px 0px 4px, rgba(0, 0, 0, 0) 0px 0px 0px 0px;
                }
                .product-bundle__checkbox-input:checked {
                    border-color: transparent;
                    background-color: #059669;
                    background-size: 100% 100%;
                    background-position: center;
                    background-repeat: no-repeat;
                    background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
                    }
                .product-bundle__checkbox-input {
                    color: rgba(79, 70, 229, 1);
                    border-radius : 6px;
                    border: 1px solid rgba(0, 0, 0, 0);
                    outline:none;
                }
                .beae-product-bundle__atc-box {
                    flex-direction: column;
                }
                .beae-product-bundle__price--total {
                    margin-left: 15px;
                }
            `
        },
        handles() {
            return this.data.settings.products ? this.data.settings.products.map(product => product.value).join(',') : '';
        }
    }
}
</script>