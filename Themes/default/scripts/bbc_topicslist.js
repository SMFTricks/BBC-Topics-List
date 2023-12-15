/**
 * @package BBC Topics List
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2023, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

/**
 * Scroll into the character
 */
document.querySelectorAll('.roundframe.bbc_topicslist').forEach(e => {
	e.querySelectorAll('h3 > a').forEach(char_index => {
		char_index.addEventListener('click', char => {
			char.preventDefault();
			window.scroll(0, e.querySelector('.list_topic_' + char_index.innerText).offsetTop);
		});
	});
});


/**
 * BBC sceditor
 */
if (typeof sceditor !== 'undefined')
{
	sceditor.command.set(
		'topicslist', {

			// Dropdown
			_dropDown: function (editor, caller, callback) {

				let	content = document.createElement('div');
				content.innerHTML = bbc_topicslist_html(editor);
				editor.createDropDown(caller, 'insertTopicList', content);

				document.getElementById('topicslist_insert').addEventListener('click', e => {
					e.preventDefault();
					callback(
						document.getElementById('topicslist_title').value,
						document.getElementById('topicslist_board').value,
						document.getElementById('topicslist_include').value,
						document.getElementById('topicslist_alphanumeric').checked
					);
					editor.closeDropDown(true);
				});
			},

			// Called when editor is in WYSIWYG mode.
			exec: function(caller) {
				this.commands.topicslist._dropDown(this, caller, (title, board, only_include, alphanumeric) => bbc_topics_list_insert(this, title, board, only_include, alphanumeric));
			},
			// Called when editor is in source mode.
			txtExec: function(caller) {
				this.commands.topicslist._dropDown(this, caller, (title, board, only_include, alphanumeric) => bbc_topics_list_insert(this, title, board, only_include, alphanumeric));
			}
		}
	);
}

/**
 * Insert the BBC
 */
function bbc_topics_list_insert(editor, title, board, only_include, alphanumeric)
{
	// Board
	board = !board ? 0 : parseInt(board);
	// Default title
	title = !title ? bbc_topicslist_default : title;

	editor.insert('[topicslist' + (!board ? '' : ' board=' + board) + (!only_include ? '' : ' include=' + only_include) + (!alphanumeric ? '' : ' alphanumeric=true') + ']' + title + '[/topicslist]');
}

/**
 * Add the dropdown HTML markup
 */
function bbc_topicslist_html(editor)
{
	return '<label for="topicslist_board">' + (bbc_topicslist_board) + ':<br><span class="smalltext">' + bbc_topicslist_board_desc + '</span></label><input type="number" min="0" value="0" id="topicslist_board" dir="ltr"><label for="topicslist_title">' + (bbc_topicslist_title) + ':</label><input type="text" id="topicslist_title" dir="ltr" placeholder="' + bbc_topicslist_default + '"><label for="topicslist_include">' + (bbc_topicslist_include) + ':<br><span class="smalltext">' + bbc_topicslist_include_desc + '</span></label><input type="text" id="topicslist_include" dir="ltr" placeholder="' + bbc_topicslist_include_placeholder + '"><div class="check_input"><label for="topicslist_alphanumeric">' + (bbc_topicslist_alphanumeric) + ':</label><input id="topicslist_alphanumeric" type="checkbox" value="true"></div><button id="topicslist_insert" type="button" class="button">' + bbc_topicslist_insert + '</button>';
}