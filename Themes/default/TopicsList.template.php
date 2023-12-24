<?php

/**
 * @package BBC Topics List
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2023, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

function template_topics_list() : string
{
	global $context, $scripturl;

	$topics_list = !empty($context['list_topics_title']) ? '<h2>' . $context['list_topics_title'] . '</h2>' : '';

	// Characters list
	$topics_list .= '
		<div class="cat_bar">
			<h3 class="catbg">';

		foreach ($context['list_topics_index'] as $index_character => $value)
		{
			$topics_list .= '<a href="#">' . $index_character . '</a>';
		}

		$topics_list .= '
			</h3>
		</div>';

	foreach ($context['list_topics'] as $character => $initial_character)
	{
		$topics_list .= '
		<div class="title_bar list_topic_'. $character . '">
			<h4 class="titlebg">' . $character . '</h4>
		</div>
		<ul>';

		foreach ($initial_character as $topic_info)
		{
			$topics_list .= '
			<li class="windowbg">
				<span class="msg_icon">
					<img src="' . $topic_info['icon_url'] . '">
				</span>
				' . ($topic_info['prefix'] ?: '') . '
				<a href="' . $scripturl . '?topic=' . $topic_info['id_topic'] . '.0">' . $topic_info['subject'] . '</a>
			</li>';
		}

		$topics_list .= '
		</ul>';
	}


	return $topics_list;
}