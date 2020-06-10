<?php


	namespace MehrIt\HtmlCleaner;


	use DOMDocument;
	use DOMDocumentFragment;
	use DOMElement;
	use DOMNode;
	use DOMNodeList;
	use InvalidArgumentException;
	use Masterminds\HTML5;
	use RuntimeException;

	class HtmlCleaner
	{
		const FILTER_TYPE = 'type';
		const FILTER_TAG = 'tag';
		const FILTER_ATTRIBUTE = 'attribute';

		const ELEMENT_TYPE_TAG = 'tag';
		const ELEMENT_TYPE_COMMENT = 'comment';
		const ELEMENT_TYPE_CDATA = 'cdata';
		const ELEMENT_TYPE_TEXT = 'text';


		protected $filterBlacklist = [
			self::FILTER_TYPE      => [],
			self::FILTER_TAG       => [
				'invalid', // This tag is added automatically by HTML5 parser to replace invalid tag names. We remove it by default.
			],
			self::FILTER_ATTRIBUTE => [],
		];

		protected $filterWhitelist = [
			self::FILTER_TYPE      => ['*'],
			self::FILTER_TAG       => ['*'],
			self::FILTER_ATTRIBUTE => ['*'],
		];

		protected $filterCallbacks = [
			self::FILTER_TYPE      => null,
			self::FILTER_TAG       => null,
			self::FILTER_ATTRIBUTE => null,
		];

		protected $unwraps = [];

		protected $replacements = [];

		protected $removeStack = [];

		protected $replaceStack = [];

		protected $currentElement;

		protected $currentAttr;

		/**
		 * @var DOMDocument|null
		 */
		protected $ownerDocument;

		/**
		 * Gets the owner document for current node
		 * @return DOMDocument|null
		 */
		public function getCurrentDocument(): ?DOMDocument {
			return $this->ownerDocument;
		}

		/**
		 * Gets the current element
		 * @return DOMNode|null The current element
		 */
		public function getCurrentElement(): ?DOMNode  {
			return $this->currentElement;
		}

		/**
		 * Gets the current attribute
		 * @return DOMNode|null The current attribute
		 */
		public function getCurrentAttribute(): ?DOMNode {
			return $this->currentAttr;
		}

		/**
		 * Sets the element type blacklist
		 * @param string[] $items The blacklist items. '*' will match any type.
		 * @return HtmlCleaner
		 */
		public function setElementTypeBlacklist(array $items) : HtmlCleaner {
			$this->filterBlacklist[self::FILTER_TYPE] = $items;

			return $this;
		}

		/**
		 * Gets the element type blacklist
		 * @return string[] The blacklist items
		 */
		public function getElementTypeBlacklist(): array {
			return $this->filterBlacklist[self::FILTER_TYPE];
		}

		/**
		 * Sets the element type whitelist
		 * @param string[] $items The whitelist items. '*' will match any type.
		 * @return HtmlCleaner
		 */
		public function setElementTypeWhitelist(array $items) : HtmlCleaner {
			$this->filterWhitelist[self::FILTER_TYPE] = $items;

			return $this;
		}

		/**
		 * Gets the element type whitelist
		 * @return string[] The whitelist items
		 */
		public function getElementTypeWhitelist(): array {
			return $this->filterWhitelist[self::FILTER_TYPE];
		}

		/**
		 * Sets the element type filter callback
		 * @param callable|null $callback The callback
		 * @return HtmlCleaner
		 */
		public function setElementTypeCallback($callback): HtmlCleaner {
			$this->filterCallbacks[self::FILTER_TYPE] = $callback;

			return $this;
		}

		/**
		 * Gets the element type filter callback
		 * @return callable|null The callback if set. Else null
		 */
		public function getElementTypeCallback(): ?callable {
			return $this->filterCallbacks[self::FILTER_TYPE] ?: null;
		}
		
		/**
		 * Sets the tag blacklist
		 * @param string[] $items The blacklist items. '*' will match any tag.
		 * @return HtmlCleaner
		 */
		public function setTagBlacklist(array $items) : HtmlCleaner {
			$this->filterBlacklist[self::FILTER_TAG] = $items;

			return $this;
		}

		/**
		 * Gets the tag blacklist
		 * @return string[] The blacklist items
		 */
		public function getTagBlacklist(): array {
			return $this->filterBlacklist[self::FILTER_TAG];
		}

		/**
		 * Sets the tag whitelist
		 * @param string[] $items The whitelist items. '*' will match any tag.
		 * @return HtmlCleaner
		 */
		public function setTagWhitelist(array $items) : HtmlCleaner {
			$this->filterWhitelist[self::FILTER_TAG] = $items;

			return $this;
		}

		/**
		 * Gets the tag whitelist
		 * @return string[] The whitelist items
		 */
		public function getTagWhitelist(): array {
			return $this->filterWhitelist[self::FILTER_TAG];
		}

		/**
		 * Sets the tag filter callback
		 * @param callable|null $callback The callback
		 * @return HtmlCleaner
		 */
		public function setTagCallback($callback): HtmlCleaner {
			$this->filterCallbacks[self::FILTER_TAG] = $callback;

			return $this;
		}

		/**
		 * Gets the tag filter callback
		 * @return callable|null The callback if set. Else null
		 */
		public function getTagCallback(): ?callable {
			return $this->filterCallbacks[self::FILTER_TAG] ?: null;
		}
		
		/**
		 * Sets the attribute blacklist
		 * @param string[] $items The blacklist items. '*' will match any attribute.
		 * @return HtmlCleaner
		 */
		public function setAttributeBlacklist(array $items) : HtmlCleaner {
			$this->filterBlacklist[self::FILTER_ATTRIBUTE] = $items;

			return $this;
		}

		/**
		 * Gets the attribute blacklist
		 * @return string[] The blacklist items
		 */
		public function getAttributeBlacklist(): array {
			return $this->filterBlacklist[self::FILTER_ATTRIBUTE];
		}

		/**
		 * Sets the attribute whitelist
		 * @param string[] $items The whitelist items. '*' will match any attribute.
		 * @return HtmlCleaner
		 */
		public function setAttributeWhitelist(array $items) : HtmlCleaner {
			$this->filterWhitelist[self::FILTER_ATTRIBUTE] = $items;

			return $this;
		}

		/**
		 * Gets the attribute whitelist
		 * @return string[] The whitelist items
		 */
		public function getAttributeWhitelist(): array {
			return $this->filterWhitelist[self::FILTER_ATTRIBUTE];
		}

		/**
		 * Sets the attribute filter callback
		 * @param callable|null $callback The callback
		 * @return HtmlCleaner
		 */
		public function setAttributeCallback($callback): HtmlCleaner {
			$this->filterCallbacks[self::FILTER_ATTRIBUTE] = $callback;

			return $this;
		}

		/**
		 * Gets the attribute filter callback
		 * @return callable|null The callback if set. Else null
		 */
		public function getAttributeCallback(): ?callable {
			return $this->filterCallbacks[self::FILTER_ATTRIBUTE] ?: null;
		}

		/**
		 * Sets the replacements for HTML nodes
		 * @param string[]|null[]|callable[] $replacements The replacements. Node tag as key. If value is a string, it is used as new tag name. If value is null, the node is replaced with it's text content. If value is a callable, it may return a string, null or even a new DOMNode instance. If the callable returns any other value, the node is removed instead of replaced.
		 * @return $this
		 */
		public function setReplacements(array $replacements): HtmlCleaner {
			$this->replacements = $replacements;

			return $this;
		}

		/**
		 * Gets the replacements for HTML nodes
		 * @return string[]|null[]|callable[] The replacements
		 */
		public function getReplacements() : array {
			return $this->replacements;
		}

		/**
		 * Gets the HTML nodes to unwrap
		 * @return string[] The node to unwrap. '*' is a wildcard
		 */
		public function getUnwraps(): array {
			return $this->unwraps;
		}


		/**
		 * Sets the HTML nodes to unwrap
		 * @param string[]|string[][]|DOMNode[][] $unwraps The node to unwrap. '*' is a wildcard
		 * @return $this
		 */
		public function setUnwraps(array $unwraps): HtmlCleaner {

			$map = [];
			foreach($unwraps as $key => $value) {
				if (is_numeric($key))
					$map[$value] = [':innerHtml'];
				else
					$map[$key] = is_iterable($value) ? $value : [$value];
			}

			$this->unwraps = $map;

			return $this;
		}

		/**
		 * Cleans the given HTML fragment applying the current filters
		 * @param string $fragment The HTML fragment
		 * @return string The cleaned HTML fragment
		 */
		public function cleanFragment(string $fragment) : string {

			$html5 = new HTML5();

			// parse the fragment
			$domFragment = $html5->parseFragment($fragment);

			// clean the DOM
			$this->cleanDOM($domFragment);

			// serialize
			return $html5->saveHTML($domFragment);
		}

		/**
		 * Cleans the given HTML document applying the current filters
		 * @param string $document The document HTML
		 * @return string The cleaned document HTML
		 */
// not used and tested so far
//		public function cleanDocument(string $document) : string {
//
//			$html5 = new HTML5();
//
//			// parse the document
//			$domDoc = $html5->parse($document);
//
//			// clean the DOM
//			$this->cleanDOM($domDoc);
//
//			// serialize
//			return $html5->saveHTML($domDoc);
//		}

		/**
		 * Cleans the given DOM applying the current filters
		 * @param DOMDocumentFragment $dom
		 */
		public function cleanDOM($dom) {

			$this->removeStack  = [];
			$this->replaceStack = [];

			// not used and tested so far
			/*if ($dom instanceof DOMDocument) {
				if ($dom->documentElement)
					$this->filterNodeList($dom->childNodes);
			}
			else*/if ($dom instanceof DOMDocumentFragment) {

				$this->ownerDocument = $dom->ownerDocument;

				if ($dom->hasChildNodes())
					$this->filterNodeList($dom->childNodes);
			}
			else {
				throw new InvalidArgumentException('Expected type of ' /* . DOMDocument::class . ' or ' */. DOMDocumentFragment::class . ', but got ' . is_object($dom) ? get_class($dom) : strtolower(gettype($dom)));
			}

			$this->ownerDocument = null;
		}

		/**
		 * Applies the filters to all nodes of the given node list
		 * @param DOMNodeList $nodes The node list
		 */
		protected function filterNodeList($nodes) {

			// We cannot remove/replace nodes while iterating. Therefore we create
			// a removal stack
			$this->removeStack[]  = [];
			$this->replaceStack[] = [];

			// iterate through children
			for ($i = 0; $i < $nodes->length; ++$i) {
				$this->filterNode($nodes->item($i));
			}

			// flush removal stack
			foreach(array_pop($this->removeStack) as $nodeToRemove) {
				/** @var DOMNode $nodeToRemove */

				$parent = $nodeToRemove->parentNode;
				if (!$parent)
					throw new RuntimeException('Cannot remove node which is not assigned to a parent node.');

				$parent->removeChild($nodeToRemove);
			}

			// flush replace stack
			foreach (array_pop($this->replaceStack) as [$node, $replacement]) {
				/** @var DOMNode $node */
				/** @var DOMNode $replacement */

				$parent = $node->parentNode;
				if (!$parent)
					throw new RuntimeException('Cannot replace node which is not assigned to a parent node.');


				if (is_array($replacement)) {

					foreach($replacement as $curr) {
						if ($curr === ':innerHtml') {
							while ($currChild = $node->firstChild) {
								$node->removeChild($currChild);
								$parent->insertBefore($currChild, $node);
							}
						}
						else if ($curr instanceof DOMNode) {
							$parent->insertBefore($curr, $node);
						}
						else if (is_string($curr)) {
							$parent->insertBefore($this->ownerDocument->createTextNode($curr), $node);
						}
						else {
							throw new \RuntimeException('Invalid replacement specified');
						}
					}

					$parent->removeChild($node);
				}
				else {
					$parent->replaceChild($replacement, $node);
				}
			}

		}

		/**
		 * Applies the filters to the given node
		 * @param DOMNode|DOMElement $node The node
		 */
		protected function filterNode($node) {
			$this->currentElement = $node;
			$this->currentAttr    = null;


			switch ($node->nodeType) {
				case XML_ELEMENT_NODE:

					// check if node should be filtered
					if (
						!$this->passesFilter(self::FILTER_TYPE, self::ELEMENT_TYPE_TAG) ||
						!$this->passesFilter(self::FILTER_TAG, $node->localName)
					) {
						$this->removeNode($node);
						break;
					}

					// check for replacement
					$replacement = false;
					if (array_key_exists($node->localName, $this->replacements))
						$replacement = $this->replacements[$node->localName];
					else if (array_key_exists('*', $this->replacements))
						$replacement = $this->replacements['*'];

					// apply replacement if any
					if ($replacement !== false) {

						$replacingNode = null;
						if (is_callable($replacement))
							$replacement = call_user_func($replacement, $node->localName, $this);

						if (is_string($replacement)) {
							$replacingNode = $this->ownerDocument->createElement($replacement);

							// move children
							if ($node->hasChildNodes()) {

								$children = [];
								foreach ($node->childNodes as $currChild) {
									$children[] = $currChild;
								}
								foreach ($children as $currChild) {
									$node->removeChild($currChild);
									$replacingNode->appendChild($currChild);
								}

								// filter replacing node
								$this->filterNodeList($replacingNode->childNodes);
							}
						}
						elseif ($replacement === null) {
							$replacingNode = $this->ownerDocument->createTextNode($node->textContent);
						}
						elseif ($replacement instanceof DOMNode) {
							$replacingNode = $replacement;
						}
						else {
							$replacingNode = null;
						}


						if ($replacingNode)
							$this->replace($node, $replacingNode);
						else
							$this->removeNode($node);

						break;
					}

					// unwrap
					$unwrap = $this->unwraps[$node->localName] ?? $this->unwraps['*'] ?? false;
					if ($unwrap)
						$this->replace($node, $unwrap);


					// filter attributes
					if (!$unwrap && $node->hasAttributes()) {
						$attrToRemove = [];
						foreach($node->attributes as $curr) {
							$this->currentAttr = $curr;

							if (!$this->passesFilter(self::FILTER_ATTRIBUTE, $curr->localName))
								$attrToRemove[] = $curr;
						}
						foreach($attrToRemove as $attr) {
							$node->removeAttributeNode($attr);
						}
					}

					// apply filter to child nodes
					if ($node->hasChildNodes())
						$this->filterNodeList($node->childNodes);

					break;

				case XML_TEXT_NODE:
					if (!$this->passesFilter(self::FILTER_TYPE, self::ELEMENT_TYPE_TEXT))
						$this->removeNode($node);

					break;

				case XML_CDATA_SECTION_NODE:
					if (!$this->passesFilter(self::FILTER_TYPE, self::ELEMENT_TYPE_CDATA))
						$this->removeNode($node);
					break;

				case XML_PI_NODE:
					// we remove all PI nodes
					$this->removeNode($node);
					break;

				case XML_COMMENT_NODE:
					if (!$this->passesFilter(self::FILTER_TYPE, self::ELEMENT_TYPE_COMMENT))
						$this->removeNode($node);
					break;
			}

			$this->currentElement = null;
			$this->currentAttr    = null;
		}

		/**
		 * Removes the given node from it's parent
		 * @param DOMNode $node The node
		 */
		protected function removeNode($node) {

			// We cannot remove the node right now, because this would break
			// the iteration over parent's children. Therefore we put it on
			// the removal stack
			$this->removeStack[count($this->removeStack) - 1][] = $node;
		}

		/**
		 * Replaces the node with another one
		 * @param DOMNode $node The node
		 * @param DOMNode|DOMNode[]|string[] $replacement The replacement
		 */
		protected function replace($node, $replacement) {

			// We cannot remove the node right now, because this would break
			// the iteration over parent's children. Therefore we put it on
			// the removal stack
			$this->replaceStack[count($this->replaceStack) - 1][] = [$node, $replacement];
		}

		/**
		 * Checks if the given element type is allowed
		 * @param string $filter The filter to check with
		 * @param string $value The value
		 * @return bool True if passing. Else false.
		 */
		protected function passesFilter(string $filter, string $value) {

			// check white- and blacklist
			if (!$this->matchesFilterList($value, $this->filterWhitelist[$filter]) || $this->matchesFilterList($value, $this->filterBlacklist[$filter]))
				return false;

			// invoke user callback
			if ($this->filterCallbacks[$filter])
				return call_user_func($this->filterCallbacks[$filter], $value, $this);

			return true;
		}

		/**
		 * Returns if the given name matches the given list
		 * @param string $name The name
		 * @param string[] $list The list. Wildcard element is represented by '*'
		 * @return bool True if matching. Else false.
		 */
		protected function matchesFilterList(string $name, array $list): bool {

			foreach ($list as $curr) {
				if ($curr === '*')
					return true;

				// check if in list
				if ($name === $curr)
					return true;
			}

			return false;
		}



	}