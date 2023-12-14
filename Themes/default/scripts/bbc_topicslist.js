/**
 * @package BBC Message Boxes
 * @version 3.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2023, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

sceditor.command.set(
	'topicslist', {
		// Called when editor is in WYSIWYG mode.
		exec: function() {
			this.insert('[topicslist]' + bbc_topicslist_default, '[/topicslist]');
		},
		// Called when editor is in source mode.
		txtExec: function() {
			this.insert('[topicslist]' + bbc_topicslist_default, '[/topicslist]');
		}
	}
);


let topics_lists = document.querySelectorAll('.roundframe.bbc_topicslist');

if (topics_lists)
{
	topics_lists.forEach(e => {
		let character_index = e.querySelectorAll('h3 > a');

		if (character_index)
		{
			character_index.forEach(char_index => {
				// console.log('this is the character', e.innerText)
				char_index.addEventListener('click', char => {
					char.preventDefault();
					window.scroll(0, e.querySelector('.list_topic_' + char_index.innerText).offsetTop);
				});
			});
		}
	});
}