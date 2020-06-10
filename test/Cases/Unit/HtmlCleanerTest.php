<?php


	namespace MehrItHtmlCleanerTest\Cases\Unit;


	use MehrIt\HtmlCleaner\HtmlCleaner;

	class HtmlCleanerTest extends TestCase
	{

		protected function wrapDocument(string $fragment) {
			return "<!DOCTYPE html>\n<html><body>{$fragment}</body></html>";
		}

		public function testElementTypeFilterGettersAndSetters() {

			$cleaner = new HtmlCleaner();

			$cb = function() {};

			$this->assertSame($cleaner, $cleaner->setElementTypeBlacklist(['g', 'h']));
			$this->assertSame($cleaner, $cleaner->setElementTypeWhitelist(['i', 'j']));
			$this->assertSame($cleaner, $cleaner->setElementTypeCallback($cb));
			$this->assertSame(['g', 'h'], $cleaner->getElementTypeBlacklist());
			$this->assertSame(['i', 'j'], $cleaner->getElementTypeWhitelist());
			$this->assertSame($cb, $cleaner->getElementTypeCallback());

		}

		public function testTagFilterGettersAndSetters() {

			$cleaner = new HtmlCleaner();

			$cb = function() {};

			$this->assertSame($cleaner, $cleaner->setTagBlacklist(['g', 'h']));
			$this->assertSame($cleaner, $cleaner->setTagWhitelist(['i', 'j']));
			$this->assertSame($cleaner, $cleaner->setTagCallback($cb));
			$this->assertSame(['g', 'h'], $cleaner->getTagBlacklist());
			$this->assertSame(['i', 'j'], $cleaner->getTagWhitelist());
			$this->assertSame($cb, $cleaner->getTagCallback());

		}

		public function testAttributeFilterGettersAndSetters() {

			$cleaner = new HtmlCleaner();

			$cb = function() {};

			$this->assertSame($cleaner, $cleaner->setAttributeBlacklist(['g', 'h']));
			$this->assertSame($cleaner, $cleaner->setAttributeWhitelist(['i', 'j']));
			$this->assertSame($cleaner, $cleaner->setAttributeCallback($cb));
			$this->assertSame(['g', 'h'], $cleaner->getAttributeBlacklist());
			$this->assertSame(['i', 'j'], $cleaner->getAttributeWhitelist());
			$this->assertSame($cb, $cleaner->getAttributeCallback());

		}

		public function testReplacementsGettersAndSetters() {

			$cleaner = new HtmlCleaner();

			$this->assertSame($cleaner, $cleaner->setReplacements(['g' => 'z', 'h' => 'y']));
			$this->assertSame(['g' => 'z', 'h' => 'y'], $cleaner->getReplacements());

		}

		public function testCleanFragment() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setElementTypeWhitelist([HtmlCleaner::ELEMENT_TYPE_COMMENT, HtmlCleaner::ELEMENT_TYPE_TAG, HtmlCleaner::ELEMENT_TYPE_TEXT])
				->setTagWhitelist(['div', 'p'])
				->setAttributeWhitelist(['class'])
				->cleanFragment($html);

			$this->assertSame('<div class="my-class"><p>Text </p><!-- the comment -->s</div>', trim($cleaned));

		}

		public function testCleanFragment_invalidSyntax() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setElementTypeWhitelist([HtmlCleaner::ELEMENT_TYPE_COMMENT, HtmlCleaner::ELEMENT_TYPE_TAG, HtmlCleaner::ELEMENT_TYPE_TEXT])
				->setTagWhitelist(['div', 'p'])
				->setAttributeWhitelist(['class'])
				->cleanFragment($html);

			$this->assertSame('<div class="my-class"><p></p><!-- the comment -->s</div>', trim($cleaned));

		}

		public function testCleanElementTypes_whitelist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setElementTypeWhitelist([HtmlCleaner::ELEMENT_TYPE_TAG, HtmlCleaner::ELEMENT_TYPE_TEXT])
				->cleanFragment($html);

			$this->assertSame('<div class="my-class" id="15" data-x="vx"><p>Text <span id="1">number 1</span></p><b>old</b>s</div>', trim($cleaned));

		}

		public function testCleanElementTypes_blacklist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setElementTypeBlacklist([HtmlCleaner::ELEMENT_TYPE_COMMENT])
				->cleanFragment($html);

			$this->assertSame('<div class="my-class" id="15" data-x="vx"><p>Text <span id="1">number 1</span></p><b>old</b>s<![CDATA[ data-content ]]></div>', trim($cleaned));

		}

		public function testCleanElementTypes_callback() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setElementTypeCallback(function($type, $c) use ($cleaner) {

					$this->assertSame($cleaner, $c);
					$this->assertInstanceOf(\DOMNode::class, $cleaner->getCurrentElement());
					$this->assertSame(null, $cleaner->getCurrentAttribute());
					$this->assertInstanceOf(\DOMDocument::class, $cleaner->getCurrentDocument());

					return $type !== HtmlCleaner::ELEMENT_TYPE_COMMENT;
				})
				->cleanFragment($html);

			$this->assertSame('<div class="my-class" id="15" data-x="vx"><p>Text <span id="1">number 1</span></p><b>old</b>s<![CDATA[ data-content ]]></div>', trim($cleaned));

		}

		public function testCleanElementTypes_wildcardBlacklist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setElementTypeBlacklist(['*'])
				->cleanFragment($html);

			$this->assertSame('', trim($cleaned));

		}

		public function testCleanElementTypes_wildcardWhitelist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setElementTypeWhitelist(['*'])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id=\"1\">number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testCleanTags_whitelist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setTagWhitelist(['div', 'b'])
				->cleanFragment($html);

			$this->assertSame('<div class="my-class" id="15" data-x="vx"><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>', trim($cleaned));

		}

		public function testCleanTags_blacklist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setTagBlacklist(['span', 'b'])
				->cleanFragment($html);

			$this->assertSame('<div class="my-class" id="15" data-x="vx"><p>Text </p><!-- the comment -->s<![CDATA[ data-content ]]></div>', trim($cleaned));

		}

		public function testCleanTags_callback() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setTagCallback(function ($tag, $c) use ($cleaner) {

					$this->assertSame($cleaner, $c);
					$this->assertSame($tag, $cleaner->getCurrentElement()->localName);
					$this->assertSame(null, $cleaner->getCurrentAttribute());
					$this->assertInstanceOf(\DOMDocument::class, $cleaner->getCurrentDocument());

					return $tag != 'p';
				})
				->cleanFragment($html);

			$this->assertSame('<div class="my-class" id="15" data-x="vx"><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>', trim($cleaned));

		}

		public function testCleanTags_wildcardWhitelist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setTagWhitelist(['*'])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id=\"1\">number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testCleanTags_wildcardBlacklist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setTagBlacklist(['*'])
				->cleanFragment($html);

			$this->assertSame("", trim($cleaned));

		}

		public function testCleanAttributes_whitelist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setAttributeWhitelist(['class', 'data-x'])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" data-x=\"vx\"><p>Text <span>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testCleanAttributes_blacklist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setAttributeBlacklist(['id', 'data-x'])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\"><p>Text <span>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testCleanAttributes_callback() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setAttributeCallback(function ($attribute, $c) use ($cleaner) {

					$this->assertSame($cleaner, $c);
					$this->assertInstanceOf(\DOMNode::class, $cleaner->getCurrentElement());
					$this->assertSame($attribute, $cleaner->getCurrentAttribute()->localName);
					$this->assertInstanceOf(\DOMDocument::class, $cleaner->getCurrentDocument());

					return $attribute != 'id';
				})
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" data-x=\"vx\"><p>Text <span>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testCleanAttributes_wildcardWhitelist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setAttributeWhitelist(['*'])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id=\"1\">number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testCleanAttributes_wildcardBlacklist() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p>Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setAttributeBlacklist(['*'])
				->cleanFragment($html);

			$this->assertSame("<div><p>Text <span>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testReplace() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setReplacements([
					'p' => 'div'
				])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"><div>Text <span id=\"1\">number 1</span></div><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testReplace_recursive() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setReplacements([
					'p' => 'div',
					'span' => 'p',
				])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"><div>Text <p>number 1</p></div><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testReplace_childrenFiltered() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setReplacements([
					'p' => 'div',
				])
				->setTagBlacklist(['span'])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"><div>Text </div><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testReplace_onlyText() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setReplacements([
					'p' => null,
				])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\">Text number 1<b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testReplace_wildcard() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setReplacements([
					'*' => 'b',
				])
				->cleanFragment($html);

			$this->assertSame("<b><b>Text <b>number 1</b></b><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></b>", trim($cleaned));

		}

		public function testReplace_callback_returningString() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setReplacements([
					'p' => function($tag, $c) use ($cleaner) {
						$this->assertSame($cleaner, $c);
						$this->assertSame('p', $tag);

						$this->assertSame('p', $cleaner->getCurrentElement()->localName);
						$this->assertSame(null, $cleaner->getCurrentAttribute());
						$this->assertInstanceOf(\DOMDocument::class, $cleaner->getCurrentDocument());

						return 'span';
					},
				])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"><span>Text <span id=\"1\">number 1</span></span><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testReplace_callback_returningNull() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setReplacements([
					'p' => function($tag, $c) use ($cleaner) {
						$this->assertSame($cleaner, $c);
						$this->assertSame('p', $tag);

						$this->assertSame('p', $cleaner->getCurrentElement()->localName);
						$this->assertSame(null, $cleaner->getCurrentAttribute());
						$this->assertInstanceOf(\DOMDocument::class, $cleaner->getCurrentDocument());

						return null;
					},
				])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\">Text number 1<b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testReplace_callback_returningFalse() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setReplacements([
					'p' => function($tag, $c) use ($cleaner) {
						$this->assertSame($cleaner, $c);
						$this->assertSame('p', $tag);

						$this->assertSame('p', $cleaner->getCurrentElement()->localName);
						$this->assertSame(null, $cleaner->getCurrentAttribute());
						$this->assertInstanceOf(\DOMDocument::class, $cleaner->getCurrentDocument());

						return false;
					},
				])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testReplace_callback_returningDomNode() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id='1'>number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setReplacements([
					'p' => function($tag, $c) use ($cleaner) {
						$this->assertSame($cleaner, $c);
						$this->assertSame('p', $tag);

						$this->assertSame('p', $cleaner->getCurrentElement()->localName);
						$this->assertSame(null, $cleaner->getCurrentAttribute());
						$this->assertInstanceOf(\DOMDocument::class, $cleaner->getCurrentDocument());

						return $cleaner->getCurrentDocument()->createElement('test', 'me');
					},
				])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"><test>me</test><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testUnwrap() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id=\"1\">number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setUnwraps([
					'p',
					'b',
				])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\">Text <span id=\"1\">number 1</span>old<!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

		public function testUnwrap_wildcard() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id=\"1\">number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setUnwraps([
					'*',
				])
				->cleanFragment($html);

			$this->assertSame("Text number 1old<!-- the comment -->s<![CDATA[ data-content ]]>", trim($cleaned));

		}

		public function testUnwrap_custom() {

			$html = "<div class=\"my-class\" id=\"15\" data-x=\"vx\"><p class=\"xp\">Text <span id=\"1\">number 1</span></p><b>old</b><!-- the comment -->s<![CDATA[ data-content ]]></div>";

			$cleaned = ($cleaner = new HtmlCleaner())
				->setUnwraps([
					'p' => [' ', ':innerHtml', ' '],
					'b',
				])
				->cleanFragment($html);

			$this->assertSame("<div class=\"my-class\" id=\"15\" data-x=\"vx\"> Text <span id=\"1\">number 1</span> old<!-- the comment -->s<![CDATA[ data-content ]]></div>", trim($cleaned));

		}

	}