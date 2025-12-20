<?php
/**
 * Browser Compatibility Head Section
 * Include this file in the <head> section of all pages for cross-browser compatibility
 */
?>
<!-- Cross-browser compatibility meta tags -->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
<meta name="description" content="PNG Maritime College Students Portal">
<meta name="keywords" content="PNG Maritime College, Students Portal">

<!-- Cross-browser compatibility CSS -->
<link rel="stylesheet" href="../css/browser-compat.css">

<!-- Polyfill for older browsers (loads only if needed) -->
<script>
  // Feature detection and polyfill loading
  (function() {
    var needsPolyfill = false;
    
    // Check for modern features
    if (!window.Promise) needsPolyfill = true;
    if (!Array.prototype.includes) needsPolyfill = true;
    if (!Object.assign) needsPolyfill = true;
    if (!window.fetch) needsPolyfill = true;
    
    if (needsPolyfill) {
      var script = document.createElement('script');
      script.src = 'https://polyfill.io/v3/polyfill.min.js?features=default,es5,es6,es2015,es2016,es2017,Array.prototype.includes,Object.assign,Promise,fetch,Element.prototype.closest,Element.prototype.matches';
      script.async = true;
      document.head.appendChild(script);
    }
  })();
</script>

<!-- Fallback for browsers that don't support ES6 -->
<script>
  // Ensure basic JavaScript compatibility
  if (!Array.prototype.forEach) {
    Array.prototype.forEach = function(callback, thisArg) {
      for (var i = 0; i < this.length; i++) {
        callback.call(thisArg, this[i], i, this);
      }
    };
  }
  
  if (!Array.prototype.includes) {
    Array.prototype.includes = function(searchElement) {
      for (var i = 0; i < this.length; i++) {
        if (this[i] === searchElement) return true;
      }
      return false;
    };
  }
  
  if (!String.prototype.includes) {
    String.prototype.includes = function(search) {
      return this.indexOf(search) !== -1;
    };
  }
  
  if (!Object.assign) {
    Object.assign = function(target) {
      if (target == null) {
        throw new TypeError('Cannot convert undefined or null to object');
      }
      var to = Object(target);
      for (var index = 1; index < arguments.length; index++) {
        var nextSource = arguments[index];
        if (nextSource != null) {
          for (var nextKey in nextSource) {
            if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
              to[nextKey] = nextSource[nextKey];
            }
          }
        }
      }
      return to;
    };
  }
</script>

