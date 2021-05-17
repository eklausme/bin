<?php
/* Extension modeled after: https://saaze.dev/docs/extending
   Elmar Klausmeier, 05-Apr-2021
   Elmar Klausmeier, 11-Apr-2021
   Elmar Klausmeier, 22-Apr-2021, added wpvideo
   Elmar Klausmeier, 12-May-2021, added amplink() to correct href
*/

use Saaze\Content\MarkdownContentParser;

class MathParser extends MarkdownContentParser {
	/**
	 * Work on abc $$uvw$$ xyz.
	 * Needs MathJax. For this you have to include:
	 *    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
	 *    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
	 *
	 * @param string $content
	 * @return string
	 */
	private function displayMath($content) {
		$last = 0;
		for (;;) {
			$start = strpos($content,"$$",$last);
			if ($start === false) break;
			$end = strpos($content,"$$",$start+2);
			if ($end === false) break;
			$last = $end + 2;
			$math = substr($content,$start,$last-$start);
			$math = str_replace("\\>","\\: ",$math);
			$math = str_replace("<","\\lt ",$math);
			$math = str_replace(">","\\gt ",$math);
			//printf("toHtml(): fileToRender=%s, last=%d, start=%d, end=%d, %s[%s]\n",$GLOBALS['fileToRender'],$last,$start,$end,substr($content,$start,$end-$start+2),substr($content,0,12));
			$content = substr($content,0,$start)
				. "\n<div class=math>\n"
				. $math
				. "\n</div>\n"
				. substr($content,$last);
			$last = $start + strlen("\n<div class=math>\n") + strlen($math) + strlen("\n</div>\n");
		}
		return $content;
	}


	/**
	 * Work on abc $uvw$ xyz.
	 * @param string $content
	 * @return string
	 */
	private function inlineMath($content) {
		$last = 0;
		$i = 0;
		for (;;) {
			//if (++$i > 10) break;
			$start = strpos($content,"$",$last);
			if ($start === false) break;
			// Check if display math with double dollar found?
			if (substr($content,$start+1,1) == "$") { $last = $start + 2; continue; }
			$end = strpos($content,"$",$start+1);
			if ($end === false) break;
			// Check for display math again, just in case
			if (substr($content,$end+1,1) == "$") { $last = $end + 2; continue; }
			// Replace $xyz$" with \\(xyz\\)
			$last = $end + 1;
			$math = substr($content,$start+1,$end-$start-1);
			$math = str_replace("_","\\_",$math);
			$math = str_replace("\\{","\\\\{",$math);
			$math = str_replace("\\}","\\\\}",$math);
			$content = substr($content,0,$start)
				. "\\\\("
				. $math
				. "\\\\)"
				. substr($content,$end+1);
			$last = $start + strlen("\\\\(") + strlen($math) + strlen("\\\\)");
		}
		return $content;
	}


	/**
	 * Convert [abc]xxx[/uvw] tags in your markdown to HTML:
	 * $begintag xxx $endtag -> $left xxx $right
	 *
	 * @param string $content, $begintag, $endtag, $left, $right
	 * @return string
	 */
	private function myTag($content,$begintag,$endtag,$left,$right) {
		$last = 0;
		$len1 = strlen($begintag);
		$len2 = strlen($endtag);
		for (;;) {
			$start = strpos($content,$begintag,$last);
			if ($start === false) break;
			$end = strpos($content,$endtag,$start+$len1);
			if ($end === false) break;
			$xxx = trim(substr($content,$start+$len1,$end-$start-$len1));
			$last = $end + $len2;
			$content = substr_replace($content, $left . $xxx . $right, $start, $last-$start);
		}
		return $content;
	}


	/**
	 * Convert [youtube]xxx[/youtube] tags in your markdown to HTML:
	 * <iframe width="560" height="315" src=https://www.youtube.com/embed/xxx
	 *    frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	 * Example: [youtube] a5pnnkXpX-U     [/youtube]
	 *
	 * @param string $content
	 * @return string
	 */
	private function youtube($content) {
		return $this->myTag($content, "[youtube]", "[/youtube]",
			"<iframe width=560 height=315 src=https://www.youtube.com/embed/",
			" frameborder=0 allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>"
		);
	}


	/**
	 * Convert [twitter]xxx[/twitter] tags in your markdown HTML which Twitter-JavaScript understands.
	 * xxx is for example: https://twitter.com/eklausmeier/status/1352896936051937281
	 * i.e., just the URL, no other information is required.
	 * This xxx is "Copy link to Tweet" button in Twitter.
	 *
	 * Make sure that your layout-template contains the following:
	 *    <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
	 *
	 * @param string $content
	 * @return string
	 */
	private function twitter($content) {
		return $this->myTag($content, "[twitter]", "[/twitter]",
			"<blockquote class=\"twitter-tweet\"><a href=\"",
			"\"</a></blockquote>"
		);
	}


	/**
	 * Simply drop [more_WP_Tag], the WordPress <!--more--> tag
	 *
	 * @param string $content
	 * @return string
	 */
	private function moreTag($content) {
		return str_replace("[more_WP_Tag]","",$content);
	}


	/**
	 * [wpvideo xxx w=400 h=224] -> <iframe...></iframe>
	 * <iframe width='400' height='224' src='https://video.wordpress.com/embed/RLkLgz2V?hd=0&amp;autoPlay=0&amp;permalink=0&amp;loop=0' frameborder='0' allowfullscreen></iframe>
	 *
	 * @param string $content
	 * @return string
	 */
	private function wpvideo($content) {
		return preg_replace(
			'/\[wpvideo\s+(\w+)\s+w=(\w+)\s+h=(\w+)\s*\]/',
			"<iframe width='$2' height='$3' src='https://video.wordpress.com/embed/$1&amp;autoplay=0' allowfullscreen></iframe>",
			$content
		);
	}


	/**
	 * Correct Markdown bug: ampersands wrongly htmlified in links
	 * Convert href="http://a.com&amp;22" to href="http://a.com&22"
	 * @param string $html
	 * @return string
	 */
	private function amplink($html) {
		$begintag = array(" href=\"http", " src=\"http");
		$i = 0;
		foreach($begintag as $tag) {
			$last = 0;
			for(;;) {
				$start = strpos($html,$tag,$last);
				if ($start === false) break;
				$last = $start + 10;
				$end = strpos($html,"\"",$last);
				if ($end === false) break;
				$link = substr($html,$start,$end-$start);
				$link = str_replace("&amp;","&",$link);
				$html = substr_replace($html, $link, $start, $end-$start);
				++$i;
			}
		}
		//printf("\t\tamplink() changed %d times\n",$i);
		return $html;
	}


	/**
	 * Parse raw content and return HTML
	 * @param string $content
	 * @return string
	 */
	public function toHtml($content) {
		$content = $this->moreTag($content);	// more-tag can occur anywhere
		$arr = explode("`",$content);	// known deficiency: does not cope for HTML comments
		// even elements can be changed, uneven are code-block elements
		for($i=0, $size=count($arr); $i<$size; $i+=2) {
			$arr[$i] = $this->displayMath($arr[$i]);
			$arr[$i] = $this->inlineMath($arr[$i]);
			$arr[$i] = $this->youtube($arr[$i]);
			$arr[$i] = $this->twitter($arr[$i]);
			$arr[$i] = $this->wpvideo($arr[$i]);
		}
		$html = parent::toHtml(implode("`",$arr));	// markdown to HTML
		return $this->amplink($html);
	}
}

