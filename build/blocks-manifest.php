<?php
// This file is generated. Do not modify it manually.
return array(
	'reviews-block' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'riaco-reviews/reviews-block',
		'version' => '1.2.0',
		'title' => 'RIACO Reviews',
		'category' => 'widgets',
		'icon' => 'star-filled',
		'description' => 'Display a grid or masonry layout of customer reviews.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			)
		),
		'attributes' => array(
			'count' => array(
				'type' => 'integer',
				'default' => 6
			),
			'layout' => array(
				'type' => 'string',
				'default' => 'grid'
			),
			'cardStyle' => array(
				'type' => 'string',
				'default' => 'default'
			),
			'headingLevel' => array(
				'type' => 'integer',
				'default' => 3
			),
			'showAuthorName' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showAvatar' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showDate' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showRating' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showSource' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showProduct' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showTitle' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showShadow' => array(
				'type' => 'boolean',
				'default' => true
			),
			'minWidth' => array(
				'type' => 'integer',
				'default' => 300
			),
			'orderby' => array(
				'type' => 'string',
				'default' => 'date'
			),
			'order' => array(
				'type' => 'string',
				'default' => 'DESC'
			),
			'cardBg' => array(
				'type' => 'string',
				'default' => ''
			),
			'cardTextColor' => array(
				'type' => 'string',
				'default' => ''
			),
			'cardBorderColor' => array(
				'type' => 'string',
				'default' => ''
			),
			'starColor' => array(
				'type' => 'string',
				'default' => ''
			),
			'fontSize' => array(
				'type' => 'string',
				'default' => ''
			),
			'lineHeight' => array(
				'type' => 'string',
				'default' => ''
			),
			'productBg' => array(
				'type' => 'string',
				'default' => ''
			),
			'productTextColor' => array(
				'type' => 'string',
				'default' => ''
			),
			'productFilter' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'textdomain' => 'riaco-reviews',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css'
	)
);
