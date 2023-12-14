<?php
/**
 * Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines https://www.simplemachines.org
 * @copyright 2022 Simple Machines and individual contributors
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.0
 */

function template_topics_list() : string
{
	global $context, $scripturl, $modSettings;

	$topics_list = '';

	// Characters list
	if (!empty($modSettings['TopicsList_topic_only_first']))
	{
		$topics_list .= '
		<div class="cat_bar">
			<h3 class="catbg">';

		foreach ($context['list_topics_index'] as $index_character)
		{
			$topics_list .= '<a href="#">' . $index_character . '</a>';
		}

		$topics_list .= '
			</h3>
		</div>';
	}

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
				<a href="' . $scripturl . '?topic=' . $topic_info['id_topic'] . '.0">' . $topic_info['subject'] . '</a>
			</li>';
		}

		$topics_list .= '
		</ul>';
	}


	return $topics_list;
}