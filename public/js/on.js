import { $$ } from "./selector.js";

/**
 * Shortcut function for x.addEventListener
 *
 * @param DOM node|string  element     The element to attach the event listener to,
 *                                     or a selector string
 * @param string    eventName   The event name to listen for
 * @param function  fn          The function to run
 *
 * @return function The function to run - which is needed to remove listeners
 */
export function on(elements, eventName, fn, options = {}) {
    elements = makeIterable(elements);

    for (var element of elements) {
        element.addEventListener(eventName, fn, options);
    }

    return fn;
}

/**
 * Same as on(), but only triggered once, then removed
 */
export function once(elements, eventName, fn) {
    on(elements, eventName, fn, { once: true });

    return fn;
}

/**
 * Same as on(), but removes instead of adds
 */
export function off(elements, eventName, fn){
    elements = makeIterable(elements);
    for(var element of elements){
        element.removeEventListener(eventName, fn);
    }
}

function makeIterable(elements) {
    if (elements == null) {
        throw "Element passed to `on()` was null";
    } else if (typeof elements === "string") {
        elements = $$(elements);
    } else if (elements instanceof HTMLElement || elements === document || elements === window) {
        elements = [elements];
    }

    return elements;
}


/**
 * Shortcut for the shortcut on().  Sets the element to listen on to `document`
 *
 * @param string    eventName   The event name to listen for
 * @param function  fn          The function to run
 */
export function docOn(eventName, fn) {
    return on(document, eventName, fn);
}

/**
 * Shortcut for on(document,"DOMContentLoaded",function...);
 * 
 * @param function fn The function to run
 */
export function docOnLoad(fn) {
    return on(document, "DOMContentLoaded", fn);
}

/**
 * Same as docOn, but only triggered once, then removed
 */
export function docOnce(eventName, fn) {
    once(document, eventName, fn);
}