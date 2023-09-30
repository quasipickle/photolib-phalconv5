import { $, $$ } from "./selector.js";
import { on } from "./on.js";
import { get } from "./axios-wrapper.js";

const loupeOptions = {
    magnification: 1,
    width:500,
    height:500,
    shape: "circle"
};

let disablers = [];

//17 = ctrl
on(document, "keydown", e => {
    if(e.keyCode == 17 && disablers.length == 0) {
        $$(".contender").forEach($container => {
            var $img = $(".js-contender-img", $container);
            var origUrl = $img.dataset.origSrc;

            var imgLoader = new Image();
            imgLoader.onload = () => {
                const loupe = new window.loupe.Loupe(loupeOptions);
                disablers.push(window.loupe.enableLoupe($img, origUrl, loupe));
            };
            imgLoader.src = origUrl;
        });
    }
});

on(document, "keyup", e => {
    if(e.keyCode == 17)
        disablers.forEach(disable => disable());

    disablers = [];
});

on(".contender", "click", e => {
    if (e.target.matches(".js-contender-img, .contender")) {
        const $contender = e.target.closest(".contender");
        const winnerId = $contender.dataset.id;
        const loserId = Array
            .from($$(".contender"))
            .find(node => node != $contender)
            .dataset.id;
        $(".battle__mask").classList.remove("d-none");
        $("#winner-id-field").value = winnerId;
        $("#loser-id-field").value = loserId;
        $("#form").submit();
    }
});
on("#battle-stats", "click", e => {
    e.preventDefault();
    get("/battle/stats", {}, 'retrieve stats')
        .then(data => {
            $("#stats-body").innerHTML = data.content;

            Array.from($$('[data-bs-toggle="tooltip"]')).map($el => new bootstrap.Tooltip($el))
            Array.from($$('[data-bs-toggle="popover"]')).map($el => new bootstrap.Popover($el))
        });
    const modal = bootstrap.Modal.getOrCreateInstance($("#stats-modal")).show();
});
