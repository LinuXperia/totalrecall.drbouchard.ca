<?php
#
# Total Recall - the flash card webapp.
# Stand-alone PHP script for Flash Cards with a Javascript interface and logic.
# By: Brady Bouchard
# brady@bradybouchard.ca
# Available at: http://github.com/brady8/total-recall
#
# ------------------------------------------------------------------
#
# Copyright Brady Bouchard 2010.
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
# ------------------------------------------------------------------
#
# See the README (README.markdown) for more information.


# Configuration (is editing allowed?)
require_once('config.php');
# The CSV handling class.
require_once('lib/CSV.php');
# Allow PHP to choose the line break depending on the operating system.
ini_set('auto_detect_line_endings', true);
# Safari chokes on unicode characters unless this is here.
header("Content-type:text/html; charset=utf-8");

class Navigation {

	public $action;			# The current user action.
	public $page_title;
	public $study_data;
	public $csv;			# A handle to an instance of the CSV class.
	public $filename;		# Filename of the current CSV file.
	public $start_index;	# Which card do we want to start with?

	function __construct() {
		$this->csv = new CSV;
		$this->action = (strlen($_SERVER['QUERY_STRING']) > 0 ? 'study' : 'choose');
		$qs = preg_split('/&/', $_SERVER['QUERY_STRING']);
		$this->filename = (isset($qs[0]) && strlen($qs[0]) > 0 ? $qs[0] : null);
		$this->start_index = (isset($qs[1]) && strlen($qs[1]) > 0 ? $qs[1] : null);
		switch ($this->action) {
			case 'study':
				$this->study();
				break;
			case 'choose':
				$this->choose_directory();
				break;
			default:
				$this->choose_directory();
		}
	}

	function study() {
		if ($this->study_data = $this->csv->open_study_data($this->filename)) {
			$this->page_title = 'Studying: ' . $this->study_data['title'];
		} else {
			$this->error = 'Invalid study set.';
			$this->action = 'choose';
			$this->page_title = 'Choose a Study Set';
		}
	}

	function choose_directory() {
		$this->page_title = "Let's get this study party started...";
	}
}

$nav = new Navigation;

?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width = 660" />
<meta name="author" content="Brady Bouchard; http://github.com/brady8/total-recall" />
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=8" />
<title>Total Recall - <?php echo $nav->page_title; ?></title>
<link href="css/base.css" rel="stylesheet" type="text/css" />
<link href="css/study.css" rel="stylesheet" type="text/css" />
<link href="js/fancybox/jquery.fancybox-1.3.1.css" rel="stylesheet" type="text/css" />
<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/jquery.jstore.js" type="text/javascript"></script>
<script src="js/study.js" type="text/javascript"></script>
<script src="js/fancybox/jquery.fancybox-1.3.1.pack.js" type="text/javascript"></script>

<script type="text/javascript">
//<![CDATA[
<?php if ($nav->action == 'choose') : ?>
	$(document).ready(function() {
		$('#set-list ul li a').each(function() {
			if ($.getItem($(this).attr('rel') + '_date') != null) {
				today = new Date();
				last_tested = new Date(Date.parse($.getItem($(this).attr('rel') + '_date')));
				if (today.toDateString() == last_tested.toDateString()) {
					if ($.getItem($(this).attr('rel') + '_card_counts') != null) {
						studied_count = $.getItem($(this).attr('rel') + '_card_counts')[0];
						total_count = $.getItem($(this).attr('rel') + '_card_counts')[1];
						percentage = Math.round(100 - (100 * studied_count / total_count));
						$(this).html('<strong>' + $(this).html() + '</strong> &nbsp;' +
						  percentage + '%' + ' (' +
						  studied_count + ' of ' + total_count + ' left today)'
						);
					}
				}
			}
		});
	});
