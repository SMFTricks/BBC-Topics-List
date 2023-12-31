<?php

/**
 * @package BBC Topics List
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2023, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

use PostPrefix\Helper\Database;
use PostPrefix\PostPrefix;

if (!defined('SMF'))
	die('No direct access...');

class BBC_TopicsList
{
	/**
	 * @var array The BBC's
	 */
	private array $_bbc_list = [
		'tlist',
		'topicslist',
	];

	/**
	 * @var array Numeric characters
	 */
	private array $_numeric_chars = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

	/**
	 * @var string The included characters
	 */
	private string $_include_chars = '';

	/**
	 * @var int The selected board
	 */
	private int $_selected_board = 0;
	
	/**
	 * @var string The actual data of the BBC
	 */
	private string $_data = '';

	/**
	 * @var bool Use prefixes
	 */
	public bool $_use_prefixes = false;

	/**
	 * Initialize the mod
	 */
	public function initialize() : void
	{
		// Load hooks
		$this->hooks();
	}

	/**
	 * Load the hooks for the mod
	 */
	private function hooks() : void
	{
		add_integration_function('integrate_pre_css_output', 'BBC_TopicsList::css#', false);
		add_integration_function('integrate_pre_javascript_output', 'BBC_TopicsList::js#', false);
		add_integration_function('integrate_load_permissions', 'BBC_TopicsList::permissions#', false);
		add_integration_function('integrate_admin_areas', 'BBC_TopicsList::admin_areas#', false);
		add_integration_function('integrate_modify_modifications', 'BBC_TopicsList::modify_modifications#', false);
		add_integration_function('integrate_helpadmin', 'BBC_TopicsList::language#', false);
		add_integration_function('integrate_preparsecode', 'BBC_TopicsList::preparsecode#', false);
		add_integration_function('integrate_bbc_buttons', 'BBC_TopicsList::bbc_buttons#', false);
		add_integration_function('integrate_bbc_codes', 'BBC_TopicsList::bbc_codes#', false);
		add_integration_function('integrate_load_theme', 'BBC_TopicsList::load_theme#', false);
	}

	/**
	 * Language
	 */
	public function language() : void
	{
		loadLanguage('TopicsList/');
	}

	/**
	 * Load the template
	 */
	public function load_theme() : void
	{
		loadTemplate('TopicsList');
	}

	/**
	 * Ádd the permissions
	 * 
	 * @param array $permissionList The list of permissions
	 */
	public function permissions(array &$permissionGroups, array &$permissionList) : void
	{
		$permissionList['membergroup']['TopicsList_use'] = [false, 'general'];
	}

	/**
	 * Add the seciton to the menu
	 * 
	 * @param $areas The admin areas
	 */
	public function admin_areas(array &$areas)
	{
		global $txt;

		$this->language();
		$areas['config']['areas']['modsettings']['subsections']['topicslist'] = [$txt['TopicsList_title']];
	}

	/**
	 * Add the new subaction for the topics list
	 * 
	 * @param $subActions The list of subactions
	 */
	public function modify_modifications(array &$subActions) : void
	{
		$subActions['topicslist'] = __CLASS__ . '::settings#';
	}

	/**
	 * The settings page
	 * 
	 * @param $return_config If the results are being returned to the search page.
	 */
	public function settings(bool $return_config = false)
	{
		global $txt, $context, $scripturl;

		$context['post_url'] = $scripturl . '?action=admin;area=modsettings;sa=topicslist;save';
		$context['page_title'] = $txt['TopicsList_title'];
		$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['TopicsList_description'];

		$config_vars = [
			['int', 'TopicsList_topic_limit', 'subtext' => $txt['TopicsList_topic_limit_desc'], 'min' => 0],
			['check', 'TopicsList_topic_notags', 'subtext' => $txt['TopicsList_topic_notags_desc']],
			['check', 'TopicsList_topic_only', 'subtext' => $txt['TopicsList_topic_only_desc']],
			['check', 'TopicsList_topics_nosticky'],
			['check', 'TopicsList_topics_noself', 'subtext' => $txt['TopicsList_topics_noself_desc']],
			['check', 'TopicsList_topics_prefixes', 'subtext' => $txt['TopicsList_topics_prefixes_desc']],
			['permissions', 'TopicsList_use', 'subtext' => $txt['permissionhelp_TopicsList_use']]
		];
		
		// Return config vars
		if ($return_config)
			return $config_vars;

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();
			saveDBSettings($config_vars);
			clean_cache();
			redirectexit('action=admin;area=modsettings;sa=topicslist');
		}
		prepareDBSettingContext($config_vars);
	}

	/**
	 * Add some checks before the message is sent
	 * 
	 * @param string $message The message content
	 */
	public function preparsecode(string &$message) : void
	{
		// Pattern
		$tag_patterns = array();
		foreach ($this->_bbc_list as $bbc)
			$tag_patterns[] = preg_quote($bbc) . '(?:(?!\]).)*?';
		$pattern = '/\[(?:' . implode('|', $tag_patterns) . ')\](.*?)\[\/(?:' . implode('|', $this->_bbc_list) . ')\]/';

		// If the user is not an admin, can't use any of the tags.
		if (!allowedTo('TopicsList_use'))
			$message = preg_replace($pattern, '$1', $message);
	}

	/**
	 * Add the bbc to the editor toolbar
	 * 
	 * @param array $tags The bbc tags
	 * @return void
	 */
	public function bbc_buttons(array &$bbc_tags) : void
	{
		global $txt, $editortxt;

		// Permission to use these?1
		if (!allowedTo('TopicsList_use'))
			return;

		$this->language();
		addJavaScriptVar('bbc_topicslist_title', $txt['TopicsList_insert_title'], true);
		addJavaScriptVar('bbc_topicslist_default', $txt['TopicsList_default_text'], true);
		addJavaScriptVar('bbc_topicslist_board', $txt['TopicsList_insert_board'], true);
		addJavaScriptVar('bbc_topicslist_board_desc', $txt['TopicsList_insert_board_desc'], true);
		addJavaScriptVar('bbc_topicslist_insert', $editortxt['insert'], true);
		addJavaScriptVar('bbc_topicslist_alphanumeric', $txt['TopicsList_insert_alphanumeric'], true);
		addJavaScriptVar('bbc_topicslist_include', $txt['TopicsList_insert_include'], true);
		addJavaScriptVar('bbc_topicslist_include_desc', $txt['TopicsList_insert_include_desc'], true);
		addJavaScriptVar('bbc_topicslist_include_placeholder', $txt['TopicsList_insert_include_placeholder'], true);

		// Add the BBCs
		$bbc_tags[][] = [
			'image' => 'topicslist',
			'code' => 'topicslist',
			'description' => $txt['TopicsList_insert_desc']
		];
	}

	/**
	 * Attach the content to the bbc.
	 * 
	 * @param array $codes The bbc codes
	 * @param array $no_autolink_tags Disable autolink for these tags
	 * @return void
	 */
	public function bbc_codes(array &$codes, array &$no_autolink_tags) : void
	{
		global $txt;

		$this->language();

		// Add the BBCs
		foreach ($this->_bbc_list as $bbc)
		{
			// Don't autolink this bbc
			$no_autolink_tags[] = $bbc;

			// Add the bbc
			$codes[] = [
				'tag' => $bbc,
				'type' => ($bbc === 'topicslist' ? 'unparsed_content' : 'unparsed_equals_content'),
				'parameters' => ($bbc === 'topicslist' ? [
					'board' => [
						'optional' => true,
						'quote' => true,
						'default' => 0,
						'match' => '(\d+)',
					],
					'include' => [
						'optional' => true,
						'quote' => true,
					],
					'alphanumeric' => [
						'optional' => true,
						'quote' => true,
						'match' => '(true|false)'
					],
				] : null),
				'content' => '<div class="roundframe bbc_topicslist">$1</div>',
				'disabled_content' => '<div class="noticebox">' . $txt['TopcisList_disabled'] . '</div>',
				'validate' => isset($disabled['code']) ? null : function(array &$tag, string|array|null &$data, array|null $disabled, array &$params)
				{
					// Handle the bbc somewhere else.
					$this->getList($tag, $data, $params);
				},
				'block_level' => true,
				'disallow_children' => true,
			];
		}
	}

	/**
	 * Get the topics list
	 * 
	 * @param array $tag The content and options of the tag
	 * @param string $data The content of the BBC
	 * @param array $params The bbc parameters
	 */
	private function getList(array $tag, string|array|null &$data, array &$params) : void
	{
		global $smcFunc, $txt, $scripturl, $modSettings, $context, $board, $topic, $user_info, $settings;

		// List title
		if ($tag['tag'] === 'topicslist')
		{
			$context['list_topics_title'] = $data;
		}

		// Included characters
		$this->_include_chars = ($tag['tag'] === 'tlist' ? ($data[0] ?: '') : ($params['{include}'] ?: ''));

		// Selected board
		$this->_selected_board = ($tag['tag'] === 'tlist' ? ($data[1] ?? 0) : ($params['{board}'] ?: 0));

		// Default data
		$this->_data = $txt['TopicsList_no_data'];

		// Only in topics?
		if (!empty($modSettings['TopicsList_topic_only']) && empty($board) && empty($topic))
			return;

		// $context['icon_sources'] says where each icon should come from - here we set up the ones which will always exist!
		if (empty($context['icon_sources']))
		{
			$context['icon_sources'] = array();
			foreach ($context['stable_icons'] as $icon)
				$context['icon_sources'][$icon] = 'images_url';
		}

		// Topcis list for the template
		$context['list_topics'] = [];
		$context['list_topics_index'] = [];

		// Prefixes
		$this->_use_prefixes = method_exists('PostPrefix\PostPrefix', 'format') && !empty($modSettings['TopicsList_topics_prefixes']) ?? false;
	
		if (($context['list_topics'] = cache_get_data('bbc_topicslist_b' . $this->_selected_board . '_u' . $user_info['id'] . (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' ? '_alphanum' : '') . (!empty($this->_include_chars) ? '_inc-' . $this->_include_chars : ''), $context['list_topics'], 3600)) === null || ($context['list_topics_index'] = cache_get_data('bbc_topicslistindex_b' . $this->_selected_board . '_u' . $user_info['id'] . (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' ? '_alphanum' : '') . (!empty($this->_include_chars) ? '_inc-' . $this->_include_chars : ''), $context['list_topics_index'], 3600)) === null)
		{
			$result = $smcFunc['db_query']('', '
				SELECT
					t.id_topic, t.id_board, t.approved, t.id_first_msg,
					TRIM(m.subject) as subject, m.icon' . (empty($this->_use_prefixes) ? '' : ', 
					t.id_prefix, ' . implode(',',Database::$_prefix_columns)) . '
				FROM {db_prefix}topics as t
				JOIN {db_prefix}messages as m ON (m.id_msg = t.id_first_msg)' . (empty($this->_use_prefixes) ? '' : '
				LEFT JOIN {db_prefix}postprefixes AS pp ON (pp.id = t.id_prefix)') . '
				WHERE t.approved = {int:approved}
					AND {query_see_topic_board}' . (empty($this->_selected_board) ? '' : '
					AND t.id_board = {int:board}') . (empty($modSettings['TopicsList_topics_nosticky']) ? '' : '
					AND t.is_sticky = {int:notsticky}') . (empty($modSettings['TopicsList_topics_noself']) ? '' : '
					AND t.id_topic <> {int:current}') . '
				ORDER BY subject' . (empty($modSettings['TopicsList_topic_limit']) ? '' : '
				LIMIT {int:limit}'),
				[
					'board' => $this->_selected_board,
					'limit' => (int) ($modSettings['TopicsList_topic_limit'] ?? 0),
					'approved' => 1,
					'notsticky' => 0,
					'current' => $topic ?? 0
				]
			);

			$context['list_topics']['0-9'] = [];
			$context['list_topics_index'] = ['0-9' => 0];
			$context['list_included_chars'] = !empty($this->_include_chars) ? explode($tag['tag'] === 'tlist' ? '|' : ',', $this->_include_chars) : [];

			// Make the characters uppercase
			if (!empty($context['list_included_chars']))
			{
				foreach ($context['list_included_chars'] as $key => $included_character)
				{
					$context['list_included_chars'][$key] = mb_strtoupper($included_character);
				}
			}

			while ($row = $smcFunc['db_fetch_assoc']($result))
			{
				// Message Icon Management... check the images exist.
				if (!empty($modSettings['messageIconChecks_enable']))
				{
					// If the current icon isn't known, then we need to do something...
					if (!isset($context['icon_sources'][$row['icon']]))
						$context['icon_sources'][$row['icon']] = file_exists($settings['theme_dir'] . '/images/post/' . $row['icon'] . '.png') ? 'images_url' : 'default_images_url';
				}
				elseif (!isset($context['icon_sources'][$row['icon']]))
				{
					$context['icon_sources'][$row['icon']] = 'images_url';
				}

				// Remove initial tags?
				if (!empty($modSettings['TopicsList_topic_notags']))
					$row['subject'] = preg_replace('/^\[[^\]]+\]\s*/', '', $row['subject']);

				// Initial letter
				$initial_character = mb_substr(mb_strtoupper($row['subject']), 0, 1);

				// Included?
				if (!empty($context['list_included_chars']) && !in_array($initial_character, $context['list_included_chars']))
					continue;

				// Regex?
				if (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' && preg_match('/^[^\p{L}\p{N}]/u', $initial_character))
					continue;

				// Add the initial character to the index
				$context['list_topics_index'][$initial_character] = 0;

				// Add the topic
				$context['list_topics'][$initial_character][$row['id_topic']] = [
					'id_topic' => $row['id_topic'],
					'subject' => $row['subject'],
					'icon_url' => $settings[$context['icon_sources'][$row['icon']]] . '/post/' . $row['icon'] . '.png',
					'prefix' => !empty($this->_use_prefixes) && !empty($row['id_prefix']) ?
						PostPrefix::format($row) : '',
				];

				// print_r($context['list_topics']);

			}
			$smcFunc['db_free_result']($result);

			// Topics?
			if (!empty($context['list_topics']))
			{
				// Group the numbers
				foreach ($context['list_topics'] as $initial_character => $character_data)
				{
					if (!in_array($initial_character, $this->_numeric_chars))
						continue;

					$context['list_topics']['0-9'] += $character_data;
					unset($context['list_topics'][$initial_character]);
					unset($context['list_topics_index'][$initial_character]);
				}

				// Dump the numeric index
				if (empty($context['list_topics']['0-9']))
				{
					unset($context['list_topics']['0-9']);
					unset($context['list_topics_index']['0-9']);
				}
			}

			// Cache!
			cache_put_data('bbc_topicslist_b' . $this->_selected_board . '_u' . $user_info['id'] . (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' ? '_alphanum' : '') . (!empty($this->_include_chars) ? '_inc-' . $this->_include_chars : ''), $context['list_topics'], 3600);
			cache_put_data('bbc_topicslistindex_b' . $this->_selected_board . '_u' . $user_info['id'] . (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' ? '_alphanum' : '') . (!empty($this->_include_chars) ? '_inc-' . $this->_include_chars : ''), $context['list_topics_index'], 3600);
		}

		// If there are any topics, load the list
		if (!empty($context['list_topics']))
		{
			// Template
			$this->_data = template_topics_list();

			// There's a board?
			if (!empty($this->_selected_board) && !empty($modSettings['TopicsList_topic_limit']))
			{
				$this->_data .= '<br><a class="button" href="' . $scripturl . '?board=' . $this->_selected_board . '.0">' . $txt['all'] . '</a>';
			}
		}

		// Send back the data
		if ($tag['tag'] === 'tlist')
		{
			$data[0] = $this->_data;
		}
		else
		{
			$data = $this->_data;
		}
	}

	/**
	 * Load the CSS
	 */
	public function css() : void
	{
		loadCSSFile('bbc_topicslist.css', ['minimize' => true, 'default_theme' => true], 'smf_bbc_topicslist');
	}

	/**
	 * Load the JS for the topics list
	 */
	public function js() : void
	{
		loadJavaScriptFile('bbc_topicslist.js', ['minimize' => true, 'default_theme' => true, 'defer' => true], 'smf_bbc_topicslist');
	}
}