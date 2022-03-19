<?php
/* IMS/DC MFS to PHP
   Elmar Klausmeier, 07-Feb-2022
*/

//$ffcbltdli = \FFI::cdef("int CBLTDLI(const char fct[], struct IO_PCB *iopcb, char *msg, char *mfsmodn, void *nullp);","/srv/http/T/CBLTDLI.so");

function prot(string $var, int $lth) {	// protected text
	if (!isset($P[$var])) $P[$var] = str_pad('',$lth,'_');
	echo substr($P[$var],0,$lth);
}
function noprot(string $var, int $lth) {	// noprot, i.e., input
	if (!isset($P[$var])) $P[$var] = str_pad('',$lth,'_');
	printf('<input class="%s" type=text size=%d maxlength=%d id="%s" name=%s value="%s">',$var,$lth,$lth,$var,$var,$P[$var]);
}

function hidden(string $var, int $lth) {
	if (!isset($P[$var])) $P[$var] = str_pad('',$lth,'_');
	printf('<input type=hidden size=%d maxlength=%d name=%s value="%s">',$lth,$lth,$var,$P[$var]);
}

function dbgprt() {
	if (strncmp($_SERVER["QUERY_STRING"],"debug",5) == 0) {
		echo "<pre>\n";
		var_dump($_POST);
		var_dump($P);
		echo "</pre>\n";
	}
}

function getPost(string $x) {
	return array_key_exists($x,$_POST) ? $_POST[$x] : "";
}

function checkRet(int $ret) {
	if ($ret == 0) return;

	printf("Internal error: ret=%d\n",$ret);
	exit(1);
}

function callCobol(string $script_name) {
}

?>

