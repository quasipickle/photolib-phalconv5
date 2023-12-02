/* global bootstrap */
import { $, $$ } from "./selector.js";
import { docOn, on } from "./on.js";
import { post } from "./axios-wrapper.js";

Array.from($$('[data-bs-toggle="popover"]')).map($el => new bootstrap.Popover($el)); // eslint-disable-line quotes

const $duplicatesCount = $("#duplicates-count");
let duplicateCount = $duplicatesCount != null ? parseInt($duplicatesCount.textContent, 10) : 0;

on("#clear-form", "submit", evt => {    
    var c = confirm("This will clear all duplicate records - even the ignored duplicates.  Are you sure?");
    if(!c)
        evt.preventDefault();
});

docOn("click", evt => {
    if(evt.target.matches(".js-take"))
    {
        const data = {
            duplicateId: evt.target.dataset.duplicateId,
            take: evt.target.dataset.take
        };

        post("/duplicates/take", data, "resolve a duplicate")
            .then(returned => {
                if(returned.success)
                    removeDuplicate(evt.target);
                else
                    alert(returned.error);
            });
    }

    if(evt.target.matches(".js-ignore"))
    {
        const data = { duplicateId: evt.target.dataset.duplicateId };

        post("/duplicates/ignore", data, "ignore a duplicate")
            .then(returned => {
                if(returned.success) 
                    removeDuplicate(evt.target);
                else
                    alert(returned.error);
            });
    }
});

function removeDuplicate($el)
{
    $el.closest(".duplicate").remove();
    duplicateCount--;
    $duplicatesCount.textContent = duplicateCount;
}