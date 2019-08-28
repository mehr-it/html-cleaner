# HTML cleaner for PHP
This library aims to offer an easy API for removing unwanted elements from a given HTML fragment.
This is required when outputting HTML from an untrusted source such as browsers, API clients or
other third parties.

# Usage

The `HtmlCleaner` class allows to define blacklists and whitelists for element types, tag 
names and attributes. If more customization is required, callbacks my be defined for filtering.


## Restrict allowed tags

The following example only allows `<p>` and `<br>` tags. All other tags **and their content** are
removed.

    $cleaned = (new HtmlCleaner())
        ->setTagWhitelist(['p', 'br'])
        ->cleanFragment($html);
        
Instead of a whitelist, a blacklist can be used via `setTagBlacklist()` or event a callback
which receives the tag name and must return `true` to keep the designated tag:

    $cleaned = (new HtmlCleaner())
        ->setTagCallback(function($tag, $cleaner) {
            return $tag == 'span';
        })
        ->cleanFragment($html);
        
     
## Restrict element types
HTML also contains other elements, such as comments and CDATA. They cannot be filtered by
tag name, but using the element filter functions in the same way as for tag restriction.
Following example only allows tags and text nodes:

    $cleaned = (new HtmlCleaner())
        ->setElementTypeWhitelist([
            HtmlCleaner::ELEMENT_TYPE_TAG,
            HtmlCleaner::ELEMENT_TYPE_TEXT,
        ])
        ->cleanFragment($html); 
        
        
## Filter attributes
Even if certain tags should be allowed, some attributes might have to be removed. Following
example only allows `style` attributes:

    $cleaned = (new HtmlCleaner())
        ->setAttributeWhitelist(['style'])
        ->cleanFragment($html);
        
        
If all attributes should be removed, the blacklist with the wildcard entry `'*'` can be used:

     $cleaned = (new HtmlCleaner())
            ->setAttributeBlacklist(['*'])
            ->cleanFragment($html);
            
            
## Replacing nodes

Imagine following HTML fragment:

    <p>A big search engine is called <a href="https://www.google.com">Google</a>.</p>
    
Simply removing all `<a>` tags, would also cause their content to be removed. But what if the
text should be kept? Here the replacing functionality comes in. Following example replaces
all `<a>` tags with `<span>`:

    $cleaned = (new HtmlCleaner())
        ->setReplacements([
            'a' => 'span'
        ])
        ->cleanFragment($html);
        
    // output: "<p>A big search engine is called <span>Google</span>.</p>"
        
As you see, existing attributes are removed automatically.
    
To get rid of the `<span>` tags, you may simply pass `null` as value, to only keep the
text content of a node:

    $cleaned = (new HtmlCleaner())
        ->setReplacements([
            'a' => null
        ])
        ->cleanFragment($html);
    
    // output: "<p>A big search engine is called Google.</p>"
    
    
You may event pass a `Closure` as replacement to generate a replacement value such as a tag name,
`null` or event a newly created `DOMNode`. If the callback returns `false` the corresponding node
is not replaced but removed.