<?php endif; ?>
<?php if ($nav->action == 'study') : ?>
<?php
if (count($nav->study_data['questions']) > 0) {
	echo "var \$fc = " . json_encode($nav->study_data['questions']) . ";\n";
	echo "var \$set_filename = " . json_encode($nav->filename) . ";\n";
	if (isset($nav->start_index)) {
		echo "var \$start_index = " . json_encode($nav->start_index) . ";\n";
	}
}
?>
<?php endif; ?>
//]]>
</script>

</head>

<body>

<div id="nav-buttons">
	<div id="reset-database" style="display: none;">
		<a href="#">reset progress</a>
	</div>
	<?php if (ALLOW_EDITING || $_SERVER['HTTP_HOST'] == 'localhost') : ?>
	<div id="edit-question" style="display: none;">
		<a href="#">edit this question</a>
	</div>
	<?php endif; ?>
</div>
<?php

	if ($nav->action == 'choose') {
		echo "<h1>$nav->page_title</h1>\n";
		$file_list = $nav->csv->list_files();
		echo "<div id=\"set-list\">\n";
		if (count($file_list) > 0) {
			echo "<ul>\n";
			foreach ($file_list as $file) {
				echo "<li><a href=\"?" . urlencode(pathinfo($file, PATHINFO_FILENAME)) . "\" rel=\"" . urlencode(pathinfo($file, PATHINFO_FILENAME)) . "\">" . $nav->csv->humanize(pathinfo($file, PATHINFO_FILENAME)) . "</a></li>\n";
			}
			echo "</ul>\n";
		} else {
			echo 'You don\'t have any study sets available in the directory ' . $nav->csv->directory . "\n";
		}
		echo "</div>\n";
	} elseif ($nav->action == 'study') {
?>
<div id="progress-bar" style="display: none;">
</div>
<div id="stop-it">
	<a href="./">stop studying</a>
</div>
<div id="question-box">
	<div id="question-content">
		sorry, but you need to enable javascript for this to work.
	</div>
	<div id="question-controls">
		<form>
			<button id="show-answer">click here or press (space) to show answer</button>
		</form>
	</div>
</div>

<div id="answer-box">
	<div id="answer-content">
		loading answer...
	</div>
	<div id="answer-controls">
		<form action="#">
			<fieldset>
				<button class="b1 sbad scorebutton" id="1">no clue ( J )</button>
				<button class="b2 sbad scorebutton" id="2">poor ( K )</button>
				<button class="b3 scorebutton sgood" id="3">fair ( L )</button>
				<button class="b5 scorebutton sgood" id="5">perfect ( ; )</button>
			</fieldset>
		</form>
	</div>
</div>

<div id="debug" style="font-size: 1.5em;">
</div>

<?php } ?>

<?php if ($nav->action == 'choose') : ?>
<div id="footer">
<p><a href="http://github.com/brady8/total-recall">Total Recall</a>, developed by <a href="mailto:brady@lunardawn.ca">Brady Bouchard</a>.</p>
<p>Requires <strong>Firefox 3.5+</strong>, <strong>Chrome 5+ (ish?)</strong>, <strong>Safari 4+</strong>, or <strong>MSIE 8+</strong>.<br />Works in <strong>Firefox 3.0</strong> too, but no memorization data is saved.</p>
<p>Using the SM-2 algorithm for <a href="http://en.wikipedia.org/wiki/Spaced_repetition">spaced interval learning</a>.<br />The frequency with which cards are shown is based on how you do on previous attempts.</p>
<?php if (ADD_SUBMIT_LINK || $_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'totalrecall.bradybouchard.ca') { ?>
<p>If you have flash cards to contribute or corrections to those already here, please feel free to <a href="mailto:<?php echo (CONTRIB_EMAIL_LINK); ?>">email</a> me!</p>
<?php } ?>
</div>
<?php endif; ?>

<?php if ($_SERVER['HTTP_HOST'] != 'localhost' && GOOGLE_ANALYTICS_CODE) : ?>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("<?php echo(GOOGLE_ANALYTICS_CODE); ?>");
pageTracker._trackPageview();
} catch(err) {}</script>
<?php endif; ?>

</body>
</html>
