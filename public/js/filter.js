import { $$ } from "./selector.js";
import { docOn } from "./on.js";

/**
 * To use:
 * 
 * - Create an input group with an input and a clear button (see album/partials/album-chooser.phtml for an example)
 * - Add "filter__input" to the input field
 * - Add "filter__button" to the clearing button
 * - Add `x-data="filter(itemsSelector)" to the container around the filter fields
 *      - `itemsSelector` is the CSS selector that selects the items to be filtered
 * - Add `data-filter-on="..."` to the items to be filtered, populating the attribute with a string on which to filter
 *
 */


docOn("alpine:init", () => {
    Alpine.data("filter", (itemsSelector) => ({
        searchTerms: null,

        init(){            
            this.$watch("searchTerms",() => {
                const normalizedSearchTerms = this.normalize(this.searchTerms);
                const $$items = $$(itemsSelector);

                $$items.forEach(node => {
                    if(this.normalize(node.dataset.filterOn).includes(normalizedSearchTerms)) {
                        node.classList.remove("d-none");
                    } else {
                        node.classList.add("d-none");
                    }
                });
            });
        },
        clear(){
            this.searchTerms = null;
        },
        normalize: (string) => string?.normalize("NFD").replace(/\p{Diacritic}/gu, "").toLowerCase() ?? ""
    }));
});