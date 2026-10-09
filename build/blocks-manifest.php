<?php
// This file is generated. Do not modify it manually.
return array(
	'ub-callout' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-usefull-blocks/ub-callout',
		'version' => '0.6.0',
		'title' => 'UB Callout',
		'category' => 'text',
		'icon' => 'info-outline',
		'description' => 'Highlight a tip, info, warning, or success note for readers.',
		'keywords' => array(
			'callout',
			'notice',
			'tip',
			'warning',
			'info',
			'hinweis',
			'achtung'
		),
		'textdomain' => 'wp-usefull-blocks',
		'attributes' => array(
			'variant' => array(
				'type' => 'string',
				'default' => 'info',
				'enum' => array(
					'info',
					'tip',
					'warning',
					'success'
				)
			),
			'title' => array(
				'type' => 'string',
				'default' => ''
			),
			'content' => array(
				'type' => 'string',
				'default' => ''
			),
			'showIcon' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'supports' => array(
			'anchor' => true,
			'align' => array(
				'wide'
			),
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'variant' => 'tip',
				'title' => 'Tip',
				'content' => 'Keep related settings in the block sidebar so the canvas stays focused on content.',
				'showIcon' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'ub-faq' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-usefull-blocks/ub-faq',
		'version' => '0.7.0',
		'title' => 'UB FAQ',
		'category' => 'text',
		'icon' => 'editor-help',
		'description' => 'A question-and-answer accordion. Click a question to reveal its answer.',
		'keywords' => array(
			'faq',
			'accordion',
			'questions',
			'answers',
			'hilfe',
			'fragen'
		),
		'textdomain' => 'wp-usefull-blocks',
		'attributes' => array(
			'items' => array(
				'type' => 'array',
				'default' => array(
					
				),
				'items' => array(
					'type' => 'object'
				)
			),
			'initiallyOpen' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'anchor' => true,
			'align' => array(
				'wide'
			),
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => true
			),
			'color' => array(
				'text' => true,
				'background' => true,
				'gradients' => true,
				'link' => false
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true
				)
			),
			'interactivity' => true
		),
		'example' => array(
			'attributes' => array(
				'initiallyOpen' => true,
				'items' => array(
					array(
						'id' => 'example-1',
						'question' => 'What is this block for?',
						'answer' => 'It presents frequently asked questions with expandable answers.'
					),
					array(
						'id' => 'example-2',
						'question' => 'Can visitors open several answers?',
						'answer' => 'Yes. Each question toggles independently, like the timeline entries.'
					)
				)
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'ub-file' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-usefull-blocks/ub-file',
		'version' => '0.3.0',
		'title' => 'UB File',
		'category' => 'media',
		'icon' => 'media-default',
		'description' => 'Link to a remote file, optionally mirror it into the media library with Download now, and show link status.',
		'keywords' => array(
			'file',
			'download',
			'mirror',
			'url',
			'pdf'
		),
		'textdomain' => 'wp-usefull-blocks',
		'attributes' => array(
			'sourceUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'label' => array(
				'type' => 'string',
				'default' => ''
			),
			'attachmentId' => array(
				'type' => 'number',
				'default' => 0
			),
			'mirroredFromUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'linkBehavior' => array(
				'type' => 'string',
				'default' => 'original-fallback-local',
				'enum' => array(
					'original-fallback-local',
					'original-only',
					'local-only'
				)
			),
			'lastStatus' => array(
				'type' => 'string',
				'default' => 'unknown'
			),
			'lastChecked' => array(
				'type' => 'number',
				'default' => 0
			)
		),
		'supports' => array(
			'html' => false,
			'anchor' => true,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'ub-gallery' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-usefull-blocks/ub-gallery',
		'version' => '0.2.0',
		'title' => 'UB Gallery',
		'category' => 'media',
		'icon' => 'format-gallery',
		'description' => 'eBay-style gallery with one large focus image and a selectable thumbnail strip.',
		'keywords' => array(
			'gallery',
			'images',
			'photos',
			'lightbox'
		),
		'textdomain' => 'wp-usefull-blocks',
		'attributes' => array(
			'images' => array(
				'type' => 'array',
				'default' => array(
					
				),
				'items' => array(
					'type' => 'object'
				)
			),
			'selectedIndex' => array(
				'type' => 'number',
				'default' => 0
			),
			'linkTo' => array(
				'type' => 'string',
				'default' => 'lightbox'
			),
			'linkTarget' => array(
				'type' => 'string',
				'default' => ''
			),
			'sizeSlug' => array(
				'type' => 'string',
				'default' => 'large'
			),
			'imageCrop' => array(
				'type' => 'boolean',
				'default' => true
			),
			'randomOrder' => array(
				'type' => 'boolean',
				'default' => false
			),
			'caption' => array(
				'type' => 'string',
				'default' => ''
			),
			'onImageClick' => array(
				'type' => 'string',
				'default' => 'lightbox'
			)
		),
		'supports' => array(
			'anchor' => true,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => true
			),
			'color' => array(
				'text' => false,
				'background' => true,
				'gradients' => true
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true
				)
			),
			'interactivity' => true
		),
		'example' => array(
			'attributes' => array(
				'images' => array(
					array(
						'id' => 0,
						'url' => 'https://s.w.org/images/core/5.3/Windbuchencom.jpg',
						'alt' => 'Sample gallery image',
						'caption' => '',
						'fullUrl' => 'https://s.w.org/images/core/5.3/Windbuchencom.jpg'
					)
				),
				'selectedIndex' => 0,
				'linkTo' => 'lightbox',
				'imageCrop' => true,
				'sizeSlug' => 'large'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'ub-link' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-usefull-blocks/ub-link',
		'version' => '0.3.0',
		'title' => 'UB Link',
		'category' => 'text',
		'icon' => 'admin-links',
		'description' => 'A normal WordPress-style link with optional traffic-light status (global setting).',
		'keywords' => array(
			'link',
			'url',
			'href',
			'anchor'
		),
		'textdomain' => 'wp-usefull-blocks',
		'attributes' => array(
			'url' => array(
				'type' => 'string',
				'default' => ''
			),
			'label' => array(
				'type' => 'string',
				'default' => ''
			),
			'openInNewTab' => array(
				'type' => 'boolean',
				'default' => false
			),
			'lastStatus' => array(
				'type' => 'string',
				'default' => 'unknown'
			),
			'lastChecked' => array(
				'type' => 'number',
				'default' => 0
			)
		),
		'supports' => array(
			'html' => false,
			'anchor' => true,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'color' => array(
				'link' => true,
				'text' => true,
				'background' => false
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'ub-reading-time' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-usefull-blocks/ub-reading-time',
		'version' => '0.7.0',
		'title' => 'UB Reading Time',
		'category' => 'widgets',
		'icon' => 'clock',
		'description' => 'Shows an estimated reading time based on this post’s word count.',
		'keywords' => array(
			'reading time',
			'minutes',
			'word count',
			'lesezeit',
			'dauer'
		),
		'textdomain' => 'wp-usefull-blocks',
		'attributes' => array(
			'wordsPerMinute' => array(
				'type' => 'number',
				'default' => 200
			),
			'showWordCount' => array(
				'type' => 'boolean',
				'default' => false
			),
			'prefix' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'anchor' => true,
			'align' => array(
				'wide'
			),
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'color' => array(
				'text' => true,
				'background' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'wordsPerMinute' => 200,
				'showWordCount' => true,
				'prefix' => ''
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'ub-timeline' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-usefull-blocks/ub-timeline',
		'version' => '0.5.0',
		'title' => 'UB Timeline',
		'category' => 'widgets',
		'icon' => 'backup',
		'description' => 'A timeline of titled entries with click-to-expand descriptions. Choose horizontal or vertical layout.',
		'keywords' => array(
			'timeline',
			'history',
			'chronology',
			'zeitleiste',
			'ablauf'
		),
		'textdomain' => 'wp-usefull-blocks',
		'attributes' => array(
			'orientation' => array(
				'type' => 'string',
				'default' => 'vertical',
				'enum' => array(
					'vertical',
					'horizontal'
				)
			),
			'items' => array(
				'type' => 'array',
				'default' => array(
					
				),
				'items' => array(
					'type' => 'object'
				)
			),
			'initiallyOpen' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'anchor' => true,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => true
			),
			'color' => array(
				'text' => true,
				'background' => true,
				'gradients' => true,
				'link' => false
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true
				)
			),
			'interactivity' => true
		),
		'example' => array(
			'attributes' => array(
				'orientation' => 'vertical',
				'initiallyOpen' => true,
				'items' => array(
					array(
						'id' => 'example-1',
						'title' => 'Kickoff',
						'description' => 'Project starts and goals are set.'
					),
					array(
						'id' => 'example-2',
						'title' => 'Build',
						'description' => 'Features ship in iterative releases.'
					),
					array(
						'id' => 'example-3',
						'title' => 'Launch',
						'description' => 'The product goes live.'
					)
				)
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'ub-toc' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-usefull-blocks/ub-toc',
		'version' => '0.6.0',
		'title' => 'UB Table of Contents',
		'category' => 'widgets',
		'icon' => 'list-view',
		'description' => 'Automatically lists this post’s headings as an on-page table of contents.',
		'keywords' => array(
			'toc',
			'contents',
			'outline',
			'headings',
			'inhaltsverzeichnis'
		),
		'textdomain' => 'wp-usefull-blocks',
		'attributes' => array(
			'title' => array(
				'type' => 'string',
				'default' => ''
			),
			'minLevel' => array(
				'type' => 'number',
				'default' => 2
			),
			'maxLevel' => array(
				'type' => 'number',
				'default' => 3
			),
			'ordered' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'anchor' => true,
			'align' => array(
				'wide'
			),
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'title' => 'Contents',
				'minLevel' => 2,
				'maxLevel' => 3
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'wp-usefull-blocks' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-usefull-blocks/wp-usefull-blocks',
		'version' => '0.1.0',
		'title' => 'WP Usefull Blocks',
		'category' => 'widgets',
		'icon' => 'smiley',
		'description' => 'Some useful blocks for the WordPress Gutenberg editor.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'wp-usefull-blocks',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js'
	)
);
