/* global bootstrap */
import { $, $$ } from "./selector.js";
import { on } from "./on.js";
import { get } from "./axios-wrapper.js";

on(".contender", "click", e => {
    if (e.target.matches(".js-contender-img, .contender")) {
        const $contender = e.target.closest(".contender");
        const winnerId = $contender.dataset.contenderId;
        const loserId = Array
            .from($$(".contender"))
            .find(node => node != $contender)
            .dataset.contenderId;
        $(".battle__mask").classList.remove("d-none");
        $("#winner-id-field").value = winnerId;
        $("#loser-id-field").value = loserId;
        $("#form").submit();
    }
});
on("#battle-stats", "click", e => {
    e.preventDefault();
    get("/battle/stats", {}, "retrieve stats")
        .then(data => {
            $("#stats-body").innerHTML = data.content;

            Array.from($$('[data-bs-toggle="tooltip"]')).map($el => new bootstrap.Tooltip($el)); // eslint-disable-line quotes
            Array.from($$('[data-bs-toggle="popover"]')).map($el => new bootstrap.Popover($el)); // eslint-disable-line quotes
        });
    bootstrap.Modal.getOrCreateInstance($("#stats-modal")).show();
});
