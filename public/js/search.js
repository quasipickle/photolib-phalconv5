import { $,$$ } from "./selector.js";
import { docOnLoad, on } from "./on.js";

docOnLoad(() => {
    const $search      = $("#sidebar-search");
    const $searchClear = $("#sidebar-search-clear");
    const $$items      = $$(".sidebar__list-item");
    
    on($search, "input", () => {
        const searchTerms = normalize($search.value);

        $$items.forEach(node => {
            if(normalize(node.dataset.albumName).includes(searchTerms)) {
                node.classList.remove("d-none");
            } else {
                node.classList.add("d-none");
            }
        });
    });

    // Clear button gets hidden & shown by CSS
    on($searchClear, "click", () => {
        $search.value = "";
        $$items.forEach(node => {
            node.classList.remove("d-none");
        });
    });
});

function normalize(string)
{
    return string.normalize("NFD").replace(/\p{Diacritic}/gu, "").toLowerCase()
}