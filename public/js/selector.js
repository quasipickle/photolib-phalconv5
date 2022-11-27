// Select one thing
export let $ = (selector, origin) => (origin ?? document).querySelector(selector);

// Select many things
export let $$ = (selector, origin) => (origin ?? document).querySelectorAll(selector);