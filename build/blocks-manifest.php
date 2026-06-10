<?php
// This file is generated. Do not modify it manually.
return array(
	'reviews-block' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'riaco-reviews/reviews-block',
		'version' => '1.0.0',
		'title' => 'RIACO Reviews',
		'category' => 'widgets',
		'icon' => 'star-filled',
		'description' => 'Display a grid or masonry layout of customer reviews.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
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
				'default' => false
			),
			'showRating' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showSource' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showTag' => array(
				'type' => 'boolean',
				'default' => true
			),
			'orderby' => array(
				'type' => 'string',
				'default' => 'date'
			),
			'order' => array(
				'type' => 'string',
				'default' => 'DESC'
			)
		),
		'textdomain' => 'riaco-reviews',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css'
	)
);
