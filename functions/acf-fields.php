<?php
if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_60a4f8b4a3a5b',
	'title' => 'Front Page Settings',
	'fields' => array(
		array(
			'key' => 'field_60a4f8c1a3a5c',
			'label' => 'Hero Title',
			'name' => 'hero_title',
			'type' => 'text',
		),
		array(
			'key' => 'field_60a4f8d0a3a5d',
			'label' => 'Hero Subtitle',
			'name' => 'hero_subtitle',
			'type' => 'textarea',
		),
		array(
			'key' => 'field_60a4f8e0a3a5e',
			'label' => 'Hero Button Text',
			'name' => 'hero_button_text',
			'type' => 'text',
		),
		array(
			'key' => 'field_60a4f8f0a3a5f',
			'label' => 'Hero Button URL',
			'name' => 'hero_button_url',
			'type' => 'url',
		),
		array(
			'key' => 'field_60a4f900a3a60',
			'label' => 'Hero Image',
			'name' => 'hero_image',
			'type' => 'image',
			'return_format' => 'url',
		),
        array(
            'key' => 'field_60a5f900a3a61',
            'label' => 'Companies Title',
            'name' => 'companies_title',
            'type' => 'text',
        ),
        array(
            'key' => 'field_60a5f900a3a62',
            'label' => 'Company Logos',
            'name' => 'company_logos',
            'type' => 'gallery',
        ),
        array(
            'key' => 'field_60a5f900a3a63',
            'label' => 'Solutions Title',
            'name' => 'solutions_title',
            'type' => 'text',
        ),
        array(
            'key' => 'field_60a5f900a3a64',
            'label' => 'Pricing Title',
            'name' => 'pricing_title',
            'type' => 'text',
        ),
        array(
            'key' => 'field_60a5f900a3a65',
            'label' => 'Pricing Plans',
            'name' => 'pricing_plans',
            'type' => 'repeater',
            'layout' => 'table',
            'button_label' => 'Add Plan',
            'sub_fields' => array(
                array(
                    'key' => 'field_60a5f900a3a66',
                    'label' => 'Plan Name',
                    'name' => 'plan_name',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_60a5f900a3a67',
                    'label' => 'Price',
                    'name' => 'price',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_60a5f900a3a68',
                    'label' => 'Features',
                    'name' => 'features',
                    'type' => 'repeater',
                    'layout' => 'table',
                    'button_label' => 'Add Feature',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_60a5f900a3a69',
                            'label' => 'Feature',
                            'name' => 'feature',
                            'type' => 'text',
                        ),
                    ),
                ),
            ),
        ),
	),
	'location' => array(
		array(
			array(
				'param' => 'page_template',
				'operator' => '==',
				'value' => 'front-page.php',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
));

endif;
