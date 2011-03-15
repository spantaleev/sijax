Sijax stands for "Simple ajax" and provides just that.
It's a simple php/jquery library providing easy ajax integration for php web apps.

Sijax is very similar to [Xajax](http://xajax-project.org/), which was developed way before it.
The difference is that Sijax aims to be a lot simpler and faster, while still providing the majority of features and extensibility.

There are sample files in `Sijax/examples` that demonstrate how it can be used.

## How does it work? ##

Sijax lets you register any function (simple function, static class method, object method, closure) to be called from the client (browser) using javascript like this:

    Sijax.request('myFunction', ['argument 1', 15.84]);

Ajax support is provided by [jQuery](http://jquery.com/) at the low-level. Sijax only handles dispatching the correct registered function on the server, and interpreting the response.

A registered function may be referred to as response function. It gets triggered with a javascript call, and receives a `Response object` as its first argument. By calling different methods on the `Response object` the response function talks back to the browser.
Here's how the myFunction implementation might look on the PHP side:

    function myFunction(Core_Sijax_Response $objResponse, $message, $double) {
        $objResponse->alert('Argument 1: ' . $message);
    }

Once the response function exits, the `queued commands` (like `alert()`, or any other method called on the response object) would be send to the browser. `alert()` shows an annoying alert window in the browser.

## What response functions are available? ##

- `alert($message)` - shows the alert message
- `html($selector, $html)` - sets the given `$html` to all elements matching the jQuery selector `$selector`
- `htmlAppend($selector, $html)` - same as `html()`, but appends html instead of setting the new html
- `htmlPrepend($selector, $html)` - same as `html()`, but prepends html instead of setting the new html
- `attr($selector, $property, $value)` - changes the `$property` to `$value` for all elements matching the jQuery selector `$selector`
- `attrAppend($selector, $property, $value)` - same as `attr()`, but appends to the property value, instead of setting a new value
- `attrPrepend($selector, $property, $value)` - same as `attr()`, but prepends to the property value, instead of setting a new value
- `css($selector, $property, $value)` - changes the style `$property` to `$value` for all elements matching the jQuery selector `$selector`
- `script($javascript)` - executes the given `$javascript` code
- `remove($selector)` - removes all DOM elements matching the selector
- `redirect($url)` - redirects the browser to the given `$url`
- `call($function, $argumentsArray)` - calls a javascript function named `$function`, passing the given arguments to it

Here's an example on how to use some of them:

    function myFunction(Core_Sijax_Response $objResponse, $message, $double) {
        //Supposing we have: `<div id="message-container"></div>`
        $objResponse->html('#message-container', $message);

        //Supposing we have: `<input type="text" id="total-sum" />`
        $objResponse->attr('#total-sum', 'value', $double * 4);
	
        $objResponse->alert('Sum was calculated!');
	
        //Let's remove all DIVs and the input box now
        $objResponse->remove('div')->remove('#total-sum');
	
        $objResponse->alert('Redirecting you..');

        //Let's redirect the user away
        $objResponse->redirect('http://github.com/');
    }

## How light is it? ##

The javascript core is about 4kB in its original form. Keep in mind that you need jQuery loaded on the page for it to function. It was tested with jQuery `1.4` and `1.5`, but some older releases should/may also work.

## Are there any other dependencies? ##

JSON is used for passing messages around, so you'll need `json_encode()` on the server. Which means **PHP >= 5.2.0** is required.

JSON is also needed (for encoding messages) in the browser, so browsers having no native JSON support (like IE <= 7) need to load the additional JSON library (3kB).

Sijax will detect such browsers and load the library for them, provided you have pointed to it like so:

    Core_Sijax::setJsonUri('{URI TO json2.js}');
    
The `json2.js` file is also hosted with this project, and can be found in the `js/` directory.

Browsers that do have native JSON support, won't need to load this additional resource.

## This looks very similar to Xajax. Which one should I use? ##

Yes, indeed. This library was hugely influenced by [Xajax](http://xajax-project.org), which is also actively developed and really nice.

Xajax provides certain features that you may need that we don't provide. See **known limitations** to see what they are.

Our goal was to make Sijax work as fast as possible and be as light as possible, while providing MOST of the frequently used features of Xajax.

We believe Sijax to be a lot faster and lighter than Xajax on the server-side. Thanks to [jQuery](http://jquery.com/) and the number of features we support, our javascript library is also about 10 times smaller.

## Known limitations ##

- Requires jQuery - since most projects probably already use jQuery, this may not be a problem
- Only supports utf-8
- Requires JSON - an additional 3kB library has to be loaded (automatically) for IE <= 7
- We don't handle magic quotes - if you have that enabled, you'll have to do your own $_POST processing
- Probably not as extensible and configurable as Xajax
- Lacks certain plugins that are already developed for Xajax

## What's with the Core_ stuff? ##

Sijax is one component of a bigger framework developed and maintained by me, which hasn't been released yet. All core framework classes are prefixed/"namespaced" with `Core_`.

## Would this work in CodeIgniter/Zend Framework or other frameworks? ##

It's very likely it would go smoothly. Just start our autoloader (`Core_Loader`) at any point, before you need to use the `Core_Sijax_*` classes, and it should be fine.

## Do you support comet? ##

Yes, comet streaming is supported via the comet plugin. You can look at `Sijax/examples/comet.php` for more details.

We only provide a very simple implementation (using a hidden iframe), because it works in all browsers and that's probably all that's needed for simple streaming usage.

If you need to get serious with long running requests and lots of concurrent users, you should look away from PHP, and pick something like node.js.

## What other plugins are available? ##

3 plugins come built-in. These are:

- Comet plugin - allows you to send some commands to the browser and continue running your php response function, before sending some more, etc.
- Upload plugin - allows you to convert any simple upload form to an ajax-enabled one
- Suggest plugin - allows you to add suggestions support to a textbox

The suggest plugin dates back years ago, and was previously developed for Xajax, but only recently ported. Its code is extremely messy and should be entirely rewritten, but feel free to use it if it works for you.

There are demos in the `Sijax/examples` directory for all plugins.