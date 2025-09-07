<?php
function ta(mixed $in):void {
	if(TESTMODUS) {
		echo('<pre class="ta">');
		print_r($in);
		echo('</pre>');
	}
}
?